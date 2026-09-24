@props(['couleur' => 'gray'])

{{-- Les classes sont écrites en toutes lettres (pas de bg-{{ $couleur }}-100) :
     Tailwind ne détecte que les classes présentes telles quelles dans les vues. --}}
@php
    $classes = match ($couleur) {
        'blue' => 'bg-blue-100 text-blue-800',
        'green' => 'bg-green-100 text-green-800',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'red' => 'bg-red-100 text-red-800',
        'orange' => 'bg-orange-100 text-orange-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap', $classes]) }}>{{ $slot }}</span>
