<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                Types de sinistre
            </h2>
            <a href="{{ route('gestion.parametres.types-sinistre.creer') }}" wire:navigate
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                Nouveau type
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:gestion.parametres.types-sinistre.types-table />
        </div>
    </div>
</div>
