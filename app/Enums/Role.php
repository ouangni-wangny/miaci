<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'ADMIN';
    case Gestionnaire = 'GESTIONNAIRE';
    case Adherent = 'ADHERENT';

    public function libelle(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Gestionnaire => 'Gestionnaire',
            self::Adherent => 'Adhérent',
        };
    }
}
