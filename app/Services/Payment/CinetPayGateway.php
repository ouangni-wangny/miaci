<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\StatutTransaction;
use App\Models\TransactionPaiement;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Intégration CinetPay (agrège Orange Money, MTN Mobile Money, Moov Money,
 * Wave et cartes VISA/Mastercard pour la Côte d'Ivoire).
 *
 * NOTE : la forme exacte des endpoints et des champs ci-dessous suit
 * l'API Checkout v2 documentée par CinetPay au moment de l'écriture.
 * À vérifier contre la documentation à jour (https://docs.cinetpay.com)
 * une fois de vraies clés API disponibles, avant la mise en production.
 */
class CinetPayGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $siteId,
        private readonly string $baseUrl,
        private readonly string $notifyUrl,
        private readonly string $returnUrl,
    ) {}

    public function estConfigure(): bool
    {
        return filled($this->apiKey) && filled($this->siteId);
    }

    public function initierPaiement(TransactionPaiement $transaction): string
    {
        if (! $this->estConfigure()) {
            throw new RuntimeException(
                "La passerelle CinetPay n'est pas configurée (CINETPAY_API_KEY / CINETPAY_SITE_ID manquants)."
            );
        }

        $adherent = $transaction->adherent;

        $reponse = Http::asJson()->post("{$this->baseUrl}/v2/payment", [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transaction->reference_interne,
            'amount' => $transaction->montant,
            'currency' => 'XOF',
            'description' => $transaction->type->libelle().' MIACI',
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl,
            'channels' => 'ALL',
            'customer_name' => $adherent->nom,
            'customer_surname' => $adherent->prenom,
            'customer_email' => $adherent->email ?: 'sans-email@miaci.ci',
            'customer_phone_number' => $adherent->telephone ?: '0000000000',
        ]);

        $donnees = $reponse->json();

        if (! $reponse->successful() || ($donnees['code'] ?? null) !== '201') {
            throw new RuntimeException(
                'Échec de l\'initialisation du paiement CinetPay : '.($donnees['message'] ?? $reponse->body())
            );
        }

        return $donnees['data']['payment_url'];
    }

    public function verifierTransaction(TransactionPaiement $transaction): array
    {
        $reponse = Http::asJson()->post("{$this->baseUrl}/v2/payment/check", [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transaction->reference_interne,
        ]);

        $donnees = $reponse->json() ?? [];
        $statutFournisseur = $donnees['data']['status'] ?? null;

        $statut = match ($statutFournisseur) {
            'ACCEPTED' => StatutTransaction::Reussi,
            'REFUSED', 'CANCELLED' => StatutTransaction::Echoue,
            default => StatutTransaction::EnAttente,
        };

        return [
            'statut' => $statut,
            'reference_fournisseur' => $donnees['data']['operator_id'] ?? null,
            'moyen_paiement' => $donnees['data']['payment_method'] ?? null,
            'payload' => $donnees,
        ];
    }

    public function extraireReferenceDepuisWebhook(array $payload): ?string
    {
        return $payload['cpm_trans_id'] ?? $payload['transaction_id'] ?? null;
    }
}
