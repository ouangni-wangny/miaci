<?php

namespace App\Enums;

enum StatutPersonneACharge: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function libelle(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
