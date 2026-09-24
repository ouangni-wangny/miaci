<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Mes cotisations
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <dl class="grid gap-4 sm:grid-cols-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Total dû</dt><dd class="text-gray-900 dark:text-gray-100 text-lg font-medium">{{ number_format($solde['du'], 0, ',', ' ') }} FCFA</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Total payé</dt><dd class="text-gray-900 dark:text-gray-100 text-lg font-medium">{{ number_format($solde['paye'], 0, ',', ' ') }} FCFA</dd></div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Reste à payer</dt>
                        <dd class="text-lg font-medium {{ $estAJour ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($solde['reste'], 0, ',', ' ') }} FCFA
                            @unless ($estAJour)
                                <span class="block text-xs font-normal text-red-500">Arriéré sur un ou plusieurs mois échus</span>
                            @endunless
                        </dd>
                    </div>
                </dl>
                @if (! $solde['parametre'])
                    <p class="mt-4 text-sm text-yellow-600 dark:text-yellow-400">
                        Le paramétrage de cotisation n'a pas encore été défini par la mutuelle.
                    </p>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Droit d'adhésion</p>
                    <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                        <div class="flex items-center gap-2">
                            <span>Vous</span>
                            @if ($adherent->droitAdhesionPaye())
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payé</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Impayé</span>
                            @endif
                        </div>
                        @foreach ($personnesACharge as $personne)
                            <div class="flex items-center gap-2">
                                <span>{{ $personne->nomComplet() }}</span>
                                @if ($personne->droitAdhesionPaye())
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Payé</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Impayé</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if (! $adherent->droitAdhesionPaye() || $personnesACharge->contains(fn ($p) => ! $p->droitAdhesionPaye()))
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Réglez le droit d'adhésion impayé auprès d'un gestionnaire de la mutuelle.
                        </p>
                    @endif
                </div>

                @if (session('erreur_paiement'))
                    <p class="mt-4 text-sm text-red-600 dark:text-red-400">{{ session('erreur_paiement') }}</p>
                @endif

                @if ($solde['reste'] > 0 && ! $peutPayerEnLigne)
                    <p class="mt-4 text-sm text-blue-700 dark:text-blue-300">
                        Le paiement en ligne sera disponible une fois votre adhésion validée par un gestionnaire.
                    </p>
                @endif

                @if ($solde['reste'] > 0 && $peutPayerEnLigne)
                    <form method="POST" action="{{ route('paiement.initier') }}" class="mt-6 flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="montant" value="{{ $solde['reste'] }}">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                            Payer {{ number_format($solde['reste'], 0, ',', ' ') }} FCFA en ligne
                        </button>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Mobile Money, Wave ou carte bancaire via CinetPay</span>
                    </form>
                @endif

                @if ($transactionsEnCours->isNotEmpty())
                    <div class="mt-4 text-sm text-yellow-700 dark:text-yellow-400">
                        {{ $transactionsEnCours->count() }} paiement(s) en cours de confirmation.
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                @include('partials.releve-cotisation')
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Historique des paiements</h3>

                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Année</label>
                        <select wire:model.live="anneeFiltre" class="rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm text-sm">
                            <option value="">Toutes</option>
                            @foreach ($anneesDisponibles as $annee)
                                <option value="{{ $annee }}">{{ $annee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Mois</label>
                        <select wire:model.live="moisFiltre" class="rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm text-sm">
                            <option value="">Tous</option>
                            @foreach ($moisListe as $m)
                                <option value="{{ $m->value }}">{{ $m->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($anneeFiltre || $moisFiltre)
                        <button type="button" wire:click="$set('anneeFiltre', ''); $set('moisFiltre', '')" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">
                            Réinitialiser
                        </button>
                    @endif
                </div>

                @if ($cotisations->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if ($anneeFiltre || $moisFiltre)
                            Aucun paiement pour cette période.
                        @else
                            Aucun paiement enregistré pour le moment.
                        @endif
                    </p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <th class="py-2">Date</th>
                                <th class="py-2">Période</th>
                                <th class="py-2">Montant</th>
                                <th class="py-2">Mode</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($cotisations as $c)
                                <tr wire:key="cotisation-{{ $c->id }}">
                                    <td class="py-2 text-gray-900 dark:text-gray-100">{{ $c->date_paiement->format('d/m/Y') }}</td>
                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $c->periode_debut->format('d/m/Y') }} - {{ $c->periode_fin->format('d/m/Y') }}</td>
                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ number_format($c->montant, 0, ',', ' ') }} FCFA</td>
                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $c->mode_paiement->libelle() }}</td>
                                    <td class="py-2 text-right">
                                        <a href="{{ route('cotisations.recu', $c) }}" class="text-primary-600 hover:underline">Reçu PDF</a>
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
