{{-- Champs du profil : identité, coordonnées, situation professionnelle
     (partagés création / modification). --}}
<div class="space-y-8">
    <div>
        <h4 class="text-sm font-semibold text-gray-900">Identité</h4>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
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
                <x-select-input wire:model="sexe" id="sexe" class="mt-1 block w-full">
                    @foreach ($sexes as $s)
                        <option value="{{ $s->value }}">{{ $s->libelle() }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('sexe')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="date_naissance" value="Date de naissance" />
                <x-text-input type="date" wire:model="date_naissance" id="date_naissance" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('date_naissance')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-gray-900">Coordonnées</h4>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
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
        </div>
    </div>

    <div>
        <h4 class="text-sm font-semibold text-gray-900">Situation professionnelle</h4>
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
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
    </div>
</div>
