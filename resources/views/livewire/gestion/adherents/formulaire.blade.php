@php
    $sections = [
        'profil' => ['Profil et photo', 'Identité, coordonnées, photo', 'user'],
        'adhesion' => ['Adhésion et statut', 'Statut, dates, fin de carence', 'calendar-days'],
    ];
@endphp

<div>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <nav class="flex items-center gap-1.5 text-sm text-gray-500" aria-label="Fil d'Ariane">
                    <a href="{{ route('gestion.adherents.index') }}" wire:navigate class="hover:text-primary-600">Adhérents tuteurs</a>
                    @if ($adherent)
                        <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-300" />
                        <a href="{{ route('gestion.adherents.fiche', $adherent) }}" wire:navigate class="hover:text-primary-600">{{ $adherent->nomComplet() }}</a>
                    @endif
                    <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-300" />
                    <span class="text-gray-700">{{ $adherent ? 'Modifier' : 'Nouvel adhérent' }}</span>
                </nav>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">
                    {{ $adherent ? 'Modifier l\'adhérent tuteur' : 'Nouvel adhérent tuteur' }}
                </h2>
            </div>

            @if ($adherent)
                <a href="{{ route('gestion.adherents.fiche', $adherent) }}" wire:navigate
                   class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl shadow-sm hover:bg-gray-50 transition">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Retour à la fiche
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($adherent)
                {{-- ==================== MODIFICATION : une section à la fois ==================== --}}
                <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
                    <aside class="space-y-4">
                        <div class="flex items-center gap-3 bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                            @if ($adherent->photo_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($adherent->photo_path) }}" alt="" class="h-12 w-12 rounded-xl object-cover">
                            @else
                                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 text-sm font-bold text-primary-700">
                                    {{ strtoupper(mb_substr($adherent->prenom, 0, 1).mb_substr($adherent->nom, 0, 1)) }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900">{{ $adherent->nomComplet() }}</p>
                                <p class="text-xs text-gray-500">{{ $adherent->matricule }}</p>
                            </div>
                        </div>

                        <nav class="flex gap-2 overflow-x-auto lg:flex-col lg:overflow-visible" aria-label="Sections de la modification">
                            @foreach ($sections as $cle => [$libelle, $description, $icone])
                                <a href="{{ route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => $cle]) }}"
                                   wire:navigate
                                   @if ($section === $cle) aria-current="page" @endif
                                   @class([
                                       'flex shrink-0 items-center gap-3 rounded-2xl border p-3 transition lg:shrink',
                                       'border-primary-200 bg-primary-50' => $section === $cle,
                                       'border-gray-100 bg-white hover:bg-gray-50' => $section !== $cle,
                                   ])>
                                    <span @class([
                                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl',
                                        'bg-primary-500 text-white' => $section === $cle,
                                        'bg-gray-100 text-gray-500' => $section !== $cle,
                                    ])>
                                        <x-dynamic-component :component="'heroicon-o-'.$icone" class="w-5 h-5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span @class(['block text-sm font-semibold whitespace-nowrap lg:whitespace-normal', 'text-primary-800' => $section === $cle, 'text-gray-900' => $section !== $cle])>{{ $libelle }}</span>
                                        <span class="hidden lg:block text-xs text-gray-500">{{ $description }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </nav>

                        <p class="hidden lg:block px-1 text-xs text-gray-500">
                            Chaque section s'enregistre séparément : ce que vous modifiez dans l'une n'affecte pas les autres.
                            Le mot de passe se gère dans l'onglet « Sécurité » de la fiche.
                        </p>
                    </aside>

                    <div class="min-w-0 space-y-6">
                        @if (session('status'))
                            <div class="flex items-start gap-3 bg-green-50 text-green-800 text-sm rounded-xl p-4" role="status">
                                <x-heroicon-o-check-circle class="w-5 h-5 shrink-0 text-green-600" />
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        {{-- ---------------- Profil ---------------- --}}
                        @if ($section === 'profil')
                            <form wire:submit="enregistrerProfil">
                                <x-panel titre="Profil et photo" description="Ces informations apparaissent sur la fiche et sur la carte de membre." icone="user">
                                    <div class="space-y-8">
                                        @include('livewire.gestion.adherents.formulaire.photo')
                                        <hr class="border-gray-100">
                                        @include('livewire.gestion.adherents.formulaire.profil')
                                    </div>

                                    <x-slot:footer>
                                        <a href="{{ route('gestion.adherents.fiche', $adherent) }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</a>
                                        <x-primary-button>
                                            <span wire:loading.remove wire:target="enregistrerProfil">Enregistrer le profil</span>
                                            <span wire:loading wire:target="enregistrerProfil">Enregistrement…</span>
                                        </x-primary-button>
                                    </x-slot:footer>
                                </x-panel>
                            </form>
                        @endif

                        {{-- ---------------- Adhésion et statut ---------------- --}}
                        @if ($section === 'adhesion')
                            <form wire:submit="enregistrerAdhesion">
                                <x-panel titre="Adhésion et statut" description="Situation de l'adhérent vis-à-vis de la mutuelle. Un changement de statut est inscrit au journal d'audit." icone="calendar-days">
                                    @include('livewire.gestion.adherents.formulaire.adhesion')

                                    <x-slot:footer>
                                        <a href="{{ route('gestion.adherents.fiche', $adherent) }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</a>
                                        <x-primary-button>
                                            <span wire:loading.remove wire:target="enregistrerAdhesion">Enregistrer l'adhésion</span>
                                            <span wire:loading wire:target="enregistrerAdhesion">Enregistrement…</span>
                                        </x-primary-button>
                                    </x-slot:footer>
                                </x-panel>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                {{-- ==================== CRÉATION : un seul formulaire, en panneaux ==================== --}}
                <form wire:submit="enregistrer" class="space-y-6">
                    <x-panel titre="Profil et photo" description="Identité, coordonnées et situation professionnelle." icone="user">
                        <div class="space-y-8">
                            @include('livewire.gestion.adherents.formulaire.photo')
                            <hr class="border-gray-100">
                            @include('livewire.gestion.adherents.formulaire.profil')
                        </div>
                    </x-panel>

                    <x-panel titre="Adhésion et statut" description="Le matricule est généré automatiquement à partir de la date d'adhésion." icone="calendar-days">
                        @include('livewire.gestion.adherents.formulaire.adhesion')
                    </x-panel>

                    <x-panel titre="Accès à « Mon espace »" icone="lock-closed">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="creerCompteAcces" id="creerCompteAcces" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                            <span class="text-sm text-gray-700">
                                <span class="font-medium text-gray-900">Créer un compte d'accès</span><br>
                                Identifiant = matricule, mot de passe par défaut :
                                <span class="font-mono font-semibold">password</span>. Vous pourrez le
                                réinitialiser ensuite depuis l'onglet « Sécurité » de la fiche.
                            </span>
                        </label>
                    </x-panel>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a href="{{ route('gestion.adherents.index') }}" wire:navigate class="text-sm font-medium text-gray-600 hover:text-gray-900">Annuler</a>
                        <x-primary-button>Créer l'adhérent</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
