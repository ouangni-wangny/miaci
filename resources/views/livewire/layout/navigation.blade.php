<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-30 w-64 bg-secondary-900 text-white flex flex-col transform transition-transform duration-200 lg:relative lg:translate-x-0 flex-shrink-0"
>
    <!-- Logo -->
    <div class="flex items-center gap-3 px-6 py-5 border-b border-secondary-800">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
            <span class="flex items-center justify-center w-14 h-14 rounded-xl bg-white p-1.5 flex-shrink-0 overflow-hidden">
                <img src="{{ asset('images/logo.png') }}" alt="MIACI" class="w-full h-full object-contain">
            </span>
            <div>
                <p class="font-bold text-sm leading-tight">MIACI</p>
                <p class="text-xs text-secondary-300 leading-tight">Instituteurs et Assimilés</p>
            </div>
        </a>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto" @click="sidebarOpen = false">
        <a href="{{ route('dashboard') }}" wire:navigate
           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
            <x-icon name="dashboard" class="w-5 h-5 flex-shrink-0" />
            Tableau de bord
        </a>

        @hasanyrole('ADMIN|GESTIONNAIRE')
            <a href="{{ route('gestion.adherents.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.adherents.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="users" class="w-5 h-5 flex-shrink-0" />
                Adhérents tuteurs
            </a>
            <a href="{{ route('gestion.cotisations.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.cotisations.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="cash" class="w-5 h-5 flex-shrink-0" />
                Cotisations
            </a>
            <a href="{{ route('gestion.paiements.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.paiements.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="card" class="w-5 h-5 flex-shrink-0" />
                Paiements
            </a>
            <a href="{{ route('gestion.dons-fin-annee.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.dons-fin-annee.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="cash" class="w-5 h-5 flex-shrink-0" />
                Dons de fin d'année
            </a>
            <a href="{{ route('gestion.bilan.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.bilan.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="list" class="w-5 h-5 flex-shrink-0" />
                Bilan annuel
            </a>
            <a href="{{ route('gestion.sinistres.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.sinistres.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="alert" class="w-5 h-5 flex-shrink-0" />
                Sinistres
            </a>
        @endhasanyrole

        @role('ADHERENT')
            <a href="{{ route('mon-espace.personnes-a-charge') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('mon-espace.personnes-a-charge') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="user-group" class="w-5 h-5 flex-shrink-0" />
                Personnes à charge
            </a>
            <a href="{{ route('mon-espace.cotisations') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('mon-espace.cotisations') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="cash" class="w-5 h-5 flex-shrink-0" />
                Cotisations
            </a>
            <a href="{{ route('mon-espace.sinistres.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('mon-espace.sinistres.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="alert" class="w-5 h-5 flex-shrink-0" />
                Sinistres
            </a>
            <a href="{{ route('mon-espace.profil') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('mon-espace.profil') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="user" class="w-5 h-5 flex-shrink-0" />
                Mon profil
            </a>
        @endrole

        @role('ADMIN')
            <p class="px-3 pt-5 pb-1 text-xs font-semibold text-secondary-400 uppercase tracking-wide">Administration</p>
            <a href="{{ route('gestion.parametres.cotisation') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.parametres.cotisation') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="sliders" class="w-5 h-5 flex-shrink-0" />
                Paramètres cotisation
            </a>
            <a href="{{ route('gestion.parametres.site') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.parametres.site') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="edit" class="w-5 h-5 flex-shrink-0" />
                Contenu du site
            </a>
            <a href="{{ route('gestion.parametres.types-sinistre.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.parametres.types-sinistre.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="alert" class="w-5 h-5 flex-shrink-0" />
                Types de sinistre
            </a>
            <a href="{{ route('gestion.audit.index') }}" wire:navigate
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('gestion.audit.*') ? 'bg-primary-500 text-white' : 'text-secondary-200 hover:bg-secondary-800 hover:text-white' }}">
                <x-icon name="list" class="w-5 h-5 flex-shrink-0" />
                Journal d'audit
            </a>
        @endrole
    </nav>

    <!-- User -->
    <div class="border-t border-secondary-800 p-4">
        <p class="text-xs text-secondary-300 mb-1">Connecté en tant que</p>
        <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
        <div class="flex items-center gap-4 mt-3">
            @unless (auth()->user()->hasRole('ADHERENT'))
                <a href="{{ route('profile') }}" wire:navigate class="text-xs text-secondary-300 hover:text-white transition">
                    Mon profil
                </a>
            @endunless
            <button wire:click="logout" class="flex items-center gap-1 text-red-400 hover:text-red-300 text-xs transition">
                <x-icon name="logout" class="w-3.5 h-3.5" />
                Déconnexion
            </button>
        </div>
    </div>
</aside>
