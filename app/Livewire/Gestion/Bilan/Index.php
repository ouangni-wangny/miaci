<?php

namespace App\Livewire\Gestion\Bilan;

use App\Enums\StatutCotisation;
use App\Enums\StatutDemandeSinistre;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\DemandeSinistre;
use App\Models\DonFinAnnee;
use App\Models\DroitAdhesion;
use App\Services\CotisationService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Bilan annuel : prévisionnel de cotisation (formule du règlement) comparé
 * au réalisé, et comparaison des principaux indicateurs de l'année
 * sélectionnée avec l'année précédente. Le suivi financier de la mutuelle
 * dans l'application démarre en janvier 2026 (voir ParametreCotisation) :
 * les années antérieures n'auront donc jamais de données réelles.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    #[Url(as: 'annee', history: true)]
    public int $annee = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Adherent::class);

        if ($this->annee === 0) {
            $this->annee = (int) now()->year;
        }
    }

    /**
     * @return array<int, int>
     */
    public function anneesDisponibles(): array
    {
        $premiereAnnee = 2026;
        $anneeCourante = (int) now()->year;

        return range($anneeCourante, $premiereAnnee);
    }

    private function bilanAnnee(int $annee, CotisationService $cotisationService): array
    {
        $debut = Carbon::create($annee, 1, 1)->startOfDay();
        $fin = Carbon::create($annee, 12, 31)->endOfDay();

        $cotisationsRealisees = (int) Cotisation::where('statut', StatutCotisation::Valide)
            ->whereBetween('date_paiement', [$debut, $fin])
            ->sum('montant');

        $cotisationsAttendues = $cotisationService->montantAttenduPourPeriode($debut->copy(), $fin->copy());

        $droitAdhesionEncaisse = (int) DroitAdhesion::whereBetween('date_paiement', [$debut, $fin])->sum('montant');

        $nouvellesAdhesions = Adherent::whereBetween('created_at', [$debut, $fin])->count();

        $sinistresSoumis = DemandeSinistre::whereBetween('created_at', [$debut, $fin])->count();
        $sinistresApprouves = DemandeSinistre::where('statut', StatutDemandeSinistre::Approuvee)
            ->whereBetween('traite_le', [$debut, $fin])
            ->count();
        $montantVerseSinistres = (int) DemandeSinistre::where('statut', StatutDemandeSinistre::Approuvee)
            ->whereBetween('traite_le', [$debut, $fin])
            ->sum('montant_accorde');

        $donsVerses = DonFinAnnee::where('annee', $annee)->count();

        return [
            'annee' => $annee,
            'cotisationsAttendues' => $cotisationsAttendues,
            'cotisationsRealisees' => $cotisationsRealisees,
            'tauxRecouvrement' => $cotisationsAttendues > 0
                ? (int) round(min($cotisationsRealisees, $cotisationsAttendues) / $cotisationsAttendues * 100)
                : null,
            'droitAdhesionEncaisse' => $droitAdhesionEncaisse,
            'nouvellesAdhesions' => $nouvellesAdhesions,
            'sinistresSoumis' => $sinistresSoumis,
            'sinistresApprouves' => $sinistresApprouves,
            'montantVerseSinistres' => $montantVerseSinistres,
            'donsVerses' => $donsVerses,
        ];
    }

    public function render(CotisationService $cotisationService): View
    {
        return view('livewire.gestion.bilan.index', [
            'bilan' => $this->bilanAnnee($this->annee, $cotisationService),
            'bilanPrecedent' => $this->bilanAnnee($this->annee - 1, $cotisationService),
            'annees' => $this->anneesDisponibles(),
        ]);
    }
}
