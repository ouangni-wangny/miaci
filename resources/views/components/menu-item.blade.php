@props(['href' => null, 'icon' => null, 'danger' => false, 'separated' => false, 'navigate' => true])

{{-- Entrée d'un <x-menu> : un lien (href) ou un bouton (wire:click, x-on:click…).
     danger : action destructive (rouge) ; separated : filet au-dessus ;
     navigate : false pour un téléchargement ou un lien externe (pas de wire:navigate). --}}
@php
    $classes = 'flex items-center gap-2.5 w-full px-4 py-2 text-sm whitespace-nowrap transition '
        .($danger
            ? 'text-red-600 hover:bg-red-50'
            : 'text-gray-700 hover:bg-primary-50 hover:text-primary-700');
@endphp

@if ($separated)
    <div class="my-1 border-t border-gray-100" role="separator"></div>
@endif

@if ($href)
    <a href="{{ $href }}"
       @if ($navigate) wire:navigate @endif
       role="menuitem"
       {{ $attributes->class([$classes]) }}>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="w-4 h-4 shrink-0" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="button"
            role="menuitem"
            x-on:click="open = false"
            {{ $attributes->class([$classes]) }}>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="w-4 h-4 shrink-0" />
        @endif
        {{ $slot }}
    </button>
@endif
