<?php

namespace App\Livewire\Gestion\Parametres\TypesSinistre;

use App\Livewire\Tables\DataTable;
use App\Models\TypeSinistre;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class TypesTable extends DataTable
{
    protected string $placeholderRecherche = 'Libellé, code…';

    protected string $messageVide = 'Aucun type de sinistre configuré.';

    /** Paramétrage, pas des données à sortir : pas de sélection ni d'export. */
    protected bool $exportable = false;

    protected function autoriser(): void
    {
        // La route est déjà réservée à l'ADMIN (routes/web.php) alors que la
        // policy viewAny admet aussi les gestionnaires : la table applique la
        // règle la plus stricte pour ne pas dépendre de la seule route.
        abort_unless(auth()->user()?->hasRole('ADMIN'), 403);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('libelle', 'asc');
    }

    public function builder(): Builder
    {
        return TypeSinistre::query()->withCount('demandes');
    }

    public function columns(): array
    {
        return [
            Column::make('Libellé', 'libelle')
                ->format(fn ($valeur) => '<span class="font-medium text-gray-900">'.e($valeur).'</span>')
                ->html()
                ->sortable()
                ->searchable(),

            Column::make('Code', 'code')
                ->sortable()
                ->searchable()
                ->deselected(),

            Column::make('Plafond', 'plafond_montant')
                ->format(fn ($valeur) => $this->fcfa($valeur))
                ->sortable(),

            Column::make('Carence', 'delai_carence_mois')
                ->format(fn ($valeur) => "{$valeur} mois")
                ->sortable(),

            Column::make('Cotisation à jour', 'cotisation_a_jour_requise')
                ->format(fn ($valeur) => $valeur ? 'Oui' : 'Non')
                ->sortable()
                ->collapseOnTablet(),

            // withCount() : « demandes_count » est un alias calculé, pas une
            // colonne de la table — on l'affiche et on le trie explicitement.
            Column::make('Demandes')
                ->label(fn (TypeSinistre $type) => $type->demandes_count)
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy('demandes_count', $sens)),

            Column::make('Statut', 'actif')
                ->label(fn (TypeSinistre $type) => view('livewire.gestion.parametres.types-sinistre.cellule-statut', ['type' => $type])->render())
                ->html()
                ->sortable(),

            $this->colonneActions(fn (TypeSinistre $type) => [
                ['libelle' => 'Modifier', 'icone' => 'pencil-square', 'url' => route('gestion.parametres.types-sinistre.modifier', $type)],
            ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'actif')
                ->options(['' => 'Tous', '1' => 'Actifs', '0' => 'Inactifs'])
                ->filter(fn (Builder $q, string $valeur) => $q->where('actif', $valeur === '1')),

            SelectFilter::make('Cotisation à jour', 'cotisation_requise')
                ->options(['' => 'Tous', '1' => 'Oui', '0' => 'Non'])
                ->filter(fn (Builder $q, string $valeur) => $q->where('cotisation_a_jour_requise', $valeur === '1')),
        ];
    }

    public function basculerActif(int $typeSinistreId): void
    {
        $type = TypeSinistre::findOrFail($typeSinistreId);

        $this->authorize('update', $type);

        $type->update(['actif' => ! $type->actif]);
    }
}
