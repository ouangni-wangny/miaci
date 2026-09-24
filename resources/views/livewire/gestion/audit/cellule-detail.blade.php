<div class="text-xs text-gray-500 max-w-xl break-words whitespace-normal">
    @if ($entree->donnees_avant)
        <div>Avant : {{ json_encode($entree->donnees_avant, JSON_UNESCAPED_UNICODE) }}</div>
    @endif
    @if ($entree->donnees_apres)
        <div>Après : {{ json_encode($entree->donnees_apres, JSON_UNESCAPED_UNICODE) }}</div>
    @endif
</div>
