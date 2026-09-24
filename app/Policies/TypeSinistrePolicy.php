<?php

namespace App\Policies;

use App\Models\TypeSinistre;
use App\Models\User;

class TypeSinistrePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function view(User $user, TypeSinistre $typeSinistre): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function update(User $user, TypeSinistre $typeSinistre): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function delete(User $user, TypeSinistre $typeSinistre): bool
    {
        return $user->hasRole('ADMIN');
    }
}
