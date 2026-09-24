<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Mon profil adhérent
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-xl p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informations de la mutuelle</h3>
                    <a href="{{ route('adherents.carte', $adherent) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 border border-transparent rounded-xl font-semibold text-xs text-white hover:bg-primary-600 transition">
                        Télécharger ma carte de membre
                    </a>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Champs à portée réglementaire, gérés par le secrétariat de la mutuelle. Contactez un gestionnaire pour toute correction.
                </p>
                <dl class="grid gap-4 sm:grid-cols-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Matricule</dt><dd class="text-gray-900 dark:text-gray-100">{{ $adherent->matricule }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Date d'adhésion</dt><dd class="text-gray-900 dark:text-gray-100">{{ $adherent->date_adhesion->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Statut</dt><dd class="text-gray-900 dark:text-gray-100">{{ $adherent->statut->libelle() }}</dd></div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Mes informations</h3>
                <form wire:submit="enregistrer" class="space-y-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="nom" value="Nom" />
                            <x-text-input wire:model="nom" id="nom" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('nom')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="prenom" value="Prénom" />
                            <x-text-input wire:model="prenom" id="prenom" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('prenom')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="sexe" value="Sexe" />
                            <select wire:model="sexe" id="sexe" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                                @foreach ($sexes as $s)
                                    <option value="{{ $s->value }}">{{ $s->libelle() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date_naissance" value="Date de naissance" />
                            <x-text-input type="date" wire:model="date_naissance" id="date_naissance" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="telephone" value="Téléphone" />
                            <x-text-input wire:model="telephone" id="telephone" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input type="email" wire:model="email" id="email" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="etablissement" value="Établissement" />
                            <x-text-input wire:model="etablissement" id="etablissement" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('etablissement')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="ville" value="Ville" />
                            <x-text-input wire:model="ville" id="ville" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('ville')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fonction" value="Fonction" />
                            <x-text-input wire:model="fonction" id="fonction" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('fonction')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="photo" value="Photo (optionnel)" />
                        @if ($adherent->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($adherent->photo_path) }}" alt="Photo actuelle" class="mt-2 mb-2 h-16 w-16 rounded-full object-cover">
                        @endif
                        <input type="file" wire:model="photo" id="photo" accept="image/*" class="block w-full text-sm text-gray-700 dark:text-gray-300">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Format portrait, au moins 600 x 750 pixels (minimum accepté : 300 x 370), visage bien
                            centré, pour un rendu net sur la carte de membre imprimée.
                        </p>
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                    </div>

                    <x-primary-button>Enregistrer</x-primary-button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Mon ayant droit</h3>
                    @if (! $formulaireAyantDroitOuvert && ! $adherent->aUnAyantDroit())
                        <button wire:click="ouvrirFormulaireAyantDroit" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 border border-transparent rounded-xl font-semibold text-xs text-white hover:bg-primary-600 transition">
                            Ajouter
                        </button>
                    @endif
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    La personne à qui remettre tout ce que vous avez cotisé en cas de décès ou de disparition.
                </p>

                @if ($formulaireAyantDroitOuvert)
                    <form wire:submit="enregistrerAyantDroit" class="space-y-4 max-w-md">
                        <div>
                            <x-input-label for="ayant_droit_nom" value="Nom complet" />
                            <x-text-input wire:model="ayant_droit_nom" id="ayant_droit_nom" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('ayant_droit_nom')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="ayant_droit_telephone" value="Téléphone" />
                            <x-text-input wire:model="ayant_droit_telephone" id="ayant_droit_telephone" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('ayant_droit_telephone')" class="mt-2" />
                        </div>
                        <div class="flex items-center gap-4">
                            <x-primary-button>Enregistrer</x-primary-button>
                            <button type="button" wire:click="fermerFormulaireAyantDroit" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Annuler</button>
                        </div>
                    </form>
                @elseif ($adherent->aUnAyantDroit())
                    <dl class="grid gap-4 sm:grid-cols-2 text-sm mb-4">
                        <div><dt class="text-gray-500 dark:text-gray-400">Nom complet</dt><dd class="text-gray-900 dark:text-gray-100">{{ $adherent->ayant_droit_nom }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Téléphone</dt><dd class="text-gray-900 dark:text-gray-100">{{ $adherent->ayant_droit_telephone }}</dd></div>
                    </dl>
                    <div class="flex items-center gap-4 text-sm">
                        <button wire:click="ouvrirFormulaireAyantDroit" class="text-primary-600 hover:underline">Modifier</button>
                        <button wire:click="supprimerAyantDroit" wire:confirm="Retirer votre ayant droit ?" class="text-red-600 hover:underline">Supprimer</button>
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Aucun ayant droit renseigné.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Changer mon mot de passe</h3>
                <form wire:submit="changerMotDePasse" class="space-y-4 max-w-md">
                    <div>
                        <x-input-label for="mot_de_passe_actuel" value="Mot de passe actuel" />
                        <x-password-input  wire:model="mot_de_passe_actuel" id="mot_de_passe_actuel" class="mt-1 block w-full" autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('mot_de_passe_actuel')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nouveau_mot_de_passe" value="Nouveau mot de passe" />
                        <x-password-input  wire:model="nouveau_mot_de_passe" id="nouveau_mot_de_passe" class="mt-1 block w-full" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('nouveau_mot_de_passe')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nouveau_mot_de_passe_confirmation" value="Confirmer le nouveau mot de passe" />
                        <x-password-input  wire:model="nouveau_mot_de_passe_confirmation" id="nouveau_mot_de_passe_confirmation" class="mt-1 block w-full" autocomplete="new-password" />
                    </div>

                    <x-primary-button>Changer le mot de passe</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</div>
