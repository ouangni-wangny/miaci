<?php

namespace App\Policies;

use App\Models\Cotisation;
use App\Models\User;

class CotisationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function view(User $user, Cotisation $cotisation): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])
            || $user->id === $cotisation->adherent->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function update(User $user, Cotisation $cotisation): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function annuler(User $user, Cotisation $cotisation): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function configurerParametres(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }
}
