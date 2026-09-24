<?php

namespace App\Livewire\Gestion\Parametres\TypesSinistre;

use App\Models\TypeSinistre;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Page « Types de sinistre » ; la liste est portée par TypesTable. */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', TypeSinistre::class);
    }

    public function render(): View
    {
        return view('livewire.gestion.parametres.types-sinistre.index');
    }
}
