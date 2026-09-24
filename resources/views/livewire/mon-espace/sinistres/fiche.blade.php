<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Demande de sinistre — {{ $demande->typeSinistre->libelle }}
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
                    <span @class([
                        'px-2 py-1 rounded-full text-xs font-medium',
                        'bg-blue-100 text-blue-800' => in_array($demande->statut->value, ['soumise', 'en_cours_examen']),
                        'bg-green-100 text-green-800' => $demande->statut->value === 'approuvee',
                        'bg-red-100 text-red-800' => $demande->statut->value === 'rejetee',
                        'bg-yellow-100 text-yellow-800' => $demande->statut->value === 'complement_demande',
                    ])>
                        {{ $demande->statut->libelle() }}
                    </span>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Bénéficiaire</dt><dd class="text-gray-900 dark:text-gray-100">{{ $demande->nomBeneficiaire() }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Date de l'événement</dt><dd class="text-gray-900 dark:text-gray-100">{{ $demande->date_evenement->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Montant demandé</dt><dd class="text-gray-900 dark:text-gray-100">{{ number_format($demande->montant_demande, 0, ',', ' ') }} FCFA</dd></div>
                    @if ($demande->montant_accorde !== null)
                        <div><dt class="text-gray-500 dark:text-gray-400">Montant accordé</dt><dd class="text-green-600 font-medium">{{ number_format($demande->montant_accorde, 0, ',', ' ') }} FCFA</dd></div>
                    @endif
                </dl>

                <div class="mt-4">
                    <dt class="text-gray-500 dark:text-gray-400 text-sm">Description</dt>
                    <dd class="text-gray-900 dark:text-gray-100 text-sm mt-1">{{ $demande->description }}</dd>
                </div>

                @if ($demande->motif_decision)
                    <div class="mt-4">
                        <dt class="text-gray-500 dark:text-gray-400 text-sm">Commentaire de la mutuelle</dt>
                        <dd class="text-gray-900 dark:text-gray-100 text-sm mt-1">{{ $demande->motif_decision }}</dd>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pièces justificatives fournies</h3>
                @if ($pieces->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune pièce jointe.</p>
                @else
                    <ul class="space-y-1 text-sm">
                        @foreach ($pieces as $piece)
                            <li>
                                <a href="{{ route('pieces-justificatives.telecharger', $piece) }}" class="text-primary-600 hover:underline">
                                    {{ $piece->nom_original }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($demande->statut->value === 'complement_demande')
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Ajouter une pièce complémentaire</h3>
                    <form wire:submit="ajouterComplement" class="space-y-4">
                        <div>
                            <x-input-label for="libelleComplement" value="Nature de la pièce" />
                            <x-text-input wire:model="libelleComplement" id="libelleComplement" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('libelleComplement')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="fichierComplement" value="Fichier" />
                            <input type="file" wire:model="fichierComplement" id="fichierComplement" class="block w-full text-sm text-gray-700 dark:text-gray-300">
                            <x-input-error :messages="$errors->get('fichierComplement')" class="mt-2" />
                        </div>
                        <x-primary-button>Envoyer</x-primary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
