<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Adhérents tuteurs
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('gestion.exports.adherents') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Exporter (Excel)
                </a>
                <a href="{{ route('gestion.exports.membres') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Liste des membres (Excel)
                </a>
                <a href="{{ route('gestion.adherents.importer') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Importer (CSV)
                </a>
                <a href="{{ route('gestion.adherents.creer') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                    Nouvel adhérent tuteur
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:gestion.adherents.adherents-table />
        </div>
    </div>
</div>
