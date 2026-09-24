<?php

namespace App\Enums;

enum StatutRelevePeriode: string
{
    case Paye = 'paye';
    case Partiel = 'partiel';
    case Impaye = 'impaye';

    public function libelle(): string
    {
        return match ($this) {
            self::Paye => 'Payé',
            self::Partiel => 'Partiel',
            self::Impaye => 'Impayé',
        };
    }
}
