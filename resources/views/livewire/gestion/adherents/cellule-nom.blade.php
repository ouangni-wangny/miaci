<span class="font-medium text-gray-900">{{ $adherent->nomComplet() }}</span>
@if ($signale)
    <span title="Plus de 3 mois d'arriérés de cotisation" class="ml-1 text-red-500">⚠</span>
@endif
