<div>
    <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-2">Créer mon compte adhérent</h2>
    <div class="mb-6 text-sm text-gray-600 dark:text-gray-400">
        Votre adhésion sera vérifiée par un gestionnaire de la mutuelle avant que vous puissiez
        soumettre une demande de sinistre ou payer une cotisation en ligne.
    </div>

    <form wire:submit="inscrire" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="nom" value="Nom" />
                <x-text-input wire:model="nom" id="nom" class="mt-1 block w-full" required autofocus />
                <x-input-error :messages="$errors->get('nom')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="prenom" value="Prénom" />
                <x-text-input wire:model="prenom" id="prenom" class="mt-1 block w-full" required />
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
                <x-text-input type="date" wire:model="date_naissance" id="date_naissance" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="telephone" value="Téléphone" />
                <x-text-input wire:model="telephone" id="telephone" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input type="email" wire:model="email" id="email" class="mt-1 block w-full" required autocomplete="username" />
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
            <div>
                <x-input-label for="password" value="Mot de passe" />
                <x-password-input  wire:model="password" id="password" class="mt-1 block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
                <x-password-input  wire:model="password_confirmation" id="password_confirmation" class="mt-1 block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100" href="{{ route('login') }}" wire:navigate>
                Déjà un compte ?
            </a>
            <x-primary-button>S'inscrire</x-primary-button>
        </div>
    </form>
</div>
