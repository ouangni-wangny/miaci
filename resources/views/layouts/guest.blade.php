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
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 dark:from-gray-900 dark:to-gray-800 flex flex-col items-center justify-center p-4">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <a href="/" wire:navigate class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl mb-4 shadow-lg p-2">
                        <img src="{{ asset('images/logo.png') }}" alt="MIACI" class="w-full h-full object-contain">
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">MIACI</h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire</p>
                </div>

                <div class="w-full px-6 py-8 bg-white dark:bg-gray-800 shadow-xl rounded-2xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
