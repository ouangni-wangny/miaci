<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
}; ?>

<div>
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">Mot de passe oublié</h2>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        Pour des raisons de sécurité, la réinitialisation du mot de passe n'est pas automatique. Contactez
        l'administrateur de la mutuelle (avec votre matricule) : il vous attribuera un nouveau mot de passe.
    </div>

    <a href="{{ route('login') }}" wire:navigate
       class="mt-6 inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
        Retour à la connexion
    </a>
</div>
