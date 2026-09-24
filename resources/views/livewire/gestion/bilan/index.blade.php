<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Bilan annuel
            </h2>
            <select wire:model.live="annee" class="rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm text-sm">
                @foreach ($annees as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Le suivi financier de la mutuelle dans l'application démarre en janvier 2026 : les années
                antérieures n'auront jamais de données. La comparaison d'une année sur l'autre ne devient
                pleinement pertinente qu'à partir de la deuxième année suivie.
            </p>

            <!-- Cotisations : prévisionnel vs réalisé -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Cotisations {{ $bilan['annee'] }} — Prévisionnel vs réalisé</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Prévisionnel calculé selon la formule du règlement (nombre de cotisants × montant, mois par
                    mois), y compris les adhérents radiés depuis.
                </p>

                @if ($bilan['tauxRecouvrement'] !== null)
                    <div class="flex items-end justify-between mb-2">
                        <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $bilan['tauxRecouvrement'] }}%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ number_format($bilan['cotisationsRealisees'], 0, ',', ' ') }} / {{ number_format($bilan['cotisationsAttendues'], 0, ',', ' ') }} FCFA
                        </p>
                    </div>
                    <div class="w-full h-2.5 rounded-full bg-gray-100 dark:bg-gray-900 overflow-hidden">
                        <div class="h-full rounded-full {{ $bilan['tauxRecouvrement'] >= 80 ? 'bg-green-500' : ($bilan['tauxRecouvrement'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                             style="width: {{ min(100, $bilan['tauxRecouvrement']) }}%"></div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune donnée pour {{ $bilan['annee'] }}.</p>
                @endif

                <div class="grid sm:grid-cols-2 gap-4 mt-5 pt-5 border-t border-gray-100 dark:border-gray-700 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Réalisé {{ $bilan['annee'] - 1 }}</p>
                        <p class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($bilanPrecedent['cotisationsRealisees'], 0, ',', ' ') }} FCFA</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Évolution vs {{ $bilan['annee'] - 1 }}</p>
                        @if ($bilanPrecedent['cotisationsRealisees'] > 0)
                            @php $evolution = round((($bilan['cotisationsRealisees'] - $bilanPrecedent['cotisationsRealisees']) / $bilanPrecedent['cotisationsRealisees']) * 100); @endphp
                            <p class="font-medium {{ $evolution >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $evolution >= 0 ? '+' : '' }}{{ $evolution }}%</p>
                        @else
                            <p class="text-gray-400">—</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Comparatif des autres indicateurs -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <h3 class="font-semibold text-gray-800 dark:text-gray-200 p-6 pb-0">Comparatif {{ $bilan['annee'] }} vs {{ $bilan['annee'] - 1 }}</h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm mt-4">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <th class="px-6 py-2">Indicateur</th>
                            <th class="px-6 py-2 text-right">{{ $bilan['annee'] - 1 }}</th>
                            <th class="px-6 py-2 text-right">{{ $bilan['annee'] }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">Droit d'adhésion encaissé</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">{{ number_format($bilanPrecedent['droitAdhesionEncaisse'], 0, ',', ' ') }} F</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">{{ number_format($bilan['droitAdhesionEncaisse'], 0, ',', ' ') }} F</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">Nouvelles adhésions</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">{{ $bilanPrecedent['nouvellesAdhesions'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">{{ $bilan['nouvellesAdhesions'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">Demandes de sinistre soumises</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">{{ $bilanPrecedent['sinistresSoumis'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">{{ $bilan['sinistresSoumis'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">Demandes de sinistre approuvées</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">{{ $bilanPrecedent['sinistresApprouves'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">{{ $bilan['sinistresApprouves'] }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">Montant versé en assistances</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">{{ number_format($bilanPrecedent['montantVerseSinistres'], 0, ',', ' ') }} F</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">{{ number_format($bilan['montantVerseSinistres'], 0, ',', ' ') }} F</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">Dons de fin d'année versés</td>
                            <td class="px-6 py-3 text-right text-gray-500 dark:text-gray-400">{{ $bilanPrecedent['donsVerses'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-gray-100 font-medium">{{ $bilan['donsVerses'] }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="h-6"></div>
            </div>
        </div>
    </div>
</div>
