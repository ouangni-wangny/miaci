<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            Paiement en cours de confirmation
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 p-6 text-sm text-gray-700 dark:text-gray-300 space-y-4">
                <p>
                    Merci. Votre paiement est en cours de vérification auprès du prestataire.
                    Cette opération peut prendre quelques instants.
                </p>
                <p>
                    Votre cotisation sera automatiquement mise à jour dès que le paiement sera
                    confirmé. Vous pouvez suivre le statut depuis votre espace « Mes cotisations ».
                </p>
                <a href="{{ route('mon-espace.cotisations') }}" wire:navigate
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-primary-600 transition">
                    Voir mes cotisations
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
