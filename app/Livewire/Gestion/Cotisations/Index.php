<?php

namespace App\Livewire\Gestion\Cotisations;

use App\Models\Cotisation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Page « Cotisations » : total collecté ce mois-ci et accès au paramétrage.
 * Les soldes par adhérent sont portés par SoldesTable.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Cotisation::class);
    }

    public function render(): View
    {
        $totalCollecteMois = Cotisation::valides()
            ->whereBetween('date_paiement', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('montant');

        return view('livewire.gestion.cotisations.index', [
            'totalCollecteMois' => $totalCollecteMois,
        ]);
    }
}
