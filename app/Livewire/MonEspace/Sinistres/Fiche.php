<?php

namespace App\Livewire\MonEspace\Sinistres;

use App\Enums\StatutDemandeSinistre;
use App\Models\DemandeSinistre;
use App\Models\PieceJustificative;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Fiche extends Component
{
    use WithFileUploads;

    public DemandeSinistre $demande;

    public $fichierComplement = null;

    public string $libelleComplement = '';

    public function mount(DemandeSinistre $demande): void
    {
        $this->authorize('view', $demande);

        $this->demande = $demande;
    }

    public function ajouterComplement(): void
    {
        $this->authorize('completer', $this->demande);

        $this->validate([
            'fichierComplement' => ['required', 'file', 'max:10240'],
            'libelleComplement' => ['required', 'string', 'max:255'],
        ]);

        $chemin = $this->fichierComplement->store('sinistres/'.$this->demande->id, 'local');

        PieceJustificative::create([
            'justificable_type' => DemandeSinistre::class,
            'justificable_id' => $this->demande->id,
            'fichier_path' => $chemin,
            'nom_original' => $this->libelleComplement.' — '.$this->fichierComplement->getClientOriginalName(),
            'type_mime' => $this->fichierComplement->getMimeType(),
            'taille' => $this->fichierComplement->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        $this->demande->update(['statut' => StatutDemandeSinistre::Soumise]);

        $this->reset(['fichierComplement', 'libelleComplement']);

        session()->flash('status', 'Pièce complémentaire ajoutée. Votre demande repasse en cours d\'examen.');
    }

    public function render(): View
    {
        return view('livewire.mon-espace.sinistres.fiche', [
            'pieces' => $this->demande->piecesJustificatives()->get(),
        ]);
    }
}
