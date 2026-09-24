@props(['titre' => null, 'description' => null, 'icone' => null, 'flush' => false])

{{-- Panneau (carte) de l'application : en-tête facultatif (icône, titre,
     description, boutons dans le slot « actions »), contenu, pied facultatif
     (slot « footer », pour les boutons d'un formulaire).

     <x-panel titre="Profil" icone="user" description="…">
         <x-slot:actions> … </x-slot:actions>
         contenu
         <x-slot:footer> … </x-slot:footer>
     </x-panel> --}}
<section {{ $attributes->class(['bg-white rounded-2xl border border-gray-100 shadow-sm']) }}>
    @if ($titre || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 px-6 pt-5 pb-4 border-b border-gray-100">
            <div @class(['flex gap-3', 'items-start' => $description, 'items-center' => ! $description])>
                @if ($icone)
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                        <x-dynamic-component :component="'heroicon-o-'.$icone" class="w-5 h-5" />
                    </span>
                @endif
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ $titre }}</h3>
                    @if ($description)
                        <p class="mt-0.5 text-sm text-gray-500">{{ $description }}</p>
                    @endif
                </div>
            </div>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-6' => ! $flush])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="flex flex-wrap items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-2xl">
            {{ $footer }}
        </footer>
    @endisset
</section>
