<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Paramètres de cotisation
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-xl p-3">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('erreur'))
                <div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm rounded-xl p-3">
                    {{ session('erreur') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Paramètre actif</h3>
                @if ($parametreActuel)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Cotisation : {{ $parametreActuel->montant }} FCFA / {{ strtolower($parametreActuel->frequence->libelle()) }} —
                        Droit d'adhésion : {{ $parametreActuel->droit_adhesion !== null ? number_format($parametreActuel->droit_adhesion, 0, ',', ' ').' FCFA' : 'non défini' }},
                        applicable depuis le {{ $parametreActuel->date_debut->format('d/m/Y') }}.
                    </p>
                @else
                    <p class="text-sm text-yellow-600 dark:text-yellow-400">
                        Aucun paramètre de cotisation n'est configuré. Le solde des adhérents ne peut pas être calculé.
                    </p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {{ $parametreEnEditionId ? 'Modifier le paramètre' : 'Définir un nouveau paramètre' }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    @if ($parametreEnEditionId)
                        La modification s'applique directement à ce paramètre historique.
                    @else
                        L'enregistrement d'un nouveau paramètre désactive automatiquement le précédent et s'applique
                        à partir de la date choisie.
                    @endif
                </p>
                <form wire:submit="enregistrer" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div>
                            <x-input-label for="montant" value="Cotisation (FCFA)" />
                            <x-text-input type="number" wire:model="montant" id="montant" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('montant')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="droit_adhesion" value="Droit d'adhésion (FCFA)" />
                            <x-text-input type="number" wire:model="droit_adhesion" id="droit_adhesion" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('droit_adhesion')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="frequence" value="Fréquence" />
                            <select wire:model="frequence" id="frequence" class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm">
                                @foreach ($frequences as $f)
                                    <option value="{{ $f->value }}">{{ $f->libelle() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('frequence')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="date_debut" value="Applicable à partir du" />
                            <x-text-input type="date" wire:model="date_debut" id="date_debut" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('date_debut')" class="mt-2" />
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ $parametreEnEditionId ? 'Enregistrer les modifications' : 'Enregistrer' }}</x-primary-button>
                        @if ($parametreEnEditionId)
                            <button type="button" wire:click="annulerModification" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Annuler</button>
                        @endif
                    </div>
                </form>
            </div>

            @if ($historique->isNotEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Historique</h3>
                    <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <th class="py-2">Cotisation</th>
                                <th class="py-2">Droit d'adhésion</th>
                                <th class="py-2">Fréquence</th>
                                <th class="py-2">Depuis le</th>
                                <th class="py-2">Statut</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($historique as $p)
                                <tr wire:key="parametre-{{ $p->id }}">
                                    <td class="py-2 text-gray-900 dark:text-gray-100">{{ $p->montant }} FCFA</td>
                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $p->droit_adhesion !== null ? number_format($p->droit_adhesion, 0, ',', ' ').' FCFA' : '—' }}</td>
                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $p->frequence->libelle() }}</td>
                                    <td class="py-2 text-gray-700 dark:text-gray-300">{{ $p->date_debut->format('d/m/Y') }}</td>
                                    <td class="py-2">
                                        @if ($p->actif)
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Actif</span>
                                        @else
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactif</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right space-x-2">
                                        <button wire:click="modifier({{ $p->id }})" class="text-primary-600 hover:underline">Modifier</button>
                                        <button wire:click="supprimer({{ $p->id }})" wire:confirm="Supprimer ce paramètre de cotisation ?" class="text-red-600 hover:underline">Supprimer</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
