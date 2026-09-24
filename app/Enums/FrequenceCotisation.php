<?php

namespace App\Enums;

enum FrequenceCotisation: string
{
    case Mensuelle = 'mensuelle';
    case Trimestrielle = 'trimestrielle';
    case Annuelle = 'annuelle';

    public function libelle(): string
    {
        return match ($this) {
            self::Mensuelle => 'Mensuelle',
            self::Trimestrielle => 'Trimestrielle',
            self::Annuelle => 'Annuelle',
        };
    }

    /**
     * Nombre de mois couverts par une période de cette fréquence.
     */
    public function moisParPeriode(): int
    {
        return match ($this) {
            self::Mensuelle => 1,
            self::Trimestrielle => 3,
            self::Annuelle => 12,
        };
    }
}
