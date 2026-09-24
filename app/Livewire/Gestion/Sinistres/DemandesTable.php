<?php

namespace App\Livewire\Gestion\Sinistres;

use App\Enums\PreresultatEligibilite;
use App\Enums\StatutDemandeSinistre;
use App\Livewire\Tables\DataTable;
use App\Models\DemandeSinistre;
use App\Models\TypeSinistre;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class DemandesTable extends DataTable
{
    protected string $placeholderRecherche = 'Adhérent, type de sinistre…';

    protected string $messageVide = 'Aucune demande.';

    protected string $prefixeExport = 'demandes-sinistre';

    protected function autoriser(): void
    {
        $this->authorize('viewAny', DemandeSinistre::class);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return DemandeSinistre::query()->with(['adherent', 'typeSinistre']);
    }

    public function columns(): array
    {
        return [
            $this->colonneAdherent(),

            Column::make('Type')
                ->label(fn (DemandeSinistre $d) => $d->typeSinistre->libelle)
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy(
                    TypeSinistre::select('libelle')->whereColumn('types_sinistre.id', 'demandes_sinistre.type_sinistre_id'),
                    $sens
                ))
                ->searchable(fn (Builder $q, string $terme) => $q->orWhereHas(
                    'typeSinistre',
                    fn (Builder $t) => $t->where('libelle', 'like', "%{$terme}%")
                )),

            Column::make('Montant demandé', 'montant_demande')
                ->format(fn ($valeur) => $this->fcfa($valeur))
                ->sortable(),

            Column::make('Pré-résultat', 'preresultat_eligibilite')
                ->label(fn (DemandeSinistre $d) => $d->preresultat_eligibilite
                    ? $this->badge($d->preresultat_eligibilite->libelle(), match ($d->preresultat_eligibilite) {
                        PreresultatEligibilite::ProbablementEligible => 'green',
                        PreresultatEligibilite::ProbablementNonEligible => 'orange',
                    })
                    : '')
                ->html()
                ->collapseOnTablet(),

            Column::make('Statut', 'statut')
                ->label(fn (DemandeSinistre $d) => $this->badge($d->statut->libelle(), match ($d->statut) {
                    StatutDemandeSinistre::Soumise, StatutDemandeSinistre::EnCoursExamen => 'blue',
                    StatutDemandeSinistre::Approuvee => 'green',
                    StatutDemandeSinistre::Rejetee => 'red',
                    StatutDemandeSinistre::ComplementDemande => 'yellow',
                }))
                ->html()
                ->sortable(),

            Column::make('Soumise le', 'created_at')
                ->format(fn ($valeur) => $valeur?->format('d/m/Y'))
                ->sortable()
                ->collapseOnTablet(),

            $this->colonneActions(fn (DemandeSinistre $d) => [
                ['libelle' => 'Examiner', 'icone' => 'clipboard-document-check', 'url' => route('gestion.sinistres.fiche', $d)],
            ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'statut')
                ->options(['' => 'Tous'] + collect(StatutDemandeSinistre::cases())->mapWithKeys(fn ($s) => [$s->value => $s->libelle()])->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('statut', $valeur)),

            SelectFilter::make('Type de sinistre', 'type_sinistre')
                ->options(['' => 'Tous'] + TypeSinistre::orderBy('libelle')->pluck('libelle', 'id')->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('type_sinistre_id', $valeur)),

            SelectFilter::make('Pré-résultat', 'preresultat')
                ->options(['' => 'Tous'] + collect(PreresultatEligibilite::cases())->mapWithKeys(fn ($p) => [$p->value => $p->libelle()])->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('preresultat_eligibilite', $valeur)),

            DateFilter::make('Soumise à partir du', 'soumise_depuis')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date)),

            DateFilter::make("Soumise jusqu'au", 'soumise_jusqua')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)),
        ];
    }
}
