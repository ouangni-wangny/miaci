<?php

namespace App\Livewire\Gestion\DonsFinAnnee;

use App\Livewire\Tables\DataTable;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\DonFinAnnee;
use App\Services\AuditLogger;
use App\Services\CotisationService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * Adhérents éligibles au don de fin d'année (Article 7 du règlement) : 10 000 F
 * versés le 20 décembre aux adhérents actifs, à jour de cotisation, ayant
 * fini leur délai de carence, dont le carnet compte au moins 3 membres
 * (eux-mêmes + au moins 2 personnes à charge validées) et dont au moins 2 de
 * ces personnes à charge ont elles-mêmes fini leur propre délai de carence.
 *
 * L'éligibilité est un calcul métier (Adherent::eligibleDonFinAnnee), pas une
 * condition SQL : on détermine d'abord les identifiants éligibles, puis la
 * table pagine/filtre/trie cette liste comme n'importe quelle autre.
 */
class DonsTable extends DataTable
{
    private const MONTANT = 10000;

    /** Année considérée, fournie (et mise à jour en direct) par la page. */
    #[Reactive]
    public string $annee = '';

    protected string $placeholderRecherche = 'Matricule, nom, prénom…';

    protected string $messageVide = "Aucun adhérent tuteur actif n'est actuellement éligible (carnet, cotisation et carence).";

    protected string $prefixeExport = 'dons-fin-annee-selection';

    /** @var array<int, int>|null */
    private ?array $idsEligibles = null;

    /** @var array<int, int>|null */
    private ?array $idsVerses = null;

    public function mount(string $annee = ''): void
    {
        $this->annee = $annee !== '' ? $annee : (string) now()->year;
    }

    protected function autoriser(): void
    {
        $this->authorize('create', Cotisation::class);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('nom', 'asc');
    }

    /** @return array<int, int> */
    private function idsEligibles(): array
    {
        return $this->idsEligibles ??= Adherent::query()
            ->actifs()
            ->with(['personnesACharge.droitsAdhesion', 'cotisations'])
            ->get()
            ->filter(fn (Adherent $a) => $a->eligibleDonFinAnnee(app(CotisationService::class)))
            ->pluck('id')
            ->all();
    }

    /** @return array<int, int> */
    private function idsVerses(): array
    {
        return $this->idsVerses ??= DonFinAnnee::where('annee', (int) $this->annee)
            ->pluck('adherent_id')
            ->all();
    }

    public function builder(): Builder
    {
        return Adherent::query()
            ->with(['personnesACharge.droitsAdhesion', 'cotisations'])
            ->whereIn('adherents.id', $this->idsEligibles());
    }

    public function columns(): array
    {
        return [
            Column::make('Adhérent tuteur', 'nom')
                ->label(fn (Adherent $adherent) => $adherent->nomComplet())
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy('nom', $sens)->orderBy('prenom', $sens))
                ->searchable(fn (Builder $q, string $terme) => $q->orWhere(fn (Builder $w) => $w->recherche($terme))),

            Column::make('Téléphone', 'telephone')
                ->format(fn ($valeur) => $valeur ?: '—')
                ->collapseOnTablet(),

            Column::make('Personnes dans le carnet')
                ->label(fn (Adherent $adherent) => $adherent->tailleCarnet()),

            Column::make("Statut {$this->annee}")
                ->label(fn (Adherent $adherent) => in_array($adherent->id, $this->idsVerses())
                    ? $this->badge('Versé', 'green')
                    : $this->badge('À verser', 'yellow'))
                ->html(),

            $this->colonneActions(function (Adherent $adherent) {
                if (in_array($adherent->id, $this->idsVerses())) {
                    return [];
                }

                return [[
                    'libelle' => 'Marquer comme versé',
                    'icone' => 'check-circle',
                    'wire' => "marquerVerse({$adherent->id})",
                    'confirmation' => 'Confirmer le versement de '.$this->fcfa(self::MONTANT)." à {$adherent->nomComplet()} ?",
                ]];
            }),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Versement', 'versement')
                ->options(['' => 'Tous', 'verse' => 'Versé', 'a_verser' => 'À verser'])
                ->filter(fn (Builder $q, string $valeur) => $valeur === 'verse'
                    ? $q->whereIn('adherents.id', $this->idsVerses())
                    : $q->whereNotIn('adherents.id', $this->idsVerses())),
        ];
    }

    public function marquerVerse(int $adherentId, AuditLogger $audit): void
    {
        $this->authorize('create', Cotisation::class);

        $adherent = Adherent::findOrFail($adherentId);

        abort_unless($adherent->eligibleDonFinAnnee(app(CotisationService::class)), 403, "Cet adhérent n'est pas éligible au don de fin d'année (carnet, cotisation ou délai de carence non conformes).");

        $don = DonFinAnnee::firstOrCreate(
            ['adherent_id' => $adherent->id, 'annee' => (int) $this->annee],
            [
                'montant' => self::MONTANT,
                'date_versement' => $this->annee.'-12-20',
                'enregistre_par' => auth()->id(),
            ]
        );

        if ($don->wasRecentlyCreated) {
            $audit->log('don_fin_annee.enregistre', $don, null, $don->only(['adherent_id', 'annee', 'montant']));
            session()->flash('status', 'Don de fin d\'année enregistré pour '.$adherent->nomComplet().'.');
        }

        $this->idsVerses = null;
    }
}
