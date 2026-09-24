<?php

namespace App\Livewire\Gestion\Adherents;

use App\Enums\StatutAdherent;
use App\Livewire\Tables\DataTable;
use App\Models\Adherent;
use App\Services\AuditLogger;
use App\Services\CotisationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AdherentsTable extends DataTable
{
    protected string $placeholderRecherche = 'Nom, matricule, ville, téléphone…';

    protected string $messageVide = 'Aucun adhérent tuteur trouvé.';

    protected string $prefixeExport = 'adherents-selection';

    protected function autoriser(): void
    {
        $this->authorize('viewAny', Adherent::class);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('nom', 'asc');

        // Répartition par ville, cliquable, sous la barre d'outils.
        $this->setConfigurableArea('after-tools', 'livewire.gestion.adherents.repartition-villes');
    }

    public function builder(): Builder
    {
        return Adherent::query();
    }

    public function columns(): array
    {
        $cotisations = app(CotisationService::class);

        return [
            Column::make('Photo')
                ->label(fn (Adherent $adherent) => view('livewire.gestion.adherents.cellule-photo', ['adherent' => $adherent])->render())
                ->html()
                ->excludeFromColumnSelect(),

            Column::make('Matricule', 'matricule')
                ->sortable(),

            Column::make('Nom', 'nom')
                ->label(fn (Adherent $adherent) => view('livewire.gestion.adherents.cellule-nom', [
                    'adherent' => $adherent,
                    'signale' => $cotisations->doitEtreSignalePourArrieres($adherent),
                ])->render())
                ->html()
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy('nom', $sens)->orderBy('prenom', $sens))
                // Une seule colonne porte la recherche : le scope existant
                // (matricule, nom, prénom, établissement) + contact et ville.
                ->searchable(fn (Builder $q, string $terme) => $q->orWhere(
                    fn (Builder $w) => $w->recherche($terme)
                        ->orWhere('telephone', 'like', "%{$terme}%")
                        ->orWhere('ville', 'like', "%{$terme}%")
                )),

            Column::make('Établissement', 'etablissement')
                ->sortable()
                ->collapseOnTablet(),

            Column::make('Ville', 'ville')
                ->sortable()
                ->collapseOnTablet(),

            Column::make('Téléphone', 'telephone')
                ->deselected()
                ->collapseOnTablet(),

            Column::make("Date d'adhésion", 'date_adhesion')
                ->format(fn ($valeur) => $valeur?->format('d/m/Y'))
                ->sortable()
                ->deselected()
                ->collapseOnTablet(),

            Column::make('Statut', 'statut')
                ->label(fn (Adherent $adherent) => $this->badge($adherent->statut->libelle(), $adherent->statut->couleur()))
                ->html()
                ->sortable(),

            $this->colonneActions(function (Adherent $adherent) {
                $actions = [['libelle' => 'Voir la fiche', 'icone' => 'eye', 'url' => route('gestion.adherents.fiche', $adherent)]];

                if (auth()->user()->can('delete', $adherent)) {
                    $actions[] = [
                        'libelle' => 'Supprimer',
                        'icone' => 'trash',
                        'wire' => "supprimer({$adherent->id})",
                        'couleur' => 'red',
                        'confirmation' => "Supprimer définitivement {$adherent->matricule} ? Cette action bloque si un historique de cotisations/sinistres existe déjà.",
                    ];
                }

                return $actions;
            }),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'statut')
                ->options(['' => 'Tous'] + collect(StatutAdherent::cases())->mapWithKeys(fn ($s) => [$s->value => $s->libelle()])->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('statut', $valeur)),

            SelectFilter::make('Établissement', 'etablissement')
                ->options(['' => 'Tous'] + $this->etablissements())
                ->filter(fn (Builder $q, string $valeur) => $q->where('etablissement', $valeur)),

            SelectFilter::make('Ville', 'ville')
                ->options(['' => 'Toutes'] + $this->repartitionParVille()->mapWithKeys(
                    fn (array $ligne) => [$ligne['ville'] => "{$ligne['ville']} ({$ligne['total']})"]
                )->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('ville', $valeur)),

            DateFilter::make('Adhésion à partir du', 'adhesion_depuis')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('date_adhesion', '>=', $date)),

            DateFilter::make("Adhésion jusqu'au", 'adhesion_jusqua')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('date_adhesion', '<=', $date)),
        ];
    }

    /**
     * Répartition du nombre d'adhérents par ville, du plus au moins
     * représenté (alimente le filtre « Ville » et les pastilles cliquables).
     *
     * @return Collection<int, array{ville: string, total: int}>
     */
    public function repartitionParVille(): Collection
    {
        return Adherent::query()
            ->whereNotNull('ville')
            ->where('ville', '!=', '')
            ->selectRaw('ville, count(*) as total')
            ->groupBy('ville')
            ->orderByDesc('total')
            ->orderBy('ville')
            ->get()
            ->map(fn ($ligne) => ['ville' => $ligne->ville, 'total' => (int) $ligne->total]);
    }

    /**
     * @return array<string, string>
     */
    private function etablissements(): array
    {
        return Adherent::query()
            ->whereNotNull('etablissement')
            ->where('etablissement', '!=', '')
            ->distinct()
            ->orderBy('etablissement')
            ->pluck('etablissement', 'etablissement')
            ->all();
    }

    /** Clic sur une pastille de ville : applique le filtre, ou le retire si déjà actif. */
    public function filtrerParVille(string $ville): void
    {
        $actuelle = $this->getAppliedFilterWithValue('ville');

        $this->setFilter('ville', $actuelle === $ville ? '' : $ville);
        $this->resetPage();
    }

    public function supprimer(int $id, AuditLogger $audit): void
    {
        $adherent = Adherent::findOrFail($id);

        $this->authorize('delete', $adherent);

        if ($adherent->possedeHistorique()) {
            session()->flash('erreur', "Impossible de supprimer {$adherent->matricule} : cet adhérent a un historique (cotisations, sinistres, personnes à charge...). Utilisez plutôt le statut « Radié ».");

            return;
        }

        $audit->log('adherent.supprime', $adherent, $adherent->toArray());

        DB::transaction(function () use ($adherent) {
            $user = $adherent->user;
            $adherent->delete();
            $user?->delete();
        });

        session()->flash('status', "Adhérent {$adherent->matricule} supprimé.");
    }
}
