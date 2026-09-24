{{-- Pastille cliquable : bascule actif / inactif (TypesTable::basculerActif). --}}
<button type="button"
        wire:click="basculerActif({{ $type->id }})"
        title="{{ $type->actif ? 'Cliquer pour désactiver' : 'Cliquer pour activer' }}"
        @class([
            'px-2 py-1 rounded-full text-xs font-medium transition hover:opacity-80',
            'bg-green-100 text-green-800' => $type->actif,
            'bg-gray-100 text-gray-600' => ! $type->actif,
        ])>
    {{ $type->actif ? 'Actif' : 'Inactif' }}
</button>
