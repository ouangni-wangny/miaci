<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Nouvelle demande de prise en charge
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <form wire:submit="enregistrer" class="space-y-6">
                    <div>
                        <x-input-label for="type_sinistre_id" value="Type de sinistre" />
                        <select wire:model.live="type_sinistre_id" id="type_sinistre_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                            <option value="">Sélectionner...</option>
                            @foreach ($types as $t)
                                <option value="{{ $t->id }}">{{ $t->libelle }} (plafond {{ number_format($t->plafond_montant, 0, ',', ' ') }} FCFA)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type_sinistre_id')" class="mt-2" />
                        @if ($typeSelectionne?->description)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $typeSelectionne->description }}</p>
                        @endif
                    </div>

                    <div>
                        <x-input-label value="Bénéficiaire" />
                        <div class="mt-1 flex gap-4">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" wire:model.live="beneficiaire_type" value="adherent" class="text-primary-600">
                                Moi-même
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" wire:model.live="beneficiaire_type" value="personne_a_charge" class="text-primary-600">
                                Une personne à charge
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('beneficiaire_type')" class="mt-2" />
                    </div>

                    @if ($beneficiaire_type === 'personne_a_charge')
                        <div>
                            <x-input-label for="personne_a_charge_id" value="Personne à charge" />
                            <select wire:model="personne_a_charge_id" id="personne_a_charge_id" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                                <option value="">Sélectionner...</option>
                                @foreach ($personnesACharge as $p)
                                    <option value="{{ $p->id }}">{{ $p->nomComplet() }} ({{ $p->lien_parente }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('personne_a_charge_id')" class="mt-2" />
                            @if ($personnesACharge->isEmpty())
                                <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">
                                    Aucune personne à charge validée. Ajoutez-en une depuis « Personnes à charge » ; elle doit être validée par un gestionnaire avant de pouvoir être sélectionnée ici.
                                </p>
                            @endif
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="date_evenement" value="Date de l'événement" />
                            <x-text-input type="date" wire:model="date_evenement" id="date_evenement" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('date_evenement')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="montant_demande" value="Montant demandé (FCFA)" />
                            <x-text-input type="number" wire:model="montant_demande" id="montant_demande" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('montant_demande')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Description des circonstances" />
                        <textarea wire:model="description" id="description" rows="4" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    @if ($typeSelectionne && $typeSelectionne->piecesRequises->isNotEmpty())
                        <div>
                            <x-input-label value="Pièces justificatives" />
                            <div class="mt-2 space-y-3">
                                @foreach ($typeSelectionne->piecesRequises as $piece)
                                    <div>
                                        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
                                            {{ $piece->libelle }}
                                            @if ($piece->obligatoire) <span class="text-red-500">*</span> @endif
                                        </label>
                                        <input type="file" wire:model="fichiers.{{ $piece->id }}" class="block w-full text-sm text-gray-700 dark:text-gray-300">
                                        <x-input-error :messages="$errors->get('fichiers.'.$piece->id)" class="mt-1" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center gap-4">
                        <x-primary-button wire:loading.attr="disabled">Soumettre la demande</x-primary-button>
                        <a href="{{ route('mon-espace.sinistres.index') }}" wire:navigate class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
