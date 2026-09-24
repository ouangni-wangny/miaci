<?php

namespace App\Http\Controllers;

use App\Enums\StatutAdherent;
use App\Models\ParametreCotisation;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Initie un paiement en ligne de cotisation pour l'adhérent connecté
     * et le redirige vers la page de paiement du prestataire.
     */
    public function initier(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $adherent = $request->user()->adherent;

        abort_unless($adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");
        abort_unless(
            $adherent->statut === StatutAdherent::Actif,
            403,
            "Votre adhésion est en attente d. Agrandi validation par un gestionnaire."
        );

        $valides = $request->validate([
            'montant' => ['required', 'integer', 'min:100'],
        ]);

        try {
            [, $urlPaiement] = $paymentService->initierPaiementCotisation($adherent, $valides['montant']);
        } catch (\Throwable $e) {
            Log::error('Échec initiation paiement CinetPay', ['message' => $e->getMessage()]);

            return back()->with('erreur_paiement', "Le paiement en ligne n'est pas disponible pour le moment. Merci de réessayer plus tard ou de contacter la mutuelle.");
        }

        return redirect()->away($urlPaiement);
    }

    /**
     * Initie le paiement en ligne du droit d'adhésion pour l'adhérent
     * connecté lui-même ou l'une de ses personnes à charge. Le montant
     * n'est jamais fourni par le client : il vient du paramétrage en
     * vigueur, pour ne pas pouvoir être manipulé.
     */
    public function initierDroitAdhesion(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $adherent = $request->user()->adherent;

        abort_unless($adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");

        $valides = $request->validate([
            'beneficiaire' => ['required', 'in:adherent,personne'],
            'personne_a_charge_id' => ['required_if:beneficiaire,personne', 'nullable', 'integer'],
        ]);

        if ($valides['beneficiaire'] === 'adherent') {
            $payable = $adherent;
        } else {
            $payable = $adherent->personnesACharge()->findOrFail($valides['personne_a_charge_id']);
        }

        abort_if($payable->droitAdhesionPaye(), 403, "Le droit d'adhésion est déjà payé.");

        $montant = ParametreCotisation::actuel()?->droit_adhesion;

        abort_if($montant === null, 403, "Le montant du droit d'adhésion n'est pas paramétré. Contactez la mutuelle.");

        try {
            [, $urlPaiement] = $paymentService->initierPaiementDroitAdhesion($adherent, $payable, $montant);
        } catch (\Throwable $e) {
            Log::error('Échec initiation paiement CinetPay (droit d\'adhésion)', ['message' => $e->getMessage()]);

            return back()->with('erreur_paiement', "Le paiement en ligne n'est pas disponible pour le moment. Merci de réessayer plus tard ou de contacter la mutuelle.");
        }

        return redirect()->away($urlPaiement);
    }

    /**
     * Point de notification (webhook) appelé par le prestataire. Le corps
     * de la requête ne sert qu'à identifier la transaction : le statut
     * appliqué provient toujours d'une vérification serveur-à-serveur
     * auprès du prestataire (voir PaymentService::traiterNotification).
     */
    public function webhook(Request $request, PaymentService $paymentService): \Illuminate\Http\Response
    {
        $paymentService->traiterNotification($request->all());

        return response('', 200);
    }

    /**
     * Page de retour après paiement : purement informative, n'atteste
     * jamais du succès du paiement (voir le webhook pour la validation réelle).
     */
    public function retour(): View
    {
        return view('paiement.retour');
    }
}
