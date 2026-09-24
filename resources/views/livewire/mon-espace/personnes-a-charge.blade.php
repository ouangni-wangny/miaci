<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Mes personnes à charge
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-xl p-3">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('erreur_paiement'))
                <div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm rounded-xl p-3">
                    {{ session('erreur_paiement') }}
                </div>
            @endif

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pour ajouter, modifier ou retirer une personne à charge, contactez un gestionnaire de la mutuelle.
            </p>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                @if ($personnes->isEmpty())
                    <p class="p-6 text-sm text-gray-500 dark:text-gray-400">Vous n'avez déclaré aucune personne à charge.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <th class="px-4 py-2">Nom</th>
                                <th class="px-4 py-2">Lien</th>
                                <th class="px-4 py-2">Date de naissance</th>
                                <th class="px-4 py-2">Date d'adhésion</th>
                                <th class="px-4 py-2">Délai de carence requis</th>
                                <th class="px-4 py-2">Droit d'adhésion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($personnes as $personne)
                                <tr wire:key="personne-{{ $personne->id }}">
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $personne->nomComplet() }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $personne->lien_parente ?: '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $personne->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $personne->date_adhesion?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                        {{ $carencePersonnes[$personne->id]['delai'] }} mois — fin le {{ $carencePersonnes[$personne->id]['dateFin']->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($personne->droitAdhesionPaye())
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Payé</span>
                                        @elseif ($montantDroitAdhesion)
                                            <form method="POST" action="{{ route('paiement.initier-droit-adhesion') }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="beneficiaire" value="personne">
                                                <input type="hidden" name="personne_a_charge_id" value="{{ $personne->id }}">
                                                <button type="submit" class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition">
                                                    Payer {{ number_format($montantDroitAdhesion, 0, ',', ' ') }} FCFA
                                                </button>
                                            </form>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Impayé</span>
                                        @endif
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
