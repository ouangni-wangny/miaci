<?php

namespace App\Livewire\Gestion\Sinistres;

use App\Models\DemandeSinistre;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Page « Demandes de sinistre » ; la liste est portée par DemandesTable. */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', DemandeSinistre::class);
    }

    public function render(): View
    {
        return view('livewire.gestion.sinistres.index');
    }
}
