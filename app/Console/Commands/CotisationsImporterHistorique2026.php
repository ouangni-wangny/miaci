<?php

namespace App\Console\Commands;

use App\Enums\StatutCotisation;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\PersonneACharge;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Importe l'historique des paiements de cotisation 2026 depuis la grille
 * mensuelle du fichier source (colonnes DÉCÈS de janvier à décembre 2026),
 * en les rattachant aux adhérents/personnes à charge déjà créés par
 * `adherents:importer-2026`. Chaque paiement (tuteur ou personne à charge)
 * est enregistré sur le grand livre du tuteur, comme le fait déjà le reste
 * de l'application (les cotisations sont toujours suivies au niveau du
 * foyer/adhérent, jamais par personne à charge individuellement).
 *
 * Rejoue exactement la même logique de regroupement bloc tuteur/personnes à
 * charge que la commande d'import initiale, afin de retrouver les bons
 * enregistrements déjà en base (même ordre de lecture, mêmes lignes
 * ignorées) — voir ImporterAdherents2026 pour le détail des règles.
 */
class CotisationsImporterHistorique2026 extends Command
{
    protected $signature = 'cotisations:importer-historique-2026
        {fichier : Chemin du fichier CSV source}
        {--dry-run : Analyse le fichier sans écrire en base}';

    protected $description = "Importe l'historique des paiements de cotisation 2026 (grille mensuelle) pour les adhérents déjà importés";

    /**
     * Position de la colonne DÉCÈS (montant payé) pour chaque mois de 2026.
     *
     * @var array<int, int>
     */
    private const MOIS_INDEX_DECES = [
        1 => 7, 2 => 11, 3 => 15, 4 => 19, 5 => 23, 6 => 27,
        7 => 31, 8 => 35, 9 => 39, 10 => 43, 11 => 47, 12 => 51,
    ];

    private int $compteurMatricule = 0;

    /** @var array<int, string> */
    private array $erreurs = [];

    private int $cotisationsCreees = 0;

    private int $montantTotal = 0;

    private int $tuteursIntrouvables = 0;

    private int $personnesIntrouvables = 0;

    private int $montantsIllisibles = 0;

    private int $lignesSansTuteur = 0;

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
        /** @var Collection<int, PersonneACharge>|null $dependantsDuBloc */
        $dependantsDuBloc = null;
        $indexDependant = 0;

        foreach ($lignes as $ligne) {
            $numeroOrdre = trim($ligne[0] ?? '');
            $nomBrut = $this->corrigerEncodage($ligne[2] ?? '');
            $prenomsBrut = $this->corrigerEncodage($ligne[3] ?? '');
            $protegeBrut = $this->corrigerEncodage($ligne[4] ?? '');

            if (trim($nomBrut) === '' && trim($prenomsBrut) === '') {
                // Même ligne (nom vide) ignorée lors de l'import initial des
                // fiches : aucun enregistrement n'existe pour y rattacher un
                // paiement.
                $this->erreurs[] = "Ligne {$numeroOrdre} : nom vide (déjà ignorée à l'import des fiches), paiements éventuels non importés.";

                continue;
            }

            $protege = $this->nettoyerLibelle($protegeBrut);
            $nouveauBloc = $ancreBloc === null || ! $this->memeBloc($protege, $ancreBloc);

            if ($nouveauBloc) {
                $ancreBloc = $protege;
                $this->compteurMatricule++;
                $matricule = sprintf('MIACI-%05d', $this->compteurMatricule);
                $tuteurCourant = Adherent::where('matricule', $matricule)->first();

                if ($tuteurCourant === null) {
                    $this->erreurs[] = "Ligne {$numeroOrdre} : adhérent {$matricule} introuvable, paiements ignorés.";
                    $this->tuteursIntrouvables++;
                }

                $dependantsDuBloc = $tuteurCourant?->personnesACharge()->orderBy('id')->get();
                $indexDependant = 0;

                $this->importerPaiementsDeLaLigne($ligne, $tuteurCourant, $numeroOrdre);

                continue;
            }

            $personne = $dependantsDuBloc?->get($indexDependant);
            $indexDependant++;

            if ($personne === null) {
                $this->erreurs[] = "Ligne {$numeroOrdre} : personne à charge introuvable pour le foyer \"{$ancreBloc}\", paiements ignorés.";
                $this->personnesIntrouvables++;

                continue;
            }

            // Les paiements d'une personne à charge sont comptabilisés sur
            // le grand livre de son tuteur (aucune table cotisations propre
            // aux personnes à charge dans le schéma applicatif).
            $this->importerPaiementsDeLaLigne($ligne, $tuteurCourant, $numeroOrdre);
        }
    }

    /**
     * @param  array<int, string>  $ligne
     */
    private function importerPaiementsDeLaLigne(array $ligne, ?Adherent $tuteur, string $numeroOrdre): void
    {
        if ($tuteur === null) {
            $this->lignesSansTuteur++;

            return;
        }

        foreach (self::MOIS_INDEX_DECES as $mois => $index) {
            $brut = trim($ligne[$index] ?? '');

            if ($brut === '') {
                continue;
            }

            $nettoye = preg_replace('/[^\d]/', '', $brut);

            if ($nettoye === '' || (int) $nettoye <= 0) {
                $this->erreurs[] = "Ligne {$numeroOrdre}, mois {$mois}/2026 : montant illisible (\"{$brut}\"), ignoré.";
                $this->montantsIllisibles++;

                continue;
            }

            $montant = (int) $nettoye;
            $debut = Carbon::create(2026, $mois, 1);
            $fin = $debut->copy()->endOfMonth();

            Cotisation::create([
                'adherent_id' => $tuteur->id,
                'montant' => $montant,
                'date_paiement' => $debut->copy(),
                'periode_debut' => $debut,
                'periode_fin' => $fin,
                'mode_paiement' => 'autre',
                'reference' => 'Import historique 2026',
                'statut' => StatutCotisation::Valide,
            ]);

            $this->cotisationsCreees++;
            $this->montantTotal += $montant;
        }
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
        $this->info($dryRun ? '=== Résumé (dry-run, rien n\'a été écrit) ===' : '=== Résumé de l\'import ===');
        $this->line("Cotisations créées : {$this->cotisationsCreees}");
        $this->line('Montant total importé : '.number_format($this->montantTotal, 0, ',', ' ').' FCFA');
        $this->line("Tuteurs introuvables : {$this->tuteursIntrouvables}");
        $this->line("Personnes à charge introuvables : {$this->personnesIntrouvables}");
        $this->line("Montants de cellule illisibles (ignorés) : {$this->montantsIllisibles}");

        if ($this->erreurs !== []) {
            $this->info('');
            $this->warn('Anomalies journalisées :');
            foreach ($this->erreurs as $erreur) {
                $this->line(' - '.$erreur);
            }
        }
    }
}
