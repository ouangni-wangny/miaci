<div>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Tableau de bord</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5">Bienvenue, {{ auth()->user()->name }}.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @hasanyrole('ADMIN|GESTIONNAIRE')
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Adhérents tuteurs actifs</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $adherentsActifs }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-primary-500">
                                <x-icon name="users" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Cotisations du mois</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($cotisationsMois, 0, ',', ' ') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">FCFA</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-green-500">
                                <x-icon name="cash" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Versé cette année</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalVerseAnnee, 0, ',', ' ') }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">FCFA</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-blue-500">
                                <x-icon name="card" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Sinistres en attente</p>
                                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $sinistresEnAttente }}</p>
                            </div>
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-orange-500">
                                <x-icon name="alert" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Recouvrement des cotisations -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Recouvrement du mois</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            Attendu selon le nombre de cotisants actifs, comparé à l'encaissé.
                        </p>

                        @if ($tauxRecouvrement !== null)
                            <div class="flex items-end justify-between mb-2">
                                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $tauxRecouvrement }}%</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($cotisationsMois, 0, ',', ' ') }} / {{ number_format($montantAttenduMois, 0, ',', ' ') }} FCFA
                                </p>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-gray-100 dark:bg-gray-900 overflow-hidden">
                                <div class="h-full rounded-full {{ $tauxRecouvrement >= 80 ? 'bg-green-500' : ($tauxRecouvrement >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                     style="width: {{ min(100, $tauxRecouvrement) }}%"></div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Paramétrage de cotisation manquant.</p>
                        @endif

                        <div class="grid grid-cols-2 gap-4 mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Arriérés cumulés</p>
                                <p class="mt-1 text-xl font-bold text-red-600">{{ number_format($arrieresTotal, 0, ',', ' ') }} F</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Adhérents tuteurs en alerte</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">
                                    {{ $nbEnAlerteArrieres }}
                                    <span class="text-xs font-normal text-gray-400">(+3 mois)</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tendance des encaissements -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Encaissements — 6 derniers mois</h3>
                        @php $maxMontant = max(1, collect($tendanceCotisations)->max('montant')); @endphp
                        <div class="flex items-end justify-between gap-3 h-32">
                            @foreach ($tendanceCotisations as $point)
                                <div class="flex-1 flex flex-col items-center justify-end h-full">
                                    <div class="w-full rounded-t-lg bg-primary-500"
                                         style="height: {{ max(4, (int) ($point['montant'] / $maxMontant * 100)) }}%"
                                         title="{{ number_format($point['montant'], 0, ',', ' ') }} FCFA"></div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 capitalize">{{ $point['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Adhérents -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Adhérents tuteurs</h3>
                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach ($repartitionAdherents as $statutValeur => $total)
                                @php $statutEnum = \App\Enums\StatutAdherent::from($statutValeur); @endphp
                                <span @class([
                                    'px-3 py-1.5 rounded-full text-xs font-medium',
                                    'bg-blue-100 text-blue-800' => $statutEnum->couleur() === 'blue',
                                    'bg-green-100 text-green-800' => $statutEnum->couleur() === 'green',
                                    'bg-yellow-100 text-yellow-800' => $statutEnum->couleur() === 'yellow',
                                    'bg-red-100 text-red-800' => $statutEnum->couleur() === 'red',
                                ])>
                                    {{ $statutEnum->libelle() }} : {{ $total }}
                                </span>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-2 text-sm pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Nouvelles adhésions ce mois :</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ $nouveauxCeMois }}</span>
                            @if ($nouveauxCeMois >= $nouveauxMoisDernier)
                                <span class="text-green-600 text-xs">▲ vs {{ $nouveauxMoisDernier }} le mois dernier</span>
                            @else
                                <span class="text-red-600 text-xs">▼ vs {{ $nouveauxMoisDernier }} le mois dernier</span>
                            @endif
                        </div>
                    </div>

                    <!-- Sinistres -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Sinistres</h3>
                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach (\App\Enums\StatutDemandeSinistre::cases() as $s)
                                <span class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300">
                                    {{ $s->libelle() }} : {{ $sinistresParStatut[$s->value] ?? 0 }}
                                </span>
                            @endforeach
                        </div>
                        <div class="text-sm pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-gray-500 dark:text-gray-400">Montant versé cette année :</span>
                            <span class="font-bold text-gray-900 dark:text-gray-100">{{ number_format($montantVerseAnneeSinistres, 0, ',', ' ') }} FCFA</span>
                        </div>
                    </div>

                    <!-- Droit d'adhésion -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Droit d'adhésion</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Encaissé au total</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($droitAdhesionEncaisseTotal, 0, ',', ' ') }} F</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Personnes en attente</p>
                                <p class="mt-1 text-xl font-bold {{ $personnesEnAttenteDroitAdhesion > 0 ? 'text-yellow-600' : 'text-gray-900 dark:text-gray-100' }}">
                                    {{ $personnesEnAttenteDroitAdhesion }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Dons de fin d'année -->
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Dons de fin d'année</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Éligibles ({{ now()->year }})</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $eligiblesDonsFinAnnee }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Déjà versés</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $donsVersesAnnee }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-4">Accès rapide</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('gestion.adherents.index') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                            Gérer les adhérents
                        </a>
                        <a href="{{ route('gestion.cotisations.index') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cotisations
                        </a>
                        <a href="{{ route('gestion.sinistres.index') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Sinistres
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    @if ($adherent && $adherent->statut->value === 'en_attente')
                        <div class="bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm rounded-xl p-4 mb-6">
                            Votre adhésion est <strong>en attente de validation</strong> par un gestionnaire de la
                            mutuelle. Vous pouvez dès à présent compléter votre profil, mais la soumission de
                            demandes de sinistre et le paiement en ligne seront disponibles après validation.
                        </div>
                    @endif

                    @if ($solde)
                        <div class="grid gap-4 sm:grid-cols-3 mb-6">
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Dû</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($solde['du'], 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Payé</p>
                                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($solde['paye'], 0, ',', ' ') }} FCFA</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Reste à payer</p>
                                <p class="mt-1 text-xl font-bold {{ $estAJour ? 'text-green-600' : 'text-red-600' }}">{{ number_format($solde['reste'], 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                    @endif

                    @if ($dernieresDemandes->isNotEmpty())
                        <div class="mb-6">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Dernières demandes de sinistre</p>
                            <ul class="text-sm space-y-1">
                                @foreach ($dernieresDemandes as $d)
                                    <li class="text-gray-600 dark:text-gray-400">
                                        {{ $d->typeSinistre->libelle }} — {{ $d->statut->libelle() }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('mon-espace.profil') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                            Mon profil adhérent
                        </a>
                        <a href="{{ route('mon-espace.personnes-a-charge') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Mes personnes à charge
                        </a>
                        <a href="{{ route('mon-espace.cotisations') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Mes cotisations
                        </a>
                        <a href="{{ route('mon-espace.sinistres.index') }}" wire:navigate
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Mes sinistres
                        </a>
                    </div>
                </div>
            @endhasanyrole
        </div>
    </div>
</div>
