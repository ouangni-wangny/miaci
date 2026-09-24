<?php

namespace App\Enums;

enum TypeTransactionPaiement: string
{
    case Cotisation = 'cotisation';
    case DroitAdhesion = 'droit_adhesion';

    public function libelle(): string
    {
        return match ($this) {
            self::Cotisation => 'Cotisation',
            self::DroitAdhesion => "Droit d'adhésion",
        };
    }
}
