<?php

namespace App\Livewire\Concerns;

use App\Services\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Réinitialisation, par un gestionnaire, du mot de passe de connexion d'un
 * adhérent (onglet « Sécurité » de la fiche). Le composant qui l'utilise doit
 * exposer un `$adherent` (App\Models\Adherent, éventuellement nul).
 *
 * Le mot de passe saisi est visible pendant la saisie — besoin métier : le
 * gestionnaire le communique à l'adhérent — mais il est effacé dès
 * l'enregistrement et jamais écrit dans le journal d'audit.
 */
trait ReinitialiseMotDePasse
{
    public string $nouveau_mot_de_passe = '';

    /**
     * Le champ est obligatoire ici : c'est le seul but de l'opération.
     */
    public function enregistrerSecurite(AuditLogger $audit): void
    {
        abort_unless($this->adherent, 404);
        $this->authorize('update', $this->adherent);

        abort_unless($this->adherent->user_id, 422, "Cet adhérent n'a pas de compte d'accès.");

        $valides = $this->validate(
            ['nouveau_mot_de_passe' => ['required', 'string', 'min:6']],
            [],
            ['nouveau_mot_de_passe' => 'mot de passe'],
        );

        $this->appliquerSecurite($valides['nouveau_mot_de_passe'], $audit);

        session()->flash('status', "Mot de passe réinitialisé. Communiquez-le à l'adhérent : il n'est plus affiché.");
    }

    /** Propose un mot de passe aléatoire (lettres et chiffres, faciles à dicter). */
    public function genererMotDePasse(): void
    {
        abort_unless($this->adherent, 404);
        $this->authorize('update', $this->adherent);

        $this->nouveau_mot_de_passe = Str::password(10, symbols: false);
        $this->resetValidation('nouveau_mot_de_passe');
    }

    private function appliquerSecurite(?string $nouveauMotDePasse, AuditLogger $audit): void
    {
        if (! $nouveauMotDePasse || ! $this->adherent->user_id) {
            return;
        }

        $this->adherent->user->update(['password' => Hash::make($nouveauMotDePasse)]);

        // Trace de l'opération, jamais du mot de passe lui-même.
        $audit->log('adherent.mot_de_passe_reinitialise', $this->adherent, null, null, 'Mot de passe de connexion réinitialisé par un gestionnaire.');

        $this->nouveau_mot_de_passe = '';
    }
}
