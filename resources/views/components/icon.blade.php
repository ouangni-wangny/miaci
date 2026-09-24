@props(['name'])

<svg {{ $attributes->merge(['class' => 'w-5 h-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1.5"/>
            <rect x="14" y="3" width="7" height="7" rx="1.5"/>
            <rect x="3" y="14" width="7" height="7" rx="1.5"/>
            <rect x="14" y="14" width="7" height="7" rx="1.5"/>
            @break

        @case('users')
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2"/>
            @break

        @case('user')
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21v-1a8 8 0 0 1 16 0v1"/>
            @break

        @case('user-group')
            <circle cx="8.5" cy="9" r="3.5"/>
            <circle cx="16" cy="10" r="3"/>
            <path d="M2 21v-1a5.5 5.5 0 0 1 5.5-5.5h2A5.5 5.5 0 0 1 15 20v1"/>
            <path d="M17 14.5a4 4 0 0 1 5 3.87V21"/>
            @break

        @case('cash')
            <rect x="2" y="6" width="20" height="12" rx="2"/>
            <circle cx="12" cy="12" r="2.5"/>
            <path d="M6 12h.01M18 12h.01"/>
            @break

        @case('card')
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
            @break

        @case('alert')
            <path d="M12 2L2 20h20L12 2z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <path d="M12 17h.01"/>
            @break

        @case('sliders')
            <line x1="4" y1="6" x2="20" y2="6"/>
            <circle cx="9" cy="6" r="2" fill="currentColor"/>
            <line x1="4" y1="12" x2="20" y2="12"/>
            <circle cx="15" cy="12" r="2" fill="currentColor"/>
            <line x1="4" y1="18" x2="20" y2="18"/>
            <circle cx="9" cy="18" r="2" fill="currentColor"/>
            @break

        @case('list')
            <line x1="8" y1="6" x2="20" y2="6"/>
            <line x1="8" y1="12" x2="20" y2="12"/>
            <line x1="8" y1="18" x2="20" y2="18"/>
            <circle cx="4" cy="6" r="1" fill="currentColor"/>
            <circle cx="4" cy="12" r="1" fill="currentColor"/>
            <circle cx="4" cy="18" r="1" fill="currentColor"/>
            @break

        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
            @break

        @case('chevron-down')
            <polyline points="6 9 12 15 18 9"/>
            @break

        @case('menu')
            <line x1="4" y1="6" x2="20" y2="6"/>
            <line x1="4" y1="12" x2="20" y2="12"/>
            <line x1="4" y1="18" x2="20" y2="18"/>
            @break

        @case('mail')
            <rect x="2" y="4" width="20" height="16" rx="2"/>
            <path d="M2 6l10 7 10-7"/>
            @break

        @case('phone')
            <rect x="6" y="2" width="12" height="20" rx="2"/>
            <line x1="10" y1="18" x2="14" y2="18"/>
            @break

        @case('location')
            <path d="M12 21s7-7.5 7-12a7 7 0 0 0-14 0c0 4.5 7 12 7 12z"/>
            <circle cx="12" cy="9" r="2.5"/>
            @break

        @case('target')
            <circle cx="12" cy="12" r="9"/>
            <circle cx="12" cy="12" r="5"/>
            <circle cx="12" cy="12" r="1" fill="currentColor"/>
            @break

        @case('shield')
            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/>
            <polyline points="9 12 11 14 15 10"/>
            @break

        @case('heart')
            <path d="M12 20s-7-4.5-9.5-9C.9 7.5 2.5 4 6 4c2 0 3.5 1.2 4.5 2.8 1-1.6 2.5-2.8 4.5-2.8 3.5 0 5.1 3.5 3.5 7-2.5 4.5-9.5 9-9.5 9z"/>
            @break

        @case('bolt')
            <polygon points="13 2 4 14 11 14 10 22 20 10 13 10 13 2"/>
            @break

        @case('check-circle')
            <circle cx="12" cy="12" r="9"/>
            <polyline points="8 12.5 11 15.5 16 9"/>
            @break

        @case('arrow-right')
            <line x1="4" y1="12" x2="20" y2="12"/>
            <polyline points="13 5 20 12 13 19"/>
            @break

        @case('edit')
            <path d="M12 20h9"/>
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
            @break

        @case('image')
            <rect x="3" y="4" width="18" height="16" rx="2"/>
            <circle cx="8.5" cy="9.5" r="1.5" fill="currentColor"/>
            <path d="M21 15l-5-5-9 9"/>
            @break
    @endswitch
</svg>
