<?php

namespace App\Livewire\Gestion\DonsFinAnnee;

use App\Models\Cotisation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Don de fin d'année (Article 7 du règlement) : la page porte l'année
 * considérée ; les adhérents éligibles et le versement sont portés par
 * DonsTable (voir ses règles d'éligibilité).
 */
#[Layout('layouts.app')]
class Index extends Component
{
    private const MONTANT = 10000;

    #[Url(history: true)]
    public string $annee = '';

    public function mount(): void
    {
        $this->authorize('create', Cotisation::class);

        $this->annee = (string) now()->year;
    }

    public function render(): View
    {
        return view('livewire.gestion.dons-fin-annee.index', [
            'montant' => self::MONTANT,
        ]);
    }
}
