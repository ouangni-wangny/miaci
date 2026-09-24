<?php

namespace App\Policies;

use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\PersonneACharge;
use App\Models\PieceJustificative;
use App\Models\User;

class PieceJustificativePolicy
{
    /**
     * Seuls le gestionnaire/admin et l'adhérent concerné (propriétaire de la
     * demande de sinistre ou de la personne à charge) peuvent consulter une
     * pièce justificative : ce sont des documents potentiellement sensibles
     * (santé, décès...).
     */
    public function view(User $user, PieceJustificative $piece): bool
    {
        if ($user->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])) {
            return true;
        }

        $proprietaire = match (true) {
            $piece->justificable instanceof DemandeSinistre => $piece->justificable->adherent,
            $piece->justificable instanceof PersonneACharge => $piece->justificable->adherent,
            default => null,
        };

        return $proprietaire instanceof Adherent && $user->id === $proprietaire->user_id;
    }
}
