<?php

namespace App\Livewire;

use App\Enums\StatutAdherent;
use App\Enums\StatutCotisation;
use App\Enums\StatutDemandeSinistre;
use App\Enums\StatutDroitAdhesion;
use App\Enums\StatutPersonneACharge;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\DemandeSinistre;
use App\Models\DonFinAnnee;
use App\Models\DroitAdhesion;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Services\CotisationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(CotisationService $cotisationService): View
    {
        if (auth()->user()->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])) {
            return view('livewire.dashboard', $this->donneesGestion($cotisationService));
        }

        $adherent = auth()->user()->adherent;

        return view('livewire.dashboard', [
            'adherent' => $adherent,
            'solde' => $adherent ? $cotisationService->calculerSolde($adherent) : null,
            // À jour = aucun mois échu impayé (hors mois en cours), voir
            // CotisationService::estAJour() — distinct du solde cumulé
            // ($solde['reste']), qui peut rester positif (mois en cours pas
            // encore payé) même quand on n'est pas en retard.
            'estAJour' => $adherent ? $cotisationService->estAJour($adherent) : true,
            'dernieresDemandes' => $adherent
                ? $adherent->demandesSinistre()->with('typeSinistre')->latest()->limit(3)->get()
                : collect(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesGestion(CotisationService $cotisationService): array
    {
        $parametre = ParametreCotisation::actuel();

        $cotisationsMois = Cotisation::where('statut', StatutCotisation::Valide)
            ->whereBetween('date_paiement', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('montant');

        $totalVerseAnnee = Cotisation::where('statut', StatutCotisation::Valide)
            ->whereBetween('date_paiement', [now()->startOfYear(), now()->endOfYear()])
            ->sum('montant');

        // Les adhérents actifs sont chargés une seule fois, avec leurs
        // personnes à charge (et le droit d'adhésion de chacune) et leurs
        // cotisations déjà en mémoire : les services ci-dessous accèdent à
        // ces relations par propriété (mise en cache sur le modèle), pas
        // par de nouvelles requêtes — indispensable avec plusieurs centaines
        // d'adhérents, sous peine de multiplier les allers-retours SQL par
        // adhérent (et par personne à charge) à chaque section du tableau
        // de bord.
        $adherentsActifs = Adherent::where('statut', StatutAdherent::Actif)
            ->with(['personnesACharge.droitsAdhesion', 'cotisations'])
            ->get();

        // Recouvrement du mois : montant théoriquement dû par les cotisants
        // actifs (adhérents actifs + personnes à charge actives dont le
        // droit d'adhésion est payé) comparé à l'encaissé.
        $nbAdherentsCotisants = $adherentsActifs->count();
        $nbPersonnesCotisantes = $adherentsActifs
            ->flatMap(fn (Adherent $a) => $a->personnesACharge)
            ->filter(fn (PersonneACharge $p) => $p->statut === StatutPersonneACharge::Active && $p->droitAdhesionPaye())
            ->count();
        $nbCotisants = $nbAdherentsCotisants + $nbPersonnesCotisantes;
        $montantAttenduMois = $parametre ? $nbCotisants * $parametre->montant : 0;
        $tauxRecouvrement = $montantAttenduMois > 0
            ? (int) round(min($cotisationsMois, $montantAttenduMois) / $montantAttenduMois * 100)
            : null;

        // Arriérés cumulés, sur les adhérents actifs uniquement.
        $arrieresTotal = 0;
        $nbEnAlerteArrieres = 0;
        foreach ($adherentsActifs as $a) {
            $solde = $cotisationService->calculerSolde($a);
            $arrieresTotal += $solde['reste'];
            if ($cotisationService->doitEtreSignalePourArrieres($a)) {
                $nbEnAlerteArrieres++;
            }
        }

        // Tendance des encaissements sur les 6 derniers mois.
        $tendanceCotisations = [];
        for ($i = 5; $i >= 0; $i--) {
            $mois = now()->subMonths($i);
            $tendanceCotisations[] = [
                'label' => $mois->translatedFormat('M'),
                'montant' => (int) Cotisation::where('statut', StatutCotisation::Valide)
                    ->whereYear('date_paiement', $mois->year)
                    ->whereMonth('date_paiement', $mois->month)
                    ->sum('montant'),
            ];
        }

        // Répartition des adhérents par statut + croissance mois/mois.
        $repartitionAdherents = Adherent::selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $nouveauxCeMois = Adherent::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $nouveauxMoisDernier = Adherent::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth(),
        ])->count();

        // Sinistres : répartition par statut + montant versé cette année.
        $sinistresParStatut = DemandeSinistre::selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $montantVerseAnneeSinistres = (int) DemandeSinistre::where('statut', StatutDemandeSinistre::Approuvee)
            ->whereYear('traite_le', now()->year)
            ->sum('montant_accorde');

        // Droit d'adhésion : encaissé au total (hors paiements annulés) +
        // personnes à charge bloquées faute de paiement (donc non comptées
        // comme cotisantes).
        $droitAdhesionEncaisseTotal = (int) DroitAdhesion::where('statut', StatutDroitAdhesion::Valide)->sum('montant');
        $personnesEnAttenteDroitAdhesion = PersonneACharge::where('droit_adhesion_exonere', false)
            ->whereDoesntHave('droitsAdhesion', fn ($q) => $q->where('statut', StatutDroitAdhesion::Valide))
            ->count();

        // Dons de fin d'année (Article 7) : éligibles (carnet >= 3) vs déjà versés.
        $eligiblesDonsFinAnnee = $adherentsActifs
            ->filter(fn (Adherent $a) => $a->eligibleDonFinAnnee($cotisationService))
            ->count();
        $donsVersesAnnee = DonFinAnnee::where('annee', now()->year)->count();

        return [
            'adherentsActifs' => $nbAdherentsCotisants,
            'cotisationsMois' => $cotisationsMois,
            'totalVerseAnnee' => $totalVerseAnnee,
            'sinistresEnAttente' => ($sinistresParStatut[StatutDemandeSinistre::Soumise->value] ?? 0)
                + ($sinistresParStatut[StatutDemandeSinistre::EnCoursExamen->value] ?? 0)
                + ($sinistresParStatut[StatutDemandeSinistre::ComplementDemande->value] ?? 0),

            'montantAttenduMois' => $montantAttenduMois,
            'tauxRecouvrement' => $tauxRecouvrement,
            'arrieresTotal' => $arrieresTotal,
            'nbEnAlerteArrieres' => $nbEnAlerteArrieres,
            'tendanceCotisations' => $tendanceCotisations,

            'repartitionAdherents' => $repartitionAdherents,
            'nouveauxCeMois' => $nouveauxCeMois,
            'nouveauxMoisDernier' => $nouveauxMoisDernier,

            'sinistresParStatut' => $sinistresParStatut,
            'montantVerseAnneeSinistres' => $montantVerseAnneeSinistres,

            'droitAdhesionEncaisseTotal' => $droitAdhesionEncaisseTotal,
            'personnesEnAttenteDroitAdhesion' => $personnesEnAttenteDroitAdhesion,

            'eligiblesDonsFinAnnee' => $eligiblesDonsFinAnnee,
            'donsVersesAnnee' => $donsVersesAnnee,
        ];
    }
}
