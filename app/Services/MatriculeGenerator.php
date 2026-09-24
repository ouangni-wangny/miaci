<?php

namespace App\Services;

use App\Models\Adherent;
use Carbon\Carbon;

/**
 * Génère le matricule d'un adhérent selon la nomenclature choisie par la
 * mutuelle : {année d'adhésion}-MIACI-{jour}{mois}A[rang], par exemple
 * "2021-MIACI-2604A" pour une adhésion le 26/04/2021. Le préfixe par
 * l'année d'adhésion permet de repérer les adhérents les plus anciens
 * d'un simple coup d'œil sur la liste triée par matricule.
 *
 * Quand plusieurs adhérents rejoignent la mutuelle le même jour, un
 * chiffre est ajouté après le "A" pour les différencier (le premier
 * n'en porte pas, le deuxième porte "A2", le troisième "A3", etc.).
 */
class MatriculeGenerator
{
    public function pourNouvelAdherent(Carbon $dateAdhesion): string
    {
        $dejaPresents = Adherent::whereDate('date_adhesion', $dateAdhesion)->count();

        return $this->construire($dateAdhesion, $dejaPresents + 1);
    }

    public function construire(Carbon $dateAdhesion, int $rang): string
    {
        $base = sprintf('%d-MIACI-%s%sA', $dateAdhesion->year, $dateAdhesion->format('d'), $dateAdhesion->format('m'));

        return $rang > 1 ? $base.$rang : $base;
    }
}
