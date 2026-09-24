{{-- Zone « after-tools » du tableau des adhérents (voir AdherentsTable::configurer()).
     Rendue dans le composant de la table : $this est AdherentsTable. --}}
@php
    $repartitionVilles = $this->repartitionParVille();
    $villeActive = $this->getAppliedFilterWithValue('ville');
@endphp

@if ($repartitionVilles->isNotEmpty())
    <div class="mb-4 bg-white shadow-sm rounded-2xl border border-gray-100 p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Répartition par ville</p>
        <div class="flex flex-wrap gap-2">
            @foreach ($repartitionVilles as $ligne)
                <button type="button"
                        wire:click="filtrerParVille({{ Js::from($ligne['ville']) }})"
                        wire:key="ville-{{ $loop->index }}"
                        @class([
                            'px-3 py-1.5 rounded-full text-xs font-medium transition',
                            'bg-primary-500 text-white' => $villeActive === $ligne['ville'],
                            'bg-primary-50 text-primary-700 hover:bg-primary-100' => $villeActive !== $ligne['ville'],
                        ])>
                    {{ $ligne['ville'] }} · {{ $ligne['total'] }}
                </button>
            @endforeach
        </div>
    </div>
@endif
