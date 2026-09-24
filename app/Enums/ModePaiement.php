<?php

namespace App\Enums;

enum ModePaiement: string
{
    case Especes = 'especes';
    case Cheque = 'cheque';
    case Virement = 'virement';
    case MobileMoney = 'mobile_money';
    case Carte = 'carte';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::Especes => 'Espèces',
            self::Cheque => 'Chèque',
            self::Virement => 'Virement',
            self::MobileMoney => 'Mobile Money',
            self::Carte => 'Carte bancaire',
            self::Autre => 'Autre',
        };
    }
}
