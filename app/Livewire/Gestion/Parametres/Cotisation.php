<?php

namespace App\Livewire\Gestion\Parametres;

use App\Enums\FrequenceCotisation;
use App\Models\ParametreCotisation;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Cotisation extends Component
{
    public string $montant = '';

    public string $droit_adhesion = '';

    public string $frequence = 'mensuelle';

    public string $date_debut = '';

    public ?int $parametreEnEditionId = null;

    public function mount(): void
    {
        $this->authorize('configurerParametres', \App\Models\Cotisation::class);

        $actuel = ParametreCotisation::actuel();

        $this->date_debut = now()->format('Y-m-d');
        $this->droit_adhesion = $actuel?->droit_adhesion !== null ? (string) $actuel->droit_adhesion : '';
    }

    public function modifier(int $id): void
    {
        $this->authorize('configurerParametres', \App\Models\Cotisation::class);

        $parametre = ParametreCotisation::findOrFail($id);

        $this->parametreEnEditionId = $parametre->id;
        $this->montant = (string) $parametre->montant;
        $this->droit_adhesion = $parametre->droit_adhesion !== null ? (string) $parametre->droit_adhesion : '';
        $this->frequence = $parametre->frequence->value;
        $this->date_debut = $parametre->date_debut->format('Y-m-d');
    }

    public function annulerModification(): void
    {
        $this->reset(['montant', 'droit_adhesion', 'frequence', 'date_debut', 'parametreEnEditionId']);
        $this->frequence = 'mensuelle';
        $this->date_debut = now()->format('Y-m-d');
    }

    public function enregistrer(): void
    {
        $this->authorize('configurerParametres', \App\Models\Cotisation::class);

        $valides = $this->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'droit_adhesion' => ['required', 'integer', 'min:0'],
            'frequence' => ['required', 'in:mensuelle,trimestrielle,annuelle'],
            'date_debut' => ['required', 'date'],
        ]);

        if ($this->parametreEnEditionId) {
            ParametreCotisation::findOrFail($this->parametreEnEditionId)->update($valides);
            ParametreCotisation::oublierCacheActuel();

            session()->flash('status', 'Paramètre de cotisation modifié.');
            $this->annulerModification();

            return;
        }

        ParametreCotisation::where('actif', true)->update(['actif' => false]);

        ParametreCotisation::create([
            ...$valides,
            'actif' => true,
            'created_by' => auth()->id(),
        ]);

        ParametreCotisation::oublierCacheActuel();

        session()->flash('status', 'Paramètre de cotisation enregistré.');
    }

    public function supprimer(int $id): void
    {
        $this->authorize('configurerParametres', \App\Models\Cotisation::class);

        if (ParametreCotisation::count() <= 1) {
            session()->flash('erreur', 'Impossible de supprimer : il doit rester au moins un paramètre de cotisation.');

            return;
        }

        $parametre = ParametreCotisation::findOrFail($id);
        $etaitActif = $parametre->actif;
        $parametre->delete();

        if ($etaitActif) {
            $suivant = ParametreCotisation::orderByDesc('date_debut')->first();
            $suivant?->update(['actif' => true]);
        }

        ParametreCotisation::oublierCacheActuel();

        if ($this->parametreEnEditionId === $id) {
            $this->annulerModification();
        }

        session()->flash('status', 'Paramètre de cotisation supprimé.');
    }

    public function render(): View
    {
        return view('livewire.gestion.parametres.cotisation', [
            'parametreActuel' => ParametreCotisation::actuel(),
            'historique' => ParametreCotisation::orderByDesc('date_debut')->get(),
            'frequences' => FrequenceCotisation::cases(),
        ]);
    }
}
