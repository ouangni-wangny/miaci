<?php

namespace App\Enums;

enum PreresultatEligibilite: string
{
    case ProbablementEligible = 'probablement_eligible';
    case ProbablementNonEligible = 'probablement_non_eligible';

    public function libelle(): string
    {
        return match ($this) {
            self::ProbablementEligible => 'Probablement éligible',
            self::ProbablementNonEligible => 'Probablement non éligible',
        };
    }
}
