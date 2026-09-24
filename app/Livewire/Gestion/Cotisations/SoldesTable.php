<?php

namespace App\Livewire\Gestion\Cotisations;

use App\Livewire\Tables\DataTable;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Services\CotisationService;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * Soldes de cotisation des adhérents actifs. Dû / payé / reste sont calculés
 * par CotisationService (règles de carence, personnes à charge...) et non
 * stockés : ces colonnes ne sont donc pas triables, et le filtre « Situation »
 * s'appuie sur le même service.
 */
class SoldesTable extends DataTable
{
    protected string $placeholderRecherche = 'Nom, matricule, établissement…';

    protected string $messageVide = 'Aucun adhérent trouvé.';

    protected string $prefixeExport = 'soldes-cotisation';

    /** @var array<int, array{solde: array<string, mixed>, aJour: bool}> */
    private array $soldes = [];

    protected function autoriser(): void
    {
        $this->authorize('viewAny', Cotisation::class);
    }

    protected function configurer(): void
    {
        $this->setDefaultSort('nom', 'asc');
    }

    public function builder(): Builder
    {
        return Adherent::query()->actifs();
    }

    /**
     * Solde et situation d'un adhérent, calculés une seule fois par requête
     * (affichage, export...).
     *
     * @return array{solde: array<string, mixed>, aJour: bool}
     */
    private function situation(Adherent $adherent): array
    {
        $service = app(CotisationService::class);

        // À jour = aucun mois échu impayé (hors mois en cours), voir
        // CotisationService::estAJour() — distinct du solde cumulé
        // (solde['reste']), qui peut rester positif (mois en cours pas
        // encore payé) même sans arriéré réel : le badge doit s'accorder
        // avec cette même référence.
        return $this->soldes[$adherent->id] ??= [
            'solde' => $service->calculerSolde($adherent),
            'aJour' => $service->estAJour($adherent),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Adhérent tuteur', 'nom')
                ->label(fn (Adherent $adherent) => $adherent->nomComplet())
                ->sortable(fn (Builder $q, string $sens) => $q->orderBy('nom', $sens)->orderBy('prenom', $sens))
                ->searchable(fn (Builder $q, string $terme) => $q->orWhere(fn (Builder $w) => $w->recherche($terme))),

            Column::make('Dû')
                ->label(fn (Adherent $adherent) => number_format($this->situation($adherent)['solde']['du'], 0, ',', ' ')),

            Column::make('Payé')
                ->label(fn (Adherent $adherent) => number_format($this->situation($adherent)['solde']['paye'], 0, ',', ' ')),

            Column::make('Reste à payer')
                ->label(function (Adherent $adherent) {
                    ['solde' => $solde, 'aJour' => $aJour] = $this->situation($adherent);
                    $reste = number_format($solde['reste'], 0, ',', ' ');

                    return match (true) {
                        ! $aJour => $this->badge("{$reste} FCFA", 'red'),
                        $solde['reste'] > 0 => $this->badge("À jour — {$reste} FCFA dû ce mois-ci", 'yellow'),
                        default => $this->badge('À jour', 'green'),
                    };
                })
                ->html(),

            $this->colonneActions(fn (Adherent $adherent) => [
                ['libelle' => 'Voir la fiche', 'icone' => 'eye', 'url' => route('gestion.adherents.fiche', $adherent)],
            ]),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Situation', 'situation')
                ->options([
                    '' => 'Toutes',
                    'retard' => 'En retard de cotisation',
                    'a_jour' => 'À jour',
                ])
                ->filter(function (Builder $q, string $valeur) {
                    $service = app(CotisationService::class);

                    $enRetard = Adherent::query()->actifs()->get()
                        ->reject(fn (Adherent $a) => $service->estAJour($a))
                        ->pluck('id');

                    $valeur === 'retard'
                        ? $q->whereIn('adherents.id', $enRetard)
                        : $q->whereNotIn('adherents.id', $enRetard);
                }),
        ];
    }
}
