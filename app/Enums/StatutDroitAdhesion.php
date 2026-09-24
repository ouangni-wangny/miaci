<?php

namespace App\Enums;

enum StatutDroitAdhesion: string
{
    case Valide = 'valide';
    case Annule = 'annule';

    public function libelle(): string
    {
        return match ($this) {
            self::Valide => 'Validé',
            self::Annule => 'Annulé',
        };
    }
}
