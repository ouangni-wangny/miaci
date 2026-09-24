<?php

namespace App\Livewire\Gestion\Audit;

use App\Livewire\Tables\DataTable;
use App\Models\JournalAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class JournalTable extends DataTable
{
    /** Valeur du filtre « Utilisateur » pour les actions sans utilisateur. */
    private const SYSTEME = 'systeme';

    protected string $placeholderRecherche = 'Action, utilisateur, entité…';

    protected string $messageVide = 'Aucune entrée dans le journal.';

    protected int $parPage = 25;

    protected string $prefixeExport = 'journal-audit';

    protected function autoriser(): void
    {
        abort_unless(auth()->user()?->can('voir_journal_audit'), 403);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return JournalAudit::query()->with('user');
    }

    public function columns(): array
    {
        return [
            Column::make('Date', 'created_at')
                ->format(fn ($valeur) => $valeur?->format('d/m/Y H:i'))
                ->sortable(),

            Column::make('Utilisateur')
                ->label(fn (JournalAudit $entree) => $entree->user?->name ?? 'Système')
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy(
                    User::select('name')->whereColumn('users.id', 'journal_audit.user_id'),
                    $sens
                ))
                ->searchable(fn (Builder $q, string $terme) => $q->orWhereHas(
                    'user',
                    fn (Builder $u) => $u->where('name', 'like', "%{$terme}%")
                )),

            Column::make('Action', 'action')
                ->format(fn ($valeur) => '<span class="font-mono text-xs text-gray-700">'.e($valeur).'</span>')
                ->html()
                ->sortable()
                ->searchable(),

            Column::make('Entité')
                ->label(fn (JournalAudit $entree) => class_basename($entree->entite_type).' #'.$entree->entite_id)
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy('entite_type', $sens)->orderBy('entite_id', $sens))
                ->searchable(fn (Builder $q, string $terme) => $q->orWhere('entite_type', 'like', "%{$terme}%")),

            Column::make('Détail')
                ->label(fn (JournalAudit $entree) => view('livewire.gestion.audit.cellule-detail', ['entree' => $entree])->render())
                ->html()
                ->collapseOnTablet(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Utilisateur', 'utilisateur')
                ->options(['' => 'Tous', self::SYSTEME => 'Système'] + User::query()
                    ->whereIn('id', JournalAudit::query()->whereNotNull('user_id')->select('user_id'))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->filter(fn (Builder $q, string $valeur) => $valeur === self::SYSTEME
                    ? $q->whereNull('user_id')
                    : $q->where('user_id', $valeur)),

            SelectFilter::make('Action', 'action_filtre')
                ->options(['' => 'Toutes'] + JournalAudit::query()->distinct()->orderBy('action')->pluck('action', 'action')->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('action', $valeur)),

            SelectFilter::make('Type d\'entité', 'entite')
                ->options(['' => 'Tous'] + JournalAudit::query()->distinct()->orderBy('entite_type')->pluck('entite_type')
                    ->mapWithKeys(fn (string $type) => [$type => class_basename($type)])->all())
                ->filter(fn (Builder $q, string $valeur) => $q->where('entite_type', $valeur)),

            DateFilter::make('À partir du', 'depuis')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date)),

            DateFilter::make("Jusqu'au", 'jusqua')
                ->filter(fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date)),
        ];
    }
}
