<div>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Importer des adhérents (CSV)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Le fichier CSV doit contenir une première ligne d'en-têtes avec les colonnes suivantes,
                    dans cet ordre : <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 py-0.5 rounded">matricule, nom, prenom, sexe, date_naissance, telephone, email, etablissement, fonction, date_adhesion</code>.
                    Le sexe doit être <code class="text-xs">M</code> ou <code class="text-xs">F</code>, les dates au format <code class="text-xs">AAAA-MM-JJ</code>.
                </p>

                <a href="{{ route('gestion.adherents.modele') }}" class="inline-flex items-center text-sm text-primary-600 hover:underline mb-4">
                    Télécharger le modèle CSV
                </a>

                <form wire:submit="importer" class="space-y-4">
                    <div>
                        <input type="file" wire:model="fichier" accept=".csv,text/csv" class="block w-full text-sm text-gray-700 dark:text-gray-300">
                        <x-input-error :messages="$errors->get('fichier')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button wire:loading.attr="disabled">Importer</x-primary-button>
                        <a href="{{ route('gestion.adherents.index') }}" wire:navigate class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Retour à la liste</a>
                    </div>
                </form>

                @if ($importes > 0 || count($erreurs) > 0)
                    <div class="mt-6 space-y-3">
                        @if ($importes > 0)
                            <div class="bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm rounded-xl p-3">
                                {{ $importes }} adhérent(s) importé(s) avec succès.
                            </div>
                        @endif

                        @if (count($erreurs) > 0)
                            <div class="bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm rounded-xl p-3">
                                <p class="font-medium mb-1">{{ count($erreurs) }} ligne(s) ignorée(s) :</p>
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach ($erreurs as $erreur)
                                        <li>{{ $erreur }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
