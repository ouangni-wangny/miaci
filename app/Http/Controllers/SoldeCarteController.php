<?php

namespace App\Http\Controllers;

use App\Models\Adherent;
use App\Services\CotisationService;
use Illuminate\View\View;

/**
 * Page publique (sans connexion) affichant l'état de cotisation d'un
 * adhérent, accessible uniquement via le lien signé encodé dans le QR
 * code imprimé sur sa carte de membre (voir CarteMembreController). La
 * signature Laravel (middleware `signed`) empêche de consulter le solde
 * d'un autre adhérent en modifiant l'identifiant dans l'URL : toute
 * altération invalide la signature et renvoie une erreur 403.
 */
class SoldeCarteController extends Controller
{
    public function __invoke(Adherent $adherent, CotisationService $cotisationService): View
    {
        return view('public.solde-carte', [
            'adherent' => $adherent,
            'solde' => $cotisationService->calculerSolde($adherent),
            // À jour = aucun mois échu impayé (hors mois en cours), voir
            // CotisationService::estAJour() — distinct du solde cumulé, qui
            // peut rester positif (mois en cours pas encore payé) même sans
            // arriéré réel.
            'estAJour' => $cotisationService->estAJour($adherent),
            'derniereCotisation' => $adherent->cotisations()->valides()->latest('date_paiement')->first(),
        ]);
    }
}
