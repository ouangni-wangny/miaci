<?php

namespace App\Enums;

enum BeneficiaireType: string
{
    case Adherent = 'adherent';
    case PersonneACharge = 'personne_a_charge';

    public function libelle(): string
    {
        return match ($this) {
            self::Adherent => "L'adhérent lui-même",
            self::PersonneACharge => 'Une personne à charge',
        };
    }
}
