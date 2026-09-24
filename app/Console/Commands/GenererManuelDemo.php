<?php

namespace App\Console\Commands;

use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Models\TypeSinistre;
use App\Services\CotisationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

/**
 * Génère le manuel de démo (PDF) qui guide, article par article, la
 * démonstration de conformité entre les statuts de la MIACI et
 * l'application — toujours à partir des données réellement en base au
 * moment de la génération, pour rester exact même si la base évolue avant
 * la démo (à relancer juste avant si besoin).
 */
class GenererManuelDemo extends Command
{
    protected $signature = 'demo:manuel {--sortie= : Chemin du PDF de sortie}';

    protected $description = "Génère le manuel de démo (PDF) illustrant les statuts avec les données réelles actuellement en base";

    public function handle(CotisationService $cotisationService): int
    {
        $donnees = $this->collecterDonnees($cotisationService);

        $pdf = Pdf::loadView('pdf.manuel-demo', $donnees)->setPaper('a4', 'portrait');

        $chemin = $this->option('sortie') ?: storage_path('app/private/exports/manuel-demo-miaci.pdf');

        if (! is_dir(dirname($chemin))) {
            mkdir(dirname($chemin), 0755, true);
        }

        file_put_contents($chemin, $pdf->output());

        $this->info("Manuel généré : {$chemin}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collecterDonnees(CotisationService $cotisationService): array
    {
        $totalAdherents = Adherent::count();
        $totalPac = PersonneACharge::count();

        $repartitionVilles = Adherent::query()
            ->whereNotNull('ville')->where('ville', '!=', '')
            ->selectRaw('ville, count(*) as total')
            ->groupBy('ville')->orderByDesc('total')->orderBy('ville')
            ->get();

        $parametre = ParametreCotisation::actuel();

        $exoneresCount = Adherent::where('droit_adhesion_exonere', true)->count();
        $nonExonere = Adherent::where('droit_adhesion_exonere', false)->orderBy('id')->first();

        $matriculeAncien = Adherent::orderBy('date_adhesion')->orderBy('id')->first();
        $matriculeMemeJour = Adherent::where('matricule', 'like', '%A2')->orderBy('date_adhesion')->first();

        // Exemple d'adhérent en retard, pour illustrer le délai de grâce et
        // la pénalité (Article 7) — le premier trouvé avec des arriérés
        // avérés, sans imposer un matricule figé qui pourrait ne plus
        // correspondre après une future correction de données.
        $exempleRetard = null;
        foreach (Adherent::orderBy('id')->get() as $a) {
            $arrieres = $cotisationService->moisArrieres($a);
            if ($arrieres > 0) {
                $exempleRetard = [
                    'adherent' => $a,
                    'arrieres' => $arrieres,
                    'carence' => $cotisationService->delaiCarenceMinimum($a),
                    'signalable' => $cotisationService->doitEtreSignalePourArrieres($a),
                ];

                if ($exempleRetard['signalable']) {
                    break;
                }
            }
        }

        $pacDouzeMois = PersonneACharge::where('nee_apres_1956', false)->with('adherent')->first();
        $pacHuitMois = PersonneACharge::where('nee_apres_1956', true)->with('adherent')->first();

        $donsEligibles = Adherent::actifs()->get()->filter(fn (Adherent $a) => $a->eligibleDonFinAnnee($cotisationService));
        $donExemple = $donsEligibles->first();

        $koffiGerard = Adherent::where('nom', 'like', '%KOFFI%')->where('prenom', 'like', '%GERAD%')->first();

        $typesSinistre = TypeSinistre::orderByDesc('actif')->orderByDesc('plafond_montant')->get();

        return [
            'dateGeneration' => now(),
            'totalAdherents' => $totalAdherents,
            'totalPac' => $totalPac,
            'totalVilles' => $repartitionVilles->count(),
            'repartitionVilles' => $repartitionVilles,
            'montantCotisation' => $parametre?->montant,
            'droitAdhesion' => $parametre?->droit_adhesion,
            'exoneresCount' => $exoneresCount,
            'nonExonere' => $nonExonere,
            'matriculeAncien' => $matriculeAncien,
            'matriculeMemeJour' => $matriculeMemeJour,
            'exempleRetard' => $exempleRetard,
            'pacDouzeMois' => $pacDouzeMois,
            'pacDouzeMoisCount' => PersonneACharge::where('nee_apres_1956', false)->count(),
            'pacHuitMoisCount' => PersonneACharge::where('nee_apres_1956', true)->count(),
            'pacHuitMois' => $pacHuitMois,
            'donsEligiblesCount' => $donsEligibles->count(),
            'donExemple' => $donExemple,
            'koffiGerard' => $koffiGerard,
            'typesSinistre' => $typesSinistre,
            'demandesSinistreCount' => DemandeSinistre::count(),
        ];
    }
}
