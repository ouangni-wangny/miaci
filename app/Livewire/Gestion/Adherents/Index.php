<?php

namespace App\Livewire\Gestion\Adherents;

use App\Models\Adherent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Page « Adhérents tuteurs » : en-tête et actions globales. La liste
 * (recherche, filtres, tri, pagination, suppression) est portée par
 * AdherentsTable.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Adherent::class);
    }

    public function render(): View
    {
        return view('livewire.gestion.adherents.index');
    }
}
