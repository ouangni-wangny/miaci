<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ $typeSinistre ? 'Modifier le type de sinistre' : 'Nouveau type de sinistre' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <form wire:submit="enregistrer" class="space-y-6">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="code" value="Code" />
                            <x-text-input wire:model="code" id="code" class="mt-1 block w-full" placeholder="DECES, HOSPITALISATION..." />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="libelle" value="Libellé" />
                            <x-text-input wire:model="libelle" id="libelle" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('libelle')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="description" value="Description (optionnel)" />
                            <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="plafond_montant" value="Plafond de prise en charge (FCFA)" />
                            <x-text-input type="number" wire:model="plafond_montant" id="plafond_montant" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('plafond_montant')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="delai_carence_mois" value="Délai de carence (mois)" />
                            <x-text-input type="number" wire:model="delai_carence_mois" id="delai_carence_mois" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('delai_carence_mois')" class="mt-2" />
                        </div>
                        <div class="flex items-center gap-2 mt-6">
                            <input type="checkbox" wire:model="cotisation_a_jour_requise" id="cotisation_a_jour_requise" class="rounded border-gray-300 text-primary-600 shadow-sm">
                            <label for="cotisation_a_jour_requise" class="text-sm text-gray-700 dark:text-gray-300">Cotisation à jour exigée</label>
                        </div>
                        <div class="flex items-center gap-2 mt-6">
                            <input type="checkbox" wire:model="actif" id="actif" class="rounded border-gray-300 text-primary-600 shadow-sm">
                            <label for="actif" class="text-sm text-gray-700 dark:text-gray-300">Type actif (proposé aux adhérents)</label>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label value="Pièces justificatives requises" />
                            <button type="button" wire:click="ajouterPiece" class="text-sm text-primary-600 hover:underline">+ Ajouter une pièce</button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($pieces as $index => $piece)
                                <div class="flex items-center gap-3" wire:key="piece-{{ $index }}">
                                    <input type="text" wire:model="pieces.{{ $index }}.libelle" placeholder="Ex. Acte de décès"
                                           class="flex-1 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm text-sm">
                                    <label class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        <input type="checkbox" wire:model="pieces.{{ $index }}.obligatoire" class="rounded border-gray-300 text-primary-600 shadow-sm">
                                        Obligatoire
                                    </label>
                                    <button type="button" wire:click="retirerPiece({{ $index }})" class="text-red-600 hover:underline text-sm">Retirer</button>
                                </div>
                                <x-input-error :messages="$errors->get('pieces.'.$index.'.libelle')" class="mt-1" />
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                        <a href="{{ route('gestion.parametres.types-sinistre.index') }}" wire:navigate class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
