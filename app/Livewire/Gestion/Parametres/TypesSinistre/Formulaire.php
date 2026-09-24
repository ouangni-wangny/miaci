<?php

namespace App\Livewire\Gestion\Parametres\TypesSinistre;

use App\Models\TypeSinistre;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Formulaire extends Component
{
    public ?TypeSinistre $typeSinistre = null;

    public string $code = '';

    public string $libelle = '';

    public string $description = '';

    public string $plafond_montant = '';

    public string $delai_carence_mois = '0';

    public bool $cotisation_a_jour_requise = true;

    public bool $actif = true;

    /** @var array<int, array{id: ?int, libelle: string, obligatoire: bool}> */
    public array $pieces = [];

    public function mount(?TypeSinistre $typeSinistre = null): void
    {
        if ($typeSinistre?->exists) {
            $this->authorize('update', $typeSinistre);

            $this->typeSinistre = $typeSinistre;
            $this->code = $typeSinistre->code;
            $this->libelle = $typeSinistre->libelle;
            $this->description = (string) $typeSinistre->description;
            $this->plafond_montant = (string) $typeSinistre->plafond_montant;
            $this->delai_carence_mois = (string) $typeSinistre->delai_carence_mois;
            $this->cotisation_a_jour_requise = $typeSinistre->cotisation_a_jour_requise;
            $this->actif = $typeSinistre->actif;
            $this->pieces = $typeSinistre->piecesRequises()->get()
                ->map(fn ($p) => ['id' => $p->id, 'libelle' => $p->libelle, 'obligatoire' => $p->obligatoire])
                ->all();
        } else {
            $this->authorize('create', TypeSinistre::class);
        }

        if ($this->pieces === []) {
            $this->ajouterPiece();
        }
    }

    public function ajouterPiece(): void
    {
        $this->pieces[] = ['id' => null, 'libelle' => '', 'obligatoire' => true];
    }

    public function retirerPiece(int $index): void
    {
        unset($this->pieces[$index]);
        $this->pieces = array_values($this->pieces);
    }

    protected function rules(): array
    {
        $typeSinistreId = $this->typeSinistre?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('types_sinistre', 'code')->ignore($typeSinistreId)],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'plafond_montant' => ['required', 'integer', 'min:0'],
            'delai_carence_mois' => ['required', 'integer', 'min:0'],
            'cotisation_a_jour_requise' => ['boolean'],
            'actif' => ['boolean'],
            'pieces.*.libelle' => ['required', 'string', 'max:255'],
            'pieces.*.obligatoire' => ['boolean'],
        ];
    }

    public function enregistrer(): void
    {
        $valides = $this->validate();

        $donneesType = collect($valides)->only([
            'code', 'libelle', 'description', 'plafond_montant', 'delai_carence_mois',
            'cotisation_a_jour_requise', 'actif',
        ])->all();

        $type = $this->typeSinistre
            ? tap($this->typeSinistre)->update($donneesType)
            : TypeSinistre::create($donneesType);

        $idsConserves = [];
        foreach ($valides['pieces'] as $piece) {
            $ligne = $type->piecesRequises()->updateOrCreate(
                ['id' => $piece['id'] ?? null],
                ['libelle' => $piece['libelle'], 'obligatoire' => (bool) ($piece['obligatoire'] ?? false)]
            );
            $idsConserves[] = $ligne->id;
        }
        $type->piecesRequises()->whereNotIn('id', $idsConserves)->delete();

        session()->flash('status', 'Type de sinistre enregistré.');

        $this->redirect(route('gestion.parametres.types-sinistre.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.gestion.parametres.types-sinistre.formulaire');
    }
}
