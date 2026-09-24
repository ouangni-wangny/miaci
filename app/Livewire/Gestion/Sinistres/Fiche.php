<?php

namespace App\Livewire\Gestion\Sinistres;

use App\Enums\StatutDemandeSinistre;
use App\Models\DemandeSinistre;
use App\Notifications\StatutDemandeSinistreModifie;
use App\Services\AuditLogger;
use App\Services\CotisationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Fiche extends Component
{
    public DemandeSinistre $demande;

    public string $montant_accorde = '';

    public string $motif_decision = '';

    public function mount(DemandeSinistre $demande, CotisationService $cotisationService): void
    {
        $this->authorize('view', $demande);

        $this->demande = $demande;

        $deduction = $this->deductionCotisationMoisEnCours($demande, $cotisationService);
        $this->montant_accorde = (string) ($demande->montant_accorde ?? max(0, $demande->montant_demande - $deduction));
    }

    /**
     * Article 7 : « Avant l'assistance décès, la Direction de la MIACI se
     * charge de soustraire les cotisations du mois en cours du
     * bénéficiaire. » Calcule ce montant pour le pré-remplir dans le
     * montant accordé — le gestionnaire reste libre de l'ajuster avant de
     * valider la décision.
     */
    private function deductionCotisationMoisEnCours(DemandeSinistre $demande, CotisationService $cotisationService): int
    {
        if ($demande->typeSinistre->code !== 'DECES') {
            return 0;
        }

        $periodeEnCours = $cotisationService->relevesPeriodes($demande->adherent)[0] ?? null;

        if (! $periodeEnCours) {
            return 0;
        }

        return max(0, $periodeEnCours['du'] - $periodeEnCours['paye']);
    }

    /**
     * Article 7 : « Le bureau part en congé en décembre donc toutes les
     * assistances de décembre se font en janvier. » Les journées de
     * décembre ne comptent donc pas dans le délai de traitement de 2
     * semaines (14 jours) : une demande soumise fin novembre ou en décembre
     * voit son échéance glisser d'autant en janvier plutôt que de
     * s'afficher (à tort) comme en retard pendant la fermeture du bureau.
     */
    private function dateLimiteTraitement(Carbon $depuisSoumission): Carbon
    {
        $curseur = $depuisSoumission->copy();
        $joursRestants = 14;

        while ($joursRestants > 0) {
            $curseur->addDay();

            if ($curseur->month !== 12) {
                $joursRestants--;
            }
        }

        return $curseur;
    }

    public function prendreEnCharge(): void
    {
        $this->authorize('decider', $this->demande);

        $this->demande->update(['statut' => StatutDemandeSinistre::EnCoursExamen]);
        session()->flash('status', 'Statut mis à jour.');
    }

    public function approuver(AuditLogger $audit): void
    {
        $this->authorize('decider', $this->demande);

        $valides = $this->validate([
            'montant_accorde' => ['required', 'integer', 'min:1'],
            'motif_decision' => ['nullable', 'string', 'max:2000'],
        ]);

        $plafond = min($this->demande->montant_demande, $this->demande->typeSinistre->plafond_montant);

        if ($valides['montant_accorde'] > $plafond) {
            $this->addError('montant_accorde', "Le montant accordé ne peut pas dépasser {$plafond} FCFA (montant demandé et plafond du type de sinistre).");

            return;
        }

        $statutAvant = $this->demande->statut->value;

        $this->demande->update([
            'statut' => StatutDemandeSinistre::Approuvee,
            'montant_accorde' => $valides['montant_accorde'],
            'motif_decision' => $valides['motif_decision'],
            'traite_par' => auth()->id(),
            'traite_le' => now(),
        ]);

        $audit->log(
            'sinistre.approuve',
            $this->demande,
            ['statut' => $statutAvant],
            ['statut' => 'approuvee', 'montant_accorde' => $valides['montant_accorde']],
        );

        $this->notifierAdherent();
        session()->flash('status', 'Demande approuvée.');
    }

    public function rejeter(AuditLogger $audit): void
    {
        $this->authorize('decider', $this->demande);

        $valides = $this->validate([
            'motif_decision' => ['required', 'string', 'max:2000'],
        ]);

        $statutAvant = $this->demande->statut->value;

        $this->demande->update([
            'statut' => StatutDemandeSinistre::Rejetee,
            'montant_accorde' => null,
            'motif_decision' => $valides['motif_decision'],
            'traite_par' => auth()->id(),
            'traite_le' => now(),
        ]);

        $audit->log(
            'sinistre.rejete',
            $this->demande,
            ['statut' => $statutAvant],
            ['statut' => 'rejetee', 'motif' => $valides['motif_decision']],
        );

        $this->notifierAdherent();
        session()->flash('status', 'Demande rejetée.');
    }

    public function demanderComplement(AuditLogger $audit): void
    {
        $this->authorize('decider', $this->demande);

        $valides = $this->validate([
            'motif_decision' => ['required', 'string', 'max:2000'],
        ]);

        $statutAvant = $this->demande->statut->value;

        $this->demande->update([
            'statut' => StatutDemandeSinistre::ComplementDemande,
            'motif_decision' => $valides['motif_decision'],
            'traite_par' => auth()->id(),
            'traite_le' => now(),
        ]);

        $audit->log(
            'sinistre.complement_demande',
            $this->demande,
            ['statut' => $statutAvant],
            ['statut' => 'complement_demande', 'motif' => $valides['motif_decision']],
        );

        $this->notifierAdherent();
        session()->flash('status', 'Complément demandé à l\'adhérent.');
    }

    private function notifierAdherent(): void
    {
        $this->demande->refresh();

        if ($user = $this->demande->adherent->user) {
            $user->notify(new StatutDemandeSinistreModifie($this->demande));
        }
    }

    public function render(CotisationService $cotisationService): View
    {
        $estDeces = $this->demande->typeSinistre->code === 'DECES';

        return view('livewire.gestion.sinistres.fiche', [
            'pieces' => $this->demande->piecesJustificatives()->with('pieceRequise')->get(),
            'deductionCotisation' => $this->deductionCotisationMoisEnCours($this->demande, $cotisationService),
            // Article 7 : 3 semaines pour fournir les documents de décès,
            // 2 semaines de délai de traitement une fois la demande soumise
            // (pièces jointes dès la soumission dans ce parcours). Purement
            // informatif : n'empêche jamais le traitement, sert de repère.
            'dateLimiteDocuments' => $estDeces ? $this->demande->date_evenement->copy()->addDays(21) : null,
            'dateLimiteTraitement' => $estDeces && ! $this->demande->statut->estFinale()
                ? $this->dateLimiteTraitement($this->demande->created_at)
                : null,
        ]);
    }
}
