<?php

namespace App\Livewire\MonEspace\Sinistres;

use App\Enums\StatutAdherent;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");
    }

    public function render(): View
    {
        $adherent = auth()->user()->adherent;

        return view('livewire.mon-espace.sinistres.index', [
            'demandes' => $adherent->demandesSinistre()
                ->with('typeSinistre')
                ->orderByDesc('created_at')
                ->get(),
            'peutSoumettre' => $adherent->statut === StatutAdherent::Actif,
        ]);
    }
}
