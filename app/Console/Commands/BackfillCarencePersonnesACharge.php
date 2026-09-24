<?php

namespace App\Console\Commands;

use App\Models\Adherent;
use App\Models\PersonneACharge;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Commande de correction ponctuelle : au moment de `adherents:importer-2026`,
 * l'annotation "carence" présente sur de nombreuses lignes de personnes à
 * charge (ex. "LOTCHO(1 an de carence)") était détectée mais jamais
 * enregistrée — la colonne nee_apres_1956 n'existait pas encore sur
 * personnes_a_charge. Cette commande rejoue le même fichier source avec la
 * même logique de regroupement bloc tuteur/personnes à charge, mais au lieu
 * de créer des enregistrements, retrouve ceux déjà en base (par ordre de
 * création, identique à l'ordre de lecture du fichier) et renseigne
 * nee_apres_1956 pour chaque personne à charge.
 *
 * Les matricules d'origine (MIACI-00001...) ont depuis été remplacés par la
 * nouvelle nomenclature (voir RenumeroterMatricules) : le rattachement se
 * fait donc par ordre d'identifiant, pas par matricule. Par sécurité, le nom
 * de chaque enregistrement retrouvé est comparé à celui de la ligne du
 * fichier ; au moindre écart, rien n'est écrit en base (dry-run ou pas).
 */
class BackfillCarencePersonnesACharge extends Command
{
    protected $signature = 'personnes-a-charge:backfill-carence
        {fichier : Chemin du fichier CSV source}
        {--dry-run : Analyse le fichier sans écrire en base}';

    protected $description = "Renseigne nee_apres_1956 sur les personnes à charge déjà importées, à partir de l'annotation \"carence\" du fichier source";

    /** @var array<int, string> */
    private array $anomalies = [];

    private int $tuteursMisAJour = 0;

    private int $personnesMisesAJour = 0;

    private int $personnesDejaCorrectes = 0;

    private int $personnesIntrouvables = 0;

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

        // Seuls les adhérents créés par l'import 2026 ont un compte
        // synthétique "{matricule}@miaci.local" : deux adhérents (comptes de
        // test créés par inscription en ligne, avant l'import) partagent la
        // même table mais ne font pas partie du fichier source — il faut les
        // exclure pour que l'ordre des identifiants corresponde exactement à
        // l'ordre des blocs du fichier.
        $adherents = Adherent::whereHas('user', fn ($q) => $q->where('email', 'like', '%@miaci.local'))
            ->orderBy('id')
            ->get();

        DB::beginTransaction();

        try {
            $this->traiter($lignes, $adherents);

            if ($this->anomalies !== []) {
                // Un seul nom qui ne correspond pas à ce qui était attendu
                // suffit à remettre en cause tout le rattachement par ordre
                // : on n'écrit rien plutôt que de risquer de corriger la
                // mauvaise personne.
                DB::rollBack();
                $this->afficherResume(true);
                $this->error('Anomalies détectées : aucune écriture effectuée. Corrigez ou examinez le fichier avant de relancer.');

                return self::FAILURE;
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Traitement interrompu : '.$e->getMessage());

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
            if (isset($ligne[0]) && is_numeric(trim($ligne[0]))) {
                $lignes[] = $ligne;
            }
        }

        fclose($handle);

        return $lignes;
    }

    /**
     * @param  array<int, array<int, string>>  $lignes
     * @param  Collection<int, Adherent>  $adherents
     */
    private function traiter(array $lignes, Collection $adherents): void
    {
        $ancreBloc = null;
        $indexTuteur = -1;
        $tuteurCourant = null;
        /** @var Collection<int, PersonneACharge>|null $dependantsDuBloc */
        $dependantsDuBloc = null;
        $indexDependant = 0;

        foreach ($lignes as $ligne) {
            $numeroOrdre = trim($ligne[0] ?? '');
            $nom = $this->corrigerEncodage($ligne[2] ?? '');
            $prenomsBrut = $this->corrigerEncodage($ligne[3] ?? '');
            $protegeBrut = $this->corrigerEncodage($ligne[4] ?? '');

            if (trim($nom) === '' && trim($prenomsBrut) === '') {
                continue;
            }

            $aUneAnnotationCarence = (bool) preg_match('/carence/iu', $prenomsBrut.' '.$protegeBrut);
            $neeApres1956 = ! $aUneAnnotationCarence;

            $prenoms = $this->nettoyerLibelle($prenomsBrut);
            $prenoms = trim((string) preg_replace('/\s*d[ée]c[ée]d[ée]e?\s*$/iu', '', $prenoms));
            $protege = $this->nettoyerLibelle($protegeBrut);

            $nouveauBloc = $ancreBloc === null || ! $this->memeBloc($protege, $ancreBloc);

            if ($nouveauBloc) {
                $ancreBloc = $protege;
                $indexTuteur++;
                $tuteurCourant = $adherents->get($indexTuteur);

                if ($tuteurCourant === null) {
                    $this->anomalies[] = "Ligne {$numeroOrdre} : plus aucun adhérent en base à cette position ({$indexTuteur}), fichier et base désynchronisés.";

                    continue;
                }

                if (! $this->nomsCorrespondent($tuteurCourant->nom, $tuteurCourant->prenom, $nom, $prenoms)) {
                    $this->anomalies[] = "Ligne {$numeroOrdre} : adhérent attendu \"{$nom} {$prenoms}\" mais trouvé \"{$tuteurCourant->nom} {$tuteurCourant->prenom}\" (matricule {$tuteurCourant->matricule}).";

                    continue;
                }

                if ($tuteurCourant->nee_apres_1956 !== $neeApres1956) {
                    $tuteurCourant->nee_apres_1956 = $neeApres1956;
                    $tuteurCourant->save();
                    $this->tuteursMisAJour++;
                }

                $dependantsDuBloc = PersonneACharge::where('adherent_id', $tuteurCourant->id)->orderBy('id')->get();
                $indexDependant = 0;

                continue;
            }

            if ($dependantsDuBloc === null) {
                continue;
            }

            $personne = $dependantsDuBloc->get($indexDependant);
            $indexDependant++;

            if ($personne === null) {
                $this->anomalies[] = "Ligne {$numeroOrdre} : aucune personne à charge en base à cette position pour le foyer \"{$ancreBloc}\".";
                $this->personnesIntrouvables++;

                continue;
            }

            if (! $this->nomsCorrespondent($personne->nom, $personne->prenom, $nom, $prenoms)) {
                $this->anomalies[] = "Ligne {$numeroOrdre} : personne à charge attendue \"{$nom} {$prenoms}\" mais trouvée \"{$personne->nom} {$personne->prenom}\" (foyer \"{$ancreBloc}\").";

                continue;
            }

            if ($personne->nee_apres_1956 === $neeApres1956) {
                $this->personnesDejaCorrectes++;

                continue;
            }

            $personne->nee_apres_1956 = $neeApres1956;
            $personne->save();
            $this->personnesMisesAJour++;
        }
    }

    private function nomsCorrespondent(string $nomAttendu, string $prenomAttendu, string $nom, string $prenoms): bool
    {
        return mb_strtolower(trim($nomAttendu)) === mb_strtolower(trim($nom))
            && mb_strtolower(trim($prenomAttendu)) === mb_strtolower(trim($prenoms));
    }

    private function corrigerEncodage(string $valeur): string
    {
        $valeur = trim($valeur);

        if ($valeur === '') {
            return $valeur;
        }

        $corrige = mb_convert_encoding($valeur, 'ISO-8859-1', 'UTF-8');

        return $corrige !== '' ? $corrige : $valeur;
    }

    private function nettoyerLibelle(string $valeur): string
    {
        $sansParentheses = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $valeur);

        return trim((string) preg_replace('/\s+/u', ' ', (string) $sansParentheses));
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

    private function afficherResume(bool $dryRun): void
    {
        $this->info('');
        $this->info($dryRun ? '=== Résumé (dry-run, rien n\'a été écrit) ===' : '=== Résumé ===');
        $this->line("Tuteurs corrigés : {$this->tuteursMisAJour}");
        $this->line("Personnes à charge corrigées : {$this->personnesMisesAJour}");
        $this->line("Personnes à charge déjà correctes : {$this->personnesDejaCorrectes}");
        $this->line("Personnes à charge introuvables : {$this->personnesIntrouvables}");

        if ($this->anomalies !== []) {
            $this->info('');
            $this->warn('Anomalies détectées :');
            foreach ($this->anomalies as $anomalie) {
                $this->line(' - '.$anomalie);
            }
        }
    }
}
