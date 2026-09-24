<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Cotisations
            </h2>
            <a href="{{ route('gestion.exports.cotisations') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Exporter
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total collecté ce mois-ci</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalCollecteMois, 0, ',', ' ') }} FCFA</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Paramétrage</p>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">Montant et fréquence de cotisation</p>
                    </div>
                    @can('configurerParametres', \App\Models\Cotisation::class)
                        <a href="{{ route('gestion.parametres.cotisation') }}" wire:navigate class="text-primary-600 hover:underline text-sm">Configurer</a>
                    @endcan
                </div>
            </div>

            <livewire:gestion.cotisations.soldes-table />
        </div>
    </div>
</div>
