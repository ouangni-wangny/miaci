<?php

namespace App\Enums;

enum Mois: int
{
    case Janvier = 1;
    case Fevrier = 2;
    case Mars = 3;
    case Avril = 4;
    case Mai = 5;
    case Juin = 6;
    case Juillet = 7;
    case Aout = 8;
    case Septembre = 9;
    case Octobre = 10;
    case Novembre = 11;
    case Decembre = 12;

    public function libelle(): string
    {
        return match ($this) {
            self::Janvier => 'Janvier',
            self::Fevrier => 'Février',
            self::Mars => 'Mars',
            self::Avril => 'Avril',
            self::Mai => 'Mai',
            self::Juin => 'Juin',
            self::Juillet => 'Juillet',
            self::Aout => 'Août',
            self::Septembre => 'Septembre',
            self::Octobre => 'Octobre',
            self::Novembre => 'Novembre',
            self::Decembre => 'Décembre',
        };
    }
}
