<?php

namespace App\Livewire\MonEspace;

use App\Models\Adherent;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge as PersonneAChargeModel;
use App\Services\CotisationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PersonnesACharge extends Component
{
    public Adherent $adherent;

    public function mount(): void
    {
        $adherent = auth()->user()->adherent;

        abort_unless($adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");

        $this->adherent = $adherent;
    }

    public function render(CotisationService $cotisationService): View
    {
        $personnes = $this->adherent->personnesACharge()->orderBy('nom')->get();

        return view('livewire.mon-espace.personnes-a-charge', [
            'personnes' => $personnes,
            'montantDroitAdhesion' => ParametreCotisation::actuel()?->droit_adhesion,
            // Délai de carence propre à chaque personne à charge (base selon
            // son âge + pénalité de retard du foyer, sauf date de fin fixée
            // manuellement par un gestionnaire).
            'carencePersonnes' => $personnes->mapWithKeys(fn (PersonneAChargeModel $personne) => [
                $personne->id => [
                    'delai' => $cotisationService->delaiCarenceMinimum($this->adherent, $personne),
                    'dateFin' => $cotisationService->dateFinCarence($this->adherent, $personne),
                ],
            ]),
        ]);
    }
}
