<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Dons de fin d'année
            </h2>
            <a href="{{ route('gestion.exports.dons-fin-annee', ['annee' => $annee]) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Exporter (Excel)
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Selon l'article 7 du règlement, {{ number_format($montant, 0, ',', ' ') }} FCFA (5 000 F fête de
                    Noël et 5 000 F fête du nouvel an) sont versés le 20 décembre à chaque adhérent tuteur actif, à jour de
                    cotisation et ayant fini son délai de carence, dont le carnet compte au moins 3 membres
                    (lui-même + au moins 2 personnes à charge déclarées et validées), et dont au moins 2 de ces
                    personnes à charge ont elles-mêmes fini leur propre délai de carence.
                </p>
                <div class="max-w-xs">
                    <x-input-label for="annee" value="Année" />
                    <x-text-input type="number" wire:model.live="annee" id="annee" class="mt-1 block w-full" />
                </div>
            </div>

            <livewire:gestion.dons-fin-annee.dons-table :annee="$annee" />
        </div>
    </div>
</div>
