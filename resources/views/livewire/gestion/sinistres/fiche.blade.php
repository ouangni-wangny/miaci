<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Demande de {{ $demande->adherent->nomComplet() }} — {{ $demande->typeSinistre->libelle }}
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

                    @if ($demande->preresultat_eligibilite)
                        <span @class([
                            'px-2 py-1 rounded-full text-xs font-medium',
                            'bg-green-100 text-green-800' => $demande->preresultat_eligibilite->value === 'probablement_eligible',
                            'bg-orange-100 text-orange-800' => $demande->preresultat_eligibilite->value === 'probablement_non_eligible',
                        ])>
                            {{ $demande->preresultat_eligibilite->libelle() }}
                        </span>
                    @endif
                </div>

                @if ($demande->motif_preresultat)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ $demande->motif_preresultat }}</p>
                @endif

                @if ($dateLimiteDocuments || $dateLimiteTraitement)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if ($dateLimiteDocuments)
                            <span @class([
                                'px-2 py-1 rounded-full text-xs font-medium',
                                'bg-red-100 text-red-800' => $dateLimiteDocuments->isPast() && $pieces->isEmpty(),
                                'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400' => ! ($dateLimiteDocuments->isPast() && $pieces->isEmpty()),
                            ])>
                                Documents à fournir avant le {{ $dateLimiteDocuments->format('d/m/Y') }} (Article 7 : 21 jours)
                            </span>
                        @endif
                        @if ($dateLimiteTraitement)
                            <span @class([
                                'px-2 py-1 rounded-full text-xs font-medium',
                                'bg-orange-100 text-orange-800' => $dateLimiteTraitement->isPast(),
                                'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400' => ! $dateLimiteTraitement->isPast(),
                            ])>
                                À traiter avant le {{ $dateLimiteTraitement->format('d/m/Y') }} (Article 7 : délai de traitement de 2 semaines)
                            </span>
                        @endif
                    </div>
                @endif

                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Adhérent tuteur</dt><dd class="text-gray-900 dark:text-gray-100">{{ $demande->adherent->nomComplet() }} ({{ $demande->adherent->matricule }})</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Bénéficiaire</dt><dd class="text-gray-900 dark:text-gray-100">{{ $demande->nomBeneficiaire() }} — {{ $demande->beneficiaire_type->libelle() }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Date de l'événement</dt><dd class="text-gray-900 dark:text-gray-100">{{ $demande->date_evenement->format('d/m/Y') }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Montant demandé</dt><dd class="text-gray-900 dark:text-gray-100">{{ number_format($demande->montant_demande, 0, ',', ' ') }} FCFA</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Plafond du type</dt><dd class="text-gray-900 dark:text-gray-100">{{ number_format($demande->typeSinistre->plafond_montant, 0, ',', ' ') }} FCFA</dd></div>
                    @if ($demande->montant_accorde !== null)
                        <div><dt class="text-gray-500 dark:text-gray-400">Montant accordé</dt><dd class="text-green-600 font-medium">{{ number_format($demande->montant_accorde, 0, ',', ' ') }} FCFA</dd></div>
                    @endif
                </dl>

                <div class="mt-4">
                    <dt class="text-gray-500 dark:text-gray-400 text-sm">Description</dt>
                    <dd class="text-gray-900 dark:text-gray-100 text-sm mt-1">{{ $demande->description }}</dd>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pièces justificatives</h3>
                @if ($pieces->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune pièce jointe.</p>
                @else
                    <ul class="space-y-1 text-sm">
                        @foreach ($pieces as $piece)
                            <li class="flex items-center gap-2">
                                <a href="{{ route('pieces-justificatives.telecharger', $piece) }}" class="text-primary-600 hover:underline">
                                    {{ $piece->nom_original }}
                                </a>
                                @if ($piece->pieceRequise)
                                    <span class="text-xs text-gray-400">({{ $piece->pieceRequise->libelle }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @unless ($demande->statut->estFinale())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Décision</h3>

                    @if ($demande->statut->value === 'soumise')
                        <button wire:click="prendreEnCharge" class="mb-4 text-sm text-primary-600 hover:underline">
                            Marquer « en cours d'examen »
                        </button>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 mb-4">
                        <div>
                            <x-input-label for="montant_accorde" value="Montant accordé (FCFA)" />
                            <x-text-input type="number" wire:model="montant_accorde" id="montant_accorde" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('montant_accorde')" class="mt-2" />
                            @if ($deductionCotisation > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Montant pré-rempli après déduction de {{ number_format($deductionCotisation, 0, ',', ' ') }} FCFA
                                    de cotisation du mois en cours (Article 7), à ajuster si besoin.
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="mb-4">
                        <x-input-label for="motif_decision" value="Motif / commentaire" />
                        <textarea wire:model="motif_decision" id="motif_decision" rows="3" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm"></textarea>
                        <x-input-error :messages="$errors->get('motif_decision')" class="mt-2" />
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button wire:click="approuver" wire:confirm="Confirmer l'approbation de cette demande ?"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-green-600 transition">
                            Approuver
                        </button>
                        <button wire:click="rejeter" wire:confirm="Confirmer le rejet de cette demande ?"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-red-600 transition">
                            Rejeter
                        </button>
                        <button wire:click="demanderComplement"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Demander un complément
                        </button>
                    </div>
                </div>
            @else
                @if ($demande->motif_decision)
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Motif de la décision</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $demande->motif_decision }}</p>
                    </div>
                @endif
            @endunless
        </div>
    </div>
</div>
