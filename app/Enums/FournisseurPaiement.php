<?php

namespace App\Enums;

enum FournisseurPaiement: string
{
    case CinetPay = 'cinetpay';

    public function libelle(): string
    {
        return match ($this) {
            self::CinetPay => 'CinetPay',
        };
    }
}
