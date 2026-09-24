<?php

namespace App\Contracts;

use App\Models\TransactionPaiement;

/**
 * Contrat commun à tous les agrégateurs de paiement (CinetPay, PayDunya...).
 * Toute la logique métier (création de la cotisation après succès, etc.)
 * passe par ce contrat afin de pouvoir changer ou ajouter un prestataire
 * sans toucher au reste de l'application.
 */
interface PaymentGatewayInterface
{
    /**
     * Initie un paiement auprès du prestataire et renvoie l'URL vers
     * laquelle rediriger l'adhérent pour effectuer le paiement.
     */
    public function initierPaiement(TransactionPaiement $transaction): string;

    /**
     * Interroge le prestataire pour connaître le statut réel d'une
     * transaction. C'est la SEULE source de vérité pour valider un
     * paiement : le contenu brut d'un webhook ne doit jamais être
     * appliqué tel quel.
     *
     * @return array{statut: \App\Enums\StatutTransaction, reference_fournisseur: ?string, payload: array}
     */
    public function verifierTransaction(TransactionPaiement $transaction): array;

    /**
     * Extrait la référence interne de transaction depuis la charge utile
     * envoyée par le prestataire vers l'URL de notification (webhook).
     */
    public function extraireReferenceDepuisWebhook(array $payload): ?string;
}
