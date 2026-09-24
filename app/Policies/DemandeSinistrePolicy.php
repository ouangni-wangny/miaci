<?php

namespace App\Policies;

use App\Enums\StatutDemandeSinistre;
use App\Models\DemandeSinistre;
use App\Models\User;

class DemandeSinistrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function view(User $user, DemandeSinistre $demande): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])
            || $user->id === $demande->adherent->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADHERENT');
    }

    /**
     * Compléter une demande à laquelle un complément d'information a été demandé
     * (ajout de pièces justificatives supplémentaires).
     */
    public function completer(User $user, DemandeSinistre $demande): bool
    {
        return $user->id === $demande->adherent->user_id
            && $demande->statut === StatutDemandeSinistre::ComplementDemande;
    }

    public function decider(User $user, DemandeSinistre $demande): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }
}
