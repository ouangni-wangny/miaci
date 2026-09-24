<?php

namespace App\Policies;

use App\Models\User;

class ParametreSitePolicy
{
    /**
     * Modification du contenu de la page d'accueil publique : réservée à
     * l'admin (au même titre que les autres paramètres de la mutuelle).
     */
    public function gerer(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }
}
