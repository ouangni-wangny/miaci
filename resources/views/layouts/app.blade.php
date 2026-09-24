<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('images/logo.png') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden bg-gray-100 dark:bg-gray-900">
            <livewire:layout.navigation />

            <!-- Overlay mobile -->
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
                 class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
                <!-- Topbar -->
                <header class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 px-4 sm:px-6 lg:px-8 py-3 lg:hidden">
                        <button @click="sidebarOpen = ! sidebarOpen" class="text-gray-500 dark:text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <span class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-gray-100 p-1">
                                <img src="{{ asset('images/logo.png') }}" alt="MIACI" class="w-full h-full object-contain">
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">MIACI</span>
                        </span>
                    </div>

                    @if (isset($header))
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                            {{ $header }}
                        </div>
                    @endif
                </header>

                @php
                    $adherentSignale = auth()->user()?->hasRole('ADHERENT')
                        && auth()->user()->adherent
                        && app(\App\Services\CotisationService::class)->doitEtreSignalePourArrieres(auth()->user()->adherent);
                @endphp

                @if ($adherentSignale)
                    <div class="alerte-radiation bg-red-600 text-white px-4 sm:px-6 lg:px-8 py-3">
                        <p class="max-w-7xl mx-auto text-sm font-semibold text-center sm:text-left">
                            ⚠️ Attention&nbsp;! Vous avez plus de 3 mois d'arriérés de cotisation. Conformément à
                            l'Article&nbsp;7 du règlement, vous risquez de perdre votre qualité de membre.
                            Contactez l'administration au plus vite au
                            <a href="tel:0708150830" class="underline">07 08 15 08 30</a> pour régulariser votre situation.
                        </p>
                    </div>
                @endif

                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
