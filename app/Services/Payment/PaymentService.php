<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Enums\FournisseurPaiement;
use App\Enums\ModePaiement;
use App\Enums\StatutTransaction;
use App\Enums\TypeTransactionPaiement;
use App\Models\Adherent;
use App\Models\PersonneACharge;
use App\Models\TransactionPaiement;
use App\Services\CotisationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly CotisationService $cotisationService,
    ) {}

    /**
     * Crée une transaction en attente et renvoie l'URL de paiement du prestataire.
     */
    public function initierPaiementCotisation(Adherent $adherent, int $montant): array
    {
        $transaction = TransactionPaiement::create([
            'adherent_id' => $adherent->id,
            'type' => TypeTransactionPaiement::Cotisation,
            'montant' => $montant,
            'fournisseur' => FournisseurPaiement::CinetPay,
            'reference_interne' => 'MIACI-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'statut' => StatutTransaction::EnAttente,
            'initie_le' => now(),
        ]);

        $urlPaiement = $this->gateway->initierPaiement($transaction);

        return [$transaction, $urlPaiement];
    }

    /**
     * Initie le paiement en ligne du droit d'adhésion pour l'adhérent
     * lui-même ou l'une de ses personnes à charge. `$payeur` est toujours
     * l'adhérent connecté (identité utilisée par le prestataire) ;
     * `$payable` est le bénéficiaire réel du droit d'adhésion.
     */
    public function initierPaiementDroitAdhesion(Adherent $payeur, Model $payable, int $montant): array
    {
        $transaction = TransactionPaiement::create([
            'adherent_id' => $payeur->id,
            'type' => TypeTransactionPaiement::DroitAdhesion,
            'payable_type' => $payable::class,
            'payable_id' => $payable->getKey(),
            'montant' => $montant,
            'fournisseur' => FournisseurPaiement::CinetPay,
            'reference_interne' => 'MIACI-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
            'statut' => StatutTransaction::EnAttente,
            'initie_le' => now(),
        ]);

        $urlPaiement = $this->gateway->initierPaiement($transaction);

        return [$transaction, $urlPaiement];
    }

    /**
     * Traite une notification (webhook) du prestataire : la charge utile
     * du webhook ne sert qu'à identifier QUELLE transaction vérifier —
     * le statut appliqué provient toujours d'un appel serveur-à-serveur
     * de vérification auprès du prestataire, jamais du contenu du webhook
     * lui-même.
     */
    public function traiterNotification(array $payloadWebhook): ?TransactionPaiement
    {
        $reference = $this->gateway->extraireReferenceDepuisWebhook($payloadWebhook);

        if (! $reference) {
            return null;
        }

        $transaction = TransactionPaiement::where('reference_interne', $reference)->first();

        if (! $transaction || $transaction->statut !== StatutTransaction::EnAttente) {
            return $transaction;
        }

        $verification = $this->gateway->verifierTransaction($transaction);

        $transaction->update([
            'statut' => $verification['statut'],
            'reference_fournisseur' => $verification['reference_fournisseur'],
            'payload_retour' => $verification['payload'],
            'complete_le' => now(),
        ]);

        if ($verification['statut'] === StatutTransaction::Reussi) {
            match ($transaction->type) {
                TypeTransactionPaiement::Cotisation => $this->confirmerCotisation($transaction, $verification['moyen_paiement'] ?? null),
                TypeTransactionPaiement::DroitAdhesion => $this->confirmerDroitAdhesion($transaction, $verification['moyen_paiement'] ?? null),
            };
        }

        return $transaction->fresh();
    }

    private function confirmerCotisation(TransactionPaiement $transaction, ?string $moyenPaiement): void
    {
        // Comble toujours les mois les plus anciens impayés d'abord (jamais
        // le mois en cours par défaut) — voir CotisationService::allouerPaiement().
        $cotisations = $this->cotisationService->allouerPaiement(
            $transaction->adherent,
            $transaction->montant,
            now(),
            $this->deriverModePaiement($moyenPaiement)->value,
            $transaction->reference_interne,
            null,
            $transaction->id,
        );

        $transaction->update(['cotisation_id' => $cotisations->first()->id]);
    }

    private function confirmerDroitAdhesion(TransactionPaiement $transaction, ?string $moyenPaiement): void
    {
        /** @var Adherent|PersonneACharge $payable */
        $payable = $transaction->payable;

        $payable->droitsAdhesion()->create([
            'montant' => $transaction->montant,
            'date_paiement' => now(),
            'mode_paiement' => $this->deriverModePaiement($moyenPaiement),
            'reference' => $transaction->reference_interne,
        ]);
    }

    private function deriverModePaiement(?string $moyenPaiement): ModePaiement
    {
        return match (true) {
            $moyenPaiement === null => ModePaiement::MobileMoney,
            str_contains(strtoupper($moyenPaiement), 'VISA'),
            str_contains(strtoupper($moyenPaiement), 'MASTERCARD') => ModePaiement::Carte,
            default => ModePaiement::MobileMoney,
        };
    }
}
