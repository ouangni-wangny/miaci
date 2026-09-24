<?php

namespace App\Enums;

enum StatutTransaction: string
{
    case EnAttente = 'en_attente';
    case Reussi = 'reussi';
    case Echoue = 'echoue';
    case Rembourse = 'rembourse';

    public function libelle(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Reussi => 'Réussi',
            self::Echoue => 'Échoué',
            self::Rembourse => 'Remboursé',
        };
    }
}
