<?php

namespace App\Policies;

use App\Models\Adherent;
use App\Models\PersonneACharge;
use App\Models\User;

class PersonneAChargePolicy
{
    public function view(User $user, PersonneACharge $personne): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])
            || $user->id === $personne->adherent->user_id;
    }

    /**
     * Seul un gestionnaire ou un admin peut déclarer une personne à charge —
     * l'adhérent ne peut plus le faire lui-même depuis son espace.
     */
    public function create(User $user, ?Adherent $adherent = null): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function update(User $user, PersonneACharge $personne): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function delete(User $user, PersonneACharge $personne): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }
}
