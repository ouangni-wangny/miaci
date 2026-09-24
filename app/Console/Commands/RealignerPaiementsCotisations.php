<?php

namespace App\Console\Commands;

use App\Enums\StatutCotisation;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Services\CotisationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Corrige, pour chaque foyer, les irrégularités héritées de la grille
 * mensuelle importée (ou d'une saisie manuelle) où un mois plus récent
 * apparaît payé alors qu'un mois plus ancien ne l'est pas — ce que le
 * règlement ne permet pas : un paiement doit toujours combler les mois les
 * plus anciens en premier.
 *
 * Ne touche qu'aux foyers réellement irréguliers (détectés automatiquement,
 * jamais devinés). Pour chacun, reconstitue son historique de paiements
 * (même montant total, mêmes dates de paiement d'origine préservées) en le
 * réappliquant mois par mois depuis le plus ancien — exactement la même
 * logique que CotisationService::allouerPaiement(), rejouée sur l'historique
 * existant plutôt que sur un nouveau paiement.
 */
class RealignerPaiementsCotisations extends Command
{
    protected $signature = 'cotisations:realigner {--dry-run : Analyse sans écrire en base}';

    protected $description = "Réaligne les paiements de cotisation déjà enregistrés pour combler les mois les plus anciens en premier";

    private int $foyersIrreguliers = 0;

    private int $cotisationsAvant = 0;

    private int $cotisationsApres = 0;

    public function handle(CotisationService $cotisationService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();

        try {
            foreach (Adherent::orderBy('id')->get() as $adherent) {
                $this->traiterAdherent($adherent, $cotisationService);
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Réalignement interrompu : '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('');
        $this->info($dryRun ? '=== Résumé (dry-run, rien n\'a été écrit) ===' : '=== Résumé ===');
        $this->line("Foyers irréguliers corrigés : {$this->foyersIrreguliers}");
        $this->line("Cotisations reconstruites : {$this->cotisationsAvant} → {$this->cotisationsApres} lignes (même montant total)");

        return self::SUCCESS;
    }

    private function traiterAdherent(Adherent $adherent, CotisationService $cotisationService): void
    {
        $releves = array_reverse($cotisationService->relevesPeriodes($adherent)); // plus ancien -> plus récent

        if (! $this->estIrregulier($releves)) {
            return;
        }

        $paiementsOriginaux = $adherent->cotisations()->valides()->orderBy('date_paiement')->orderBy('id')->get();
        $montantTotal = (int) $paiementsOriginaux->sum('montant');

        $nouvellesLignes = $this->reallouer($releves, $paiementsOriginaux);
        $montantReconstruit = array_sum(array_column($nouvellesLignes, 'montant'));

        if ($montantReconstruit !== $montantTotal) {
            // Garde-fou : ne jamais faire disparaître ou apparaître de
            // l'argent. En cas d'écart (ne devrait pas arriver), on
            // n'écrit rien pour ce foyer plutôt que de risquer une
            // incohérence financière.
            $this->warn("  ! {$adherent->matricule} : écart de montant détecté ({$montantReconstruit} reconstruit vs {$montantTotal} d'origine), foyer ignoré par sécurité.");

            return;
        }

        $this->line("  {$adherent->matricule} — {$adherent->nomComplet()} : {$paiementsOriginaux->count()} ligne(s) → {$this->description($nouvellesLignes)}");

        $this->foyersIrreguliers++;
        $this->cotisationsAvant += $paiementsOriginaux->count();
        $this->cotisationsApres += count($nouvellesLignes);

        foreach ($paiementsOriginaux as $ligne) {
            $ligne->delete();
        }

        foreach ($nouvellesLignes as $ligne) {
            $adherent->cotisations()->create($ligne);
        }
    }

    /**
     * @param  array<int, array{debut: \Carbon\Carbon, fin: \Carbon\Carbon, du: int, paye: int}>  $releves
     */
    private function estIrregulier(array $releves): bool
    {
        foreach ($releves as $i => $periode) {
            if ($periode['paye'] >= $periode['du']) {
                continue;
            }

            foreach (array_slice($releves, $i + 1) as $plusRecent) {
                if ($plusRecent['paye'] > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Rejoue chaque paiement d'origine (dans l'ordre où il a été versé) sur
     * les périodes dues (de la plus ancienne à la plus récente), en
     * conservant la date de paiement, le mode et la référence d'origine de
     * chaque versement — seule la période à laquelle il est rattaché change.
     *
     * @param  array<int, array{debut: \Carbon\Carbon, fin: \Carbon\Carbon, du: int}>  $releves
     * @param  \Illuminate\Support\Collection<int, Cotisation>  $paiements
     * @return array<int, array<string, mixed>>
     */
    private function reallouer(array $releves, $paiements): array
    {
        $nouvelles = [];
        $indexPeriode = 0;
        $restantPeriode = $releves[0]['du'] ?? 0;

        foreach ($paiements as $paiement) {
            $restantPaiement = $paiement->montant;

            while ($restantPaiement > 0 && $indexPeriode < count($releves)) {
                if ($restantPeriode <= 0) {
                    $indexPeriode++;
                    $restantPeriode = $releves[$indexPeriode]['du'] ?? 0;

                    continue;
                }

                $alloue = min($restantPaiement, $restantPeriode);

                $nouvelles[] = [
                    'montant' => $alloue,
                    'date_paiement' => $paiement->date_paiement,
                    'periode_debut' => $releves[$indexPeriode]['debut'],
                    'periode_fin' => $releves[$indexPeriode]['fin'],
                    'mode_paiement' => $paiement->mode_paiement,
                    'reference' => $paiement->reference,
                    'statut' => StatutCotisation::Valide,
                    'enregistre_par' => $paiement->enregistre_par,
                    'transaction_paiement_id' => $paiement->transaction_paiement_id,
                ];

                $restantPaiement -= $alloue;
                $restantPeriode -= $alloue;
            }

            // Plus aucune période due (adhérent déjà à jour au-delà du
            // relevé) : le reliquat est crédité sur la dernière période
            // plutôt que d'être perdu.
            if ($restantPaiement > 0 && $releves !== []) {
                $derniere = $releves[count($releves) - 1];
                $nouvelles[] = [
                    'montant' => $restantPaiement,
                    'date_paiement' => $paiement->date_paiement,
                    'periode_debut' => $derniere['debut'],
                    'periode_fin' => $derniere['fin'],
                    'mode_paiement' => $paiement->mode_paiement,
                    'reference' => $paiement->reference,
                    'statut' => StatutCotisation::Valide,
                    'enregistre_par' => $paiement->enregistre_par,
                    'transaction_paiement_id' => $paiement->transaction_paiement_id,
                ];
            }
        }

        return $nouvelles;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nouvellesLignes
     */
    private function description(array $nouvellesLignes): string
    {
        $periodes = collect($nouvellesLignes)
            ->map(fn (array $l) => $l['periode_debut']->format('M Y'))
            ->unique()
            ->implode(', ');

        return count($nouvellesLignes)." ligne(s) sur : {$periodes}";
    }
}
