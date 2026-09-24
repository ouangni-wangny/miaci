@props(['disabled' => false])

{{-- Champ de mot de passe avec bouton pour afficher / masquer la saisie.
     À utiliser à la place de <x-text-input type="password"> ; les attributs
     (wire:model, id, autocomplete…) sont transmis au <input>.

     <x-password-input wire:model="password" id="password" autocomplete="new-password" />

     Le bouton n'est pas dans l'ordre de tabulation (tabindex -1) : on passe du
     champ au bouton « Se connecter » sans détour. Il reste actionnable à la souris
     et au toucher, et annonce son état aux lecteurs d'écran (aria-pressed). --}}
<div x-data="{ visible: false }" class="relative">
    <input @disabled($disabled)
           x-bind:type="visible ? 'text' : 'password'"
           type="password"
           {{ $attributes->merge(['class' => 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm py-2.5 pr-12']) }}>

    <button type="button"
            tabindex="-1"
            x-on:click="visible = ! visible"
            x-bind:aria-pressed="visible.toString()"
            x-bind:aria-label="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
            x-bind:title="visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
            aria-label="Afficher le mot de passe"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300">
        <x-heroicon-o-eye x-show="! visible" class="w-5 h-5" />
        <x-heroicon-o-eye-slash x-show="visible" x-cloak class="w-5 h-5" />
    </button>
</div>
