@props([
    'label' => null,
    'icon' => null,
    'variant' => 'secondary',
    'size' => 'md',
    'align' => 'right',
])

{{-- Menu déroulant : un bouton qui ouvre une liste d'<x-menu-item>.

     <x-menu label="Modifier" icon="pencil-square" align="right">
         <x-menu-item href="…" icon="user">Profil</x-menu-item>
         <x-menu-item wire:click="supprimer" icon="trash" danger separated>Supprimer</x-menu-item>
     </x-menu>

     variant : secondary (bordé, défaut) | primary (orange) ; size : md | sm ;
     align : right | left (bord du menu aligné sur celui du bouton).

     Le menu est en position « fixed », calculée depuis le bouton : une page ou
     une table dans un conteneur à défilement (overflow) rognerait un menu
     « absolute ». Il se place au-dessus du bouton s'il manque de place en
     dessous, suit le bouton quand la page défile ou est redimensionnée (et se
     ferme si le bouton sort de l'écran), et se ferme avec Échap ou un clic à
     l'extérieur. On ne le ferme pas au défilement : des événements de
     défilement parasites suffisaient à le refermer juste après l'ouverture. --}}
@php
    $tailles = [
        'md' => 'px-4 py-2.5 text-sm',
        'sm' => 'px-3 py-1.5 text-sm',
    ];
    $variantes = [
        'secondary' => 'text-gray-700 bg-white border border-gray-300 shadow-sm hover:bg-gray-50',
        'primary' => 'text-white bg-primary-500 border border-transparent shadow-sm hover:bg-primary-600',
    ];
@endphp

<div x-data="{
        open: false,
        top: 0,
        left: 0,
        toggle() {
            this.open = ! this.open;
            if (this.open) this.$nextTick(() => this.placer());
        },
        placer() {
            const bouton = this.$refs.bouton.getBoundingClientRect();
            const menu = this.$refs.menu;
            if (bouton.bottom < 0 || bouton.top > window.innerHeight) {
                this.open = false;
                return;
            }
            this.left = {{ $align === 'right' ? 'Math.max(8, bouton.right - menu.offsetWidth)' : 'Math.min(bouton.left, window.innerWidth - menu.offsetWidth - 8)' }};
            this.top = bouton.bottom + menu.offsetHeight + 8 > window.innerHeight
                ? Math.max(8, bouton.top - menu.offsetHeight - 4)
                : bouton.bottom + 4;
        },
    }"
     x-on:keydown.escape.window="open = false"
     x-on:scroll.window.capture.passive="if (open) placer()"
     x-on:resize.window="if (open) placer()"
     {{ $attributes->class(['inline-flex']) }}>
    <button type="button"
            x-ref="bouton"
            x-on:click="toggle()"
            x-bind:aria-expanded="open"
            aria-haspopup="true"
            @class([
                'inline-flex items-center gap-2 font-medium rounded-xl transition focus:outline-none focus:ring-2 focus:ring-primary-200',
                $tailles[$size] ?? $tailles['md'],
                $variantes[$variant] ?? $variantes['secondary'],
            ])>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="w-4 h-4 shrink-0" />
        @endif
        {{ $label }}
        <x-heroicon-m-chevron-down @class(['w-4 h-4', 'text-gray-400' => $variant === 'secondary', 'text-white/80' => $variant === 'primary']) />
    </button>

    <div x-ref="menu"
         x-show="open"
         x-cloak
         x-on:click.outside="open = false"
         x-bind:style="`top: ${top}px; left: ${left}px`"
         role="menu"
         class="fixed z-50 min-w-48 py-1 text-left bg-white rounded-xl shadow-lg ring-1 ring-black/5">
        {{ $slot }}
    </div>
</div>
