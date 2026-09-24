<?php

namespace App\Console\Commands;

use App\Models\Adherent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Réinitialise le mot de passe de tous les comptes adhérents à la valeur par
 * défaut (voir User::MOT_DE_PASSE_PAR_DEFAUT). Utile pour ramener au même
 * mot de passe connu tous les comptes existants (identifiant = matricule).
 */
class RegenererMotsDePasseAdherents extends Command
{
    protected $signature = 'adherents:regenerer-mots-de-passe
        {--dry-run : Analyse sans écrire en base}';

    protected $description = 'Réinitialise le mot de passe de tous les comptes adhérents à la valeur par défaut';

    public function handle(): int
    {
        $adherents = Adherent::whereNotNull('user_id')->with('user')->get();

        if ($adherents->isEmpty()) {
            $this->error('Aucun adhérent avec compte de connexion trouvé.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info("Dry-run : {$adherents->count()} comptes seraient réinitialisés au mot de passe par défaut. Rien n'a été écrit.");

            return self::SUCCESS;
        }

        foreach ($adherents as $adherent) {
            $adherent->user->update(['password' => Hash::make(User::MOT_DE_PASSE_PAR_DEFAUT)]);
        }

        $this->info("{$adherents->count()} comptes réinitialisés au mot de passe par défaut (\"".User::MOT_DE_PASSE_PAR_DEFAUT.'").');

        return self::SUCCESS;
    }
}
