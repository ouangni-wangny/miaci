<?php

namespace App\Policies;

use App\Models\Adherent;
use App\Models\User;

class AdherentPolicy
{
    /**
     * Liste complète des adhérents : réservée aux gestionnaires et admins.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    /**
     * Consultation d'une fiche : gestionnaire/admin, ou l'adhérent concerné.
     */
    public function view(User $user, Adherent $adherent): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])
            || $user->id === $adherent->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    /**
     * Modification complète de la fiche : réservée aux gestionnaires et admins.
     */
    public function update(User $user, Adherent $adherent): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    /**
     * Un adhérent peut mettre à jour la plupart des champs de sa propre
     * fiche (identité, coordonnées, photo...) depuis "Mon profil". Le
     * matricule, la date d'adhésion et le statut restent réservés à la
     * gestion (voir `update`) : ce sont des champs à portée réglementaire
     * (délai de carence, décisions administratives) qu'un adhérent ne
     * doit pas pouvoir modifier lui-même.
     */
    public function updateProfil(User $user, Adherent $adherent): bool
    {
        return $user->id === $adherent->user_id;
    }

    public function changerStatut(User $user, Adherent $adherent): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function importer(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    /**
     * Suppression définitive : réservée à l'admin, la cascade supprime
     * aussi les personnes à charge, cotisations, sinistres et paiements
     * de l'adhérent (voir Adherents/Index::supprimer et Adherents/Fiche::supprimer,
     * qui bloquent la suppression tant qu'un historique financier existe).
     */
    public function delete(User $user, Adherent $adherent): bool
    {
        return $user->hasRole('ADMIN');
    }
}
