<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Mes demandes de sinistre
            </h2>
            @if ($peutSoumettre)
                <a href="{{ route('mon-espace.sinistres.soumettre') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                    Nouvelle demande
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        @unless ($peutSoumettre)
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-4">
                <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm rounded-xl p-3">
                    Votre adhésion est en attente de validation par un gestionnaire : la soumission de nouvelles
                    demandes sera disponible une fois votre compte validé.
                </div>
            </div>
        @endunless
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-xl p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                @if ($demandes->isEmpty())
                    <p class="p-6 text-sm text-gray-500 dark:text-gray-400">Vous n'avez soumis aucune demande de sinistre.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Date de l'événement</th>
                                <th class="px-4 py-3">Montant demandé</th>
                                <th class="px-4 py-3">Statut</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($demandes as $d)
                                <tr wire:key="demande-{{ $d->id }}">
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100">{{ $d->typeSinistre->libelle }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $d->date_evenement->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ number_format($d->montant_demande, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'px-2 py-1 rounded-full text-xs font-medium',
                                            'bg-blue-100 text-blue-800' => in_array($d->statut->value, ['soumise', 'en_cours_examen']),
                                            'bg-green-100 text-green-800' => $d->statut->value === 'approuvee',
                                            'bg-red-100 text-red-800' => $d->statut->value === 'rejetee',
                                            'bg-yellow-100 text-yellow-800' => $d->statut->value === 'complement_demande',
                                        ])>
                                            {{ $d->statut->libelle() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('mon-espace.sinistres.fiche', $d) }}" wire:navigate class="text-primary-600 hover:underline">Voir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
