<?php

namespace App\Enums;

enum StatutCotisation: string
{
    case Valide = 'valide';
    case Annule = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::Valide => 'Validée',
            self::Annule => 'Annulée',
        };
    }
}
