@props(['label', 'value' => null])

{{-- Une information en lecture seule (libellé + valeur), à placer dans un
     <dl>. Une valeur vide s'affiche « — » ; le contenu du slot, s'il existe,
     remplace $value (badge, lien...).

     <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2">
         <x-field label="Téléphone" :value="$adherent->telephone" />
     </dl> --}}
<div {{ $attributes }}>
    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $label }}</dt>
    <dd class="mt-1 text-sm text-gray-900">
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @elseif (filled($value))
            {{ $value }}
        @else
            <span class="text-gray-400">—</span>
        @endif
    </dd>
</div>
