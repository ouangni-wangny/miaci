<?php

namespace App\Livewire\MonEspace;

use App\Enums\Mois;
use App\Enums\StatutAdherent;
use App\Enums\StatutTransaction;
use App\Services\CotisationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Cotisations extends Component
{
    public string $anneeFiltre = '';

    public string $moisFiltre = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");
    }

    public function render(CotisationService $cotisationService): View
    {
        $adherent = auth()->user()->adherent;

        $cotisations = $adherent->cotisations()
            ->when($this->anneeFiltre, fn ($q) => $q->whereYear('date_paiement', $this->anneeFiltre))
            ->when($this->moisFiltre, fn ($q) => $q->whereMonth('date_paiement', $this->moisFiltre))
            ->orderByDesc('date_paiement')
            ->get();

        $anneesDisponibles = $adherent->cotisations()
            ->pluck('date_paiement')
            ->map(fn ($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('livewire.mon-espace.cotisations', [
            'peutPayerEnLigne' => $adherent->statut === StatutAdherent::Actif,
            'adherent' => $adherent,
            'personnesACharge' => $adherent->personnesACharge()->orderBy('nom')->get(),
            'solde' => $cotisationService->calculerSolde($adherent),
            // À jour = aucun mois échu impayé (hors mois en cours), voir
            // CotisationService::estAJour() — distinct du solde cumulé, qui
            // peut rester positif (mois en cours pas encore payé) même sans
            // arriéré réel.
            'estAJour' => $cotisationService->estAJour($adherent),
            'releves' => $cotisationService->relevesPeriodes($adherent),
            'cotisations' => $cotisations,
            'anneesDisponibles' => $anneesDisponibles,
            'moisListe' => Mois::cases(),
            'transactionsEnCours' => $adherent->transactionsPaiement()
                ->where('statut', StatutTransaction::EnAttente)
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
