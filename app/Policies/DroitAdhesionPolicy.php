<?php

namespace App\Policies;

use App\Models\DroitAdhesion;
use App\Models\User;

class DroitAdhesionPolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function annuler(User $user, DroitAdhesion $droitAdhesion): bool
    {
        return $user->hasRole('ADMIN');
    }
}
