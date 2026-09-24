<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\StatutAdherent;
use App\Enums\StatutPersonneACharge;
use App\Models\Adherent;
use App\Models\PersonneACharge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Importe la base d'adhérents réelle 2026 (fichier CSV fourni par la
 * mutuelle) : crée les adhérents "tuteurs" (avec compte de connexion) et
 * leurs personnes à charge. Voir le plan d'import pour le détail des règles
 * de lecture du fichier.
 */
class ImporterAdherents2026 extends Command
{
    protected $signature = 'adherents:importer-2026
        {fichier : Chemin du fichier CSV source}
        {--dry-run : Analyse le fichier sans écrire en base}
        {--export-mdp= : Chemin du CSV des mots de passe générés (défaut: storage/app/private/imports/)}';

    protected $description = "Importe la base d'adhérents réelle 2026 (tuteurs + personnes à charge + comptes)";

    /** @var array<int, string> */
    private const VILLES_CONNUES = [
        'katiola' => 'Katiola',
        'bangolo' => 'Bangolo',
        'bouake' => 'Bouaké',
        'bouaké' => 'Bouaké',
        'abidjan' => 'Abidjan',
        'guiglo' => 'Guiglo',
        'korhogo' => 'Korhogo',
        'daloa' => 'Daloa',
        'toumodi' => 'Toumodi',
        'yakro' => 'Yamoussoukro',
        'kouto' => 'Kouto',
        'odienne' => 'Odienné',
        'odienné' => 'Odienné',
        'beoumi' => 'Béoumi',
        'divo' => 'Divo',
        'kokumbo' => 'Kokumbo',
    ];

    private int $compteurMatricule = 0;

    /** @var array<int, string> */
    private array $erreurs = [];

    private int $tuteursCrees = 0;

    private int $personnesACreees = 0;

    private int $personnesDecedees = 0;

    private int $lignesIgnorees = 0;

    private int $villesNonResolues = 0;

    private int $datesAdhesionInvalides = 0;

    private int $carencesNonResolues = 0;

    /** @var array<int, array{matricule: string, nom: string, prenom: string, mot_de_passe: string}> */
    private array $motsDePasse = [];

    public function handle(): int
    {
        $chemin = $this->argument('fichier');

        if (! is_file($chemin)) {
            $this->error("Fichier introuvable : {$chemin}");

            return self::FAILURE;
        }

        $lignes = $this->lireLignesDeDonnees($chemin);

        if ($lignes === []) {
            $this->error('Aucune ligne de données exploitable trouvée dans le fichier.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();

        try {
            $this->importer($lignes);

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                $this->exporterMotsDePasse();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import interrompu : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->afficherResume($dryRun);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function lireLignesDeDonnees(string $chemin): array
    {
        $handle = fopen($chemin, 'r');
        $lignes = [];

        while (($ligne = fgetcsv($handle, escape: '\\')) !== false) {
            // Une ligne de données valide commence toujours par un N°Ordre
            // numérique : ça exclut naturellement les lignes d'en-tête, les
            // lignes vides et la ligne de totaux en fin de fichier.
            if (isset($ligne[0]) && is_numeric(trim($ligne[0]))) {
                $lignes[] = $ligne;
            }
        }

        fclose($handle);

        return $lignes;
    }

    /**
     * @param  array<int, array<int, string>>  $lignes
     */
    private function importer(array $lignes): void
    {
        $ancreBloc = null;
        $tuteurCourant = null;

        foreach ($lignes as $ligne) {
            $numeroOrdre = trim($ligne[0] ?? '');
            $nom = $this->corrigerEncodage($ligne[2] ?? '');
            $prenomsBrut = $this->corrigerEncodage($ligne[3] ?? '');
            $protegeBrut = $this->corrigerEncodage($ligne[4] ?? '');
            $dateAdhesionBrute = trim($ligne[5] ?? '');
            $delaiCarenceBrut = trim($ligne[6] ?? '');

            if (trim($nom) === '' && trim($prenomsBrut) === '') {
                $this->erreurs[] = "Ligne {$numeroOrdre} : nom et prénom vides, ignorée.";
                $this->lignesIgnorees++;

                continue;
            }

            $aUneAnnotationCarence = (bool) preg_match('/carence/iu', $prenomsBrut.' '.$protegeBrut);
            $estDecede = (bool) preg_match('/d[ée]c[ée]d[ée]e?/iu', $prenomsBrut);

            $prenoms = $this->nettoyerLibelle($prenomsBrut);
            $prenoms = trim((string) preg_replace('/\s*d[ée]c[ée]d[ée]e?\s*$/iu', '', $prenoms));
            $protege = $this->nettoyerLibelle($protegeBrut);

            $dateAdhesion = $this->parserDateSouple($dateAdhesionBrute);

            if ($dateAdhesion === null) {
                $this->erreurs[] = "Ligne {$numeroOrdre} : date d'adhésion illisible (\"{$dateAdhesionBrute}\"), ligne ignorée.";
                $this->lignesIgnorees++;
                $this->datesAdhesionInvalides++;

                continue;
            }

            $delaiCarenceIndicatif = $this->parserDateSouple($delaiCarenceBrut);

            if ($delaiCarenceBrut !== '' && $delaiCarenceIndicatif === null) {
                $this->erreurs[] = "Ligne {$numeroOrdre} : délai de carence illisible (\"{$delaiCarenceBrut}\"), champ laissé vide.";
                $this->carencesNonResolues++;
            }

            $nouveauBloc = $ancreBloc === null || ! $this->memeBloc($protege, $ancreBloc);

            if ($nouveauBloc) {
                $ancreBloc = $protege;
                $tuteurCourant = $this->creerTuteur(
                    $nom,
                    $prenoms,
                    $protege,
                    $dateAdhesion,
                    $delaiCarenceIndicatif,
                    ! $aUneAnnotationCarence,
                );

                continue;
            }

            if ($tuteurCourant === null) {
                // Ne devrait pas arriver (le premier row d'un bloc crée
                // toujours le tuteur), garde-fou défensif.
                $this->erreurs[] = "Ligne {$numeroOrdre} : impossible de rattacher cette personne à un tuteur, ignorée.";
                $this->lignesIgnorees++;

                continue;
            }

            $this->creerPersonneACharge(
                $tuteurCourant,
                $nom,
                $prenoms,
                $dateAdhesion,
                $delaiCarenceIndicatif,
                $estDecede,
                ! $aUneAnnotationCarence,
            );
        }
    }

    private function creerTuteur(
        string $nom,
        string $prenoms,
        string $protege,
        Carbon $dateAdhesion,
        ?Carbon $delaiCarenceIndicatif,
        bool $neeApres1956,
    ): Adherent {
        $this->compteurMatricule++;
        $matricule = sprintf('MIACI-%05d', $this->compteurMatricule);
        $ville = $this->extraireVille($protege);

        if ($ville === null) {
            $this->villesNonResolues++;
        }

        $adherent = Adherent::create([
            'matricule' => $matricule,
            'nom' => $nom,
            'prenom' => $prenoms,
            'ville' => $ville,
            'date_adhesion' => $dateAdhesion,
            'date_fin_carence_indicative' => $delaiCarenceIndicatif,
            'nee_apres_1956' => $neeApres1956,
            'statut' => StatutAdherent::Actif,
            'droit_adhesion_exonere' => true,
        ]);

        $motDePasse = User::MOT_DE_PASSE_PAR_DEFAUT;

        $user = User::create([
            'name' => $adherent->nomComplet(),
            'email' => strtolower($matricule).'@miaci.local',
            'password' => Hash::make($motDePasse),
        ]);
        $user->assignRole(Role::Adherent->value);

        $adherent->update(['user_id' => $user->id]);

        $this->motsDePasse[] = [
            'matricule' => $matricule,
            'nom' => $nom,
            'prenom' => $prenoms,
            'mot_de_passe' => $motDePasse,
        ];

        $this->tuteursCrees++;

        return $adherent;
    }

    private function creerPersonneACharge(
        Adherent $tuteur,
        string $nom,
        string $prenoms,
        Carbon $dateAdhesion,
        ?Carbon $delaiCarenceIndicatif,
        bool $estDecede,
        bool $neeApres1956,
    ): void {
        PersonneACharge::create([
            'adherent_id' => $tuteur->id,
            'nom' => $nom,
            'prenom' => $prenoms,
            'date_adhesion' => $dateAdhesion,
            'date_fin_carence_indicative' => $delaiCarenceIndicatif,
            'nee_apres_1956' => $neeApres1956,
            'statut' => $estDecede ? StatutPersonneACharge::Inactive : StatutPersonneACharge::Active,
            'valide_par_gestionnaire' => true,
            'valide_le' => $dateAdhesion,
            'droit_adhesion_exonere' => true,
        ]);

        $this->personnesACreees++;

        if ($estDecede) {
            $this->personnesDecedees++;
        }
    }

    private function corrigerEncodage(string $valeur): string
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            return $valeur;
        }

        $corrige = mb_convert_encoding($valeur, 'ISO-8859-1', 'UTF-8');

        // mb_convert_encoding renvoie une chaîne vide/invalide si l'entrée
        // n'était pas du "mojibake" UTF-8→Latin1 — dans ce cas on garde
        // l'original tel quel plutôt que de perdre la donnée.
        return $corrige !== '' ? $corrige : $valeur;
    }

    /**
     * Retire toutes les parenthèses (annotations : carence, décès, notes
     * diverses) qui ne font pas partie du nom/libellé réel.
     */
    private function nettoyerLibelle(string $valeur): string
    {
        $sansParentheses = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $valeur);

        return trim((string) preg_replace('/\s+/u', ' ', (string) $sansParentheses));
    }

    private function extraireVille(string $protege): ?string
    {
        $protegeMinuscule = mb_strtolower($protege);

        foreach (self::VILLES_CONNUES as $motCle => $nomAffiche) {
            if (str_contains($protegeMinuscule, $motCle)) {
                return $nomAffiche;
            }
        }

        return null;
    }

    private function memeBloc(string $a, string $b): bool
    {
        $a = mb_strtolower(trim($a));
        $b = mb_strtolower(trim($b));

        if ($a === '' || $b === '') {
            return false;
        }

        return str_starts_with($a, $b) || str_starts_with($b, $a);
    }

    private function parserDateSouple(?string $brut): ?Carbon
    {
        $brut = trim((string) $brut);

        if ($brut === '') {
            return null;
        }

        if (! preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $brut, $m)) {
            return null;
        }

        $a = (int) $m[1];
        $b = (int) $m[2];
        $annee = (int) $m[3];

        if ($a > 12 && $b <= 12) {
            $jour = $a;
            $mois = $b;
        } elseif ($b > 12 && $a <= 12) {
            $mois = $a;
            $jour = $b;
        } elseif ($a <= 12 && $b <= 12) {
            // Ambigu : le fichier utilise majoritairement le format M/J/AAAA.
            $mois = $a;
            $jour = $b;
        } else {
            return null;
        }

        if (! checkdate($mois, $jour, $annee)) {
            return null;
        }

        return Carbon::create($annee, $mois, $jour);
    }

    private function exporterMotsDePasse(): void
    {
        $chemin = $this->option('export-mdp')
            ?: storage_path('app/private/imports/mots-de-passe-'.now()->format('Y-m-d-His').'.csv');

        if (! is_dir(dirname($chemin))) {
            mkdir(dirname($chemin), 0755, true);
        }

        $handle = fopen($chemin, 'w');
        fputcsv($handle, ['matricule', 'nom', 'prenom', 'mot_de_passe']);

        foreach ($this->motsDePasse as $ligne) {
            fputcsv($handle, [$ligne['matricule'], $ligne['nom'], $ligne['prenom'], $ligne['mot_de_passe']]);
        }

        fclose($handle);

        $this->info('');
        $this->warn("Mots de passe exportés (fichier SENSIBLE, à supprimer après distribution) : {$chemin}");
    }

    private function afficherResume(bool $dryRun): void
    {
        $this->info('');
        $this->info($dryRun ? '=== Résumé (dry-run, rien n\'a été écrit) ===' : '=== Résumé de l\'import ===');
        $this->line("Tuteurs créés : {$this->tuteursCrees}");
        $this->line("Personnes à charge créées : {$this->personnesACreees} (dont {$this->personnesDecedees} marquées décédées → statut inactif)");
        $this->line("Lignes ignorées : {$this->lignesIgnorees}");
        $this->line("Villes non résolues : {$this->villesNonResolues}");
        $this->line("Dates d'adhésion invalides (ligne ignorée) : {$this->datesAdhesionInvalides}");
        $this->line("Délais de carence indicatifs non résolus : {$this->carencesNonResolues}");

        if ($this->erreurs !== []) {
            $this->info('');
            $this->warn('Anomalies journalisées :');
            foreach ($this->erreurs as $erreur) {
                $this->line(' - '.$erreur);
            }
        }
    }
}
