<?php

namespace App\Livewire\Gestion\Paiements;

use App\Enums\FournisseurPaiement;
use App\Enums\StatutTransaction;
use App\Livewire\Tables\DataTable;
use App\Models\TransactionPaiement;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class TransactionsTable extends DataTable
{
    protected string $placeholderRecherche = 'Référence, adhérent…';

    protected string $messageVide = 'Aucune transaction.';

    protected string $prefixeExport = 'transactions-paiement';

    protected function autoriser(): void
    {
        $this->authorize('viewAny', TransactionPaiement::class);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('initie_le', 'desc');
    }

    public function builder(): Builder
    {
        return TransactionPaiement::query()->with('adherent');
    }

    public function columns(): array
    {
        return [
            Column::make('Référence', 'reference_interne')
                ->format(fn ($valeur) => '<span class="font-mono text-xs text-gray-900">'.e($valeur).'</span>')
                ->html()
                ->sortable()
                ->searchable(),

            $this->colonneAdherent(),

            Column::make('Montant', 'montant')
                ->format(fn ($valeur) => $this->fcfa($valeur))
                ->sortable(),

            Column::make('Fournisseur', 'fournisseur')
                ->format(fn ($valeur) => $valeur?->libelle())
                ->sortable()
                ->collapseOnTablet(),

            Column::make('Statut', 'statut')
                ->label(fn (TransactionPaiement $t) => $this->badge($t->statut->libelle(), match ($t->statut) {
                    StatutTransaction::EnAttente => 'yellow',
                    StatutTransaction::Reussi => 'green',
                    StatutTransaction::Echoue => 'red',
                    StatutTransaction::Rembourse => 'gray',
                }))
                ->html()
                ->sortable(),

            Column::make('Initiée le', 'initie_le')
                ->format(fn ($valeur) => $valeur?->format('d/m/Y H:i'))
                ->sortable()
                ->collapseOnTablet(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Statut', 'statut')
                ->options(['' => 'Tous'] + collect(StatutTransaction::cases())->mapWithKeys(fn ($s) => [$s->value => $s->libelle()])->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('statut', $valeur)),

            SelectFilter::make('Fournisseur', 'fournisseur')
                ->options(['' => 'Tous'] + collect(FournisseurPaiement::cases())->mapWithKeys(fn ($f) => [$f->value => $f->libelle()])->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('fournisseur', $valeur)),

            DateFilter::make('Initiée à partir du', 'initiee_depuis')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('initie_le', '>=', $date)),

            DateFilter::make("Initiée jusqu'au", 'initiee_jusqua')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('initie_le', '<=', $date)),
        ];
    }
}
