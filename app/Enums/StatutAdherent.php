<?php

namespace App\Enums;

enum StatutAdherent: string
{
    case EnAttente = 'en_attente';
    case Actif = 'actif';
    case Suspendu = 'suspendu';
    case Radie = 'radie';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de validation',
            self::Actif => 'Actif',
            self::Suspendu => 'Suspendu',
            self::Radie => 'Radié',
        };
    }

    public function couleur(): string
    {
        return match ($this) {
            self::EnAttente => 'blue',
            self::Actif => 'green',
            self::Suspendu => 'yellow',
            self::Radie => 'red',
        };
    }
}
