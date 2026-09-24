<?php

namespace App\Enums;

enum StatutDemandeSinistre: string
{
    case Soumise = 'soumise';
    case EnCoursExamen = 'en_cours_examen';
    case Approuvee = 'approuvee';
    case Rejetee = 'rejetee';
    case ComplementDemande = 'complement_demande';

    public function libelle(): string
    {
        return match ($this) {
            self::Soumise => 'Soumise',
            self::EnCoursExamen => "En cours d'examen",
            self::Approuvee => 'Approuvée',
            self::Rejetee => 'Rejetée',
            self::ComplementDemande => 'Complément demandé',
        };
    }

    public function estFinale(): bool
    {
        return in_array($this, [self::Approuvee, self::Rejetee], true);
    }
}
