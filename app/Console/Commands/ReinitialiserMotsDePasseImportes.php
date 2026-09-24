<?php

namespace App\Console\Commands;

use App\Models\Adherent;
use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Réinitialise le mot de passe des comptes créés lors de l'import CSV
 * (matricule MIACI-*) à une valeur par défaut unique, en attendant que
 * chaque adhérent le change lui-même depuis "Mon profil". Les comptes
 * auto-inscrits (matricule PROV-*) ne sont pas touchés : ces adhérents
 * ont déjà choisi leur propre mot de passe à l'inscription.
 */
class ReinitialiserMotsDePasseImportes extends Command
{
    protected $signature = 'adherents:mot-de-passe-defaut
        {--mot-de-passe=Password : Le mot de passe par défaut à appliquer}
        {--dry-run : Compte les comptes concernés sans rien modifier}';

    protected $description = "Réinitialise le mot de passe des comptes adhérents importés (MIACI-*) à une valeur par défaut commune";

    public function handle(AuditLogger $audit): int
    {
        $motDePasse = (string) $this->option('mot-de-passe');
        $dryRun = (bool) $this->option('dry-run');

        $adherents = Adherent::where('matricule', 'like', 'MIACI-%')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        if ($dryRun) {
            $this->info("Dry-run : {$adherents->count()} comptes seraient réinitialisés au mot de passe par défaut.");

            return self::SUCCESS;
        }

        $hash = Hash::make($motDePasse);
        $compteur = 0;

        DB::transaction(function () use ($adherents, $hash, $audit, &$compteur) {
            foreach ($adherents as $adherent) {
                $adherent->user->update(['password' => $hash]);
                $audit->log('adherent.mot_de_passe_reinitialise_masse', $adherent);
                $compteur++;
            }
        });

        $this->info("{$compteur} comptes réinitialisés au mot de passe par défaut « {$motDePasse} ».");

        return self::SUCCESS;
    }
}
