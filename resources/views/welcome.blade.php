<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="{{ asset('images/logo.png') }}">

        <title>{{ config('app.name') }} — Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire</title>
        <meta name="description" content="Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire (MIACI) : cotisations, assistances et entraide entre instituteurs. Gérez votre adhésion en ligne.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans">
        <div class="min-h-screen bg-white dark:bg-gray-900">
            <!-- Hero -->
            <div class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-900 to-secondary-900 text-white">
                <!-- Décor -->
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-32 -right-24 w-[32rem] h-[32rem] rounded-full bg-primary-400/20 blur-3xl"></div>
                    <div class="absolute top-1/2 -left-32 w-96 h-96 rounded-full bg-primary-300/10 blur-3xl"></div>
                    <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grille" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grille)"/>
                    </svg>
                </div>

                <div class="relative max-w-6xl mx-auto px-6">
                    <header class="flex items-center justify-between py-6">
                        <div class="flex items-center gap-3">
                            <span class="flex items-center justify-center w-24 h-24 rounded-2xl bg-white p-2.5 shadow-lg shadow-black/20">
                                <img src="{{ asset('images/logo.png') }}" alt="MIACI" class="w-full h-full object-contain">
                            </span>
                            <div>
                                <span class="font-bold text-3xl leading-tight block">MIACI</span>
                                <span class="text-sm text-primary-200/80 leading-tight block">Mutuelle des Instituteurs et Assimilés</span>
                            </div>
                        </div>
                        <livewire:welcome.navigation />
                    </header>

                    <div class="text-center py-16 md:py-24">
                        <div class="inline-flex items-center gap-2 bg-white/10 border border-white/15 rounded-full px-4 py-1.5 text-xs font-semibold text-primary-100 mb-6">
                            <x-icon name="shield" class="w-3.5 h-3.5" />
                            {{ $site->hero_badge }}
                        </div>
                        <h1 class="text-4xl md:text-6xl font-extrabold mb-5 tracking-tight leading-[1.1]">
                            {{ $site->hero_titre_ligne1 }}<br class="hidden md:block">
                            <span class="bg-gradient-to-r from-primary-200 to-white bg-clip-text text-transparent">{{ $site->hero_titre_ligne2 }}</span>
                        </h1>
                        <p class="text-lg text-primary-100/90 mb-10 max-w-xl mx-auto leading-relaxed">
                            {{ $site->hero_texte }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                            <a href="{{ route('login') }}"
                               class="bg-white text-primary-700 font-bold px-8 py-4 rounded-2xl hover:bg-primary-50 transition flex items-center justify-center gap-2 text-lg shadow-xl shadow-black/20">
                                Accéder à mon espace
                                <x-icon name="arrow-right" class="w-5 h-5" />
                            </a>
                            <a href="{{ route('statuts') }}"
                               class="bg-green-500 text-white font-bold px-8 py-4 rounded-2xl hover:bg-green-600 transition flex items-center justify-center gap-2 text-lg shadow-xl shadow-black/20">
                                Télécharger nos statuts (PDF)
                            </a>
                        </div>

                        <!-- Stats -->
                        <div class="max-w-xs mx-auto">
                            <div class="bg-white/[0.07] backdrop-blur border border-white/10 rounded-2xl px-6 py-5">
                                <p class="text-3xl font-extrabold">1M</p>
                                <p class="text-sm text-primary-100/80 mt-1">FCFA d'assistance décès garantie</p>
                            </div>
                        </div>
                    </div>
                </div>

                <svg class="relative block w-full text-white dark:text-gray-900" viewBox="0 0 1440 48" fill="currentColor" preserveAspectRatio="none" style="height: 32px;">
                    <path d="M0,32 C360,0 1080,0 1440,32 L1440,48 L0,48 Z"></path>
                </svg>
            </div>

            @if ($bannieres->isNotEmpty())
                <div class="max-w-6xl mx-auto px-6 -mt-2 pb-8">
                    <div class="flex gap-4 overflow-x-auto pb-2">
                        @foreach ($bannieres as $banniere)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($banniere->image_path) }}"
                                 alt="Bannière MIACI"
                                 class="h-48 w-auto flex-shrink-0 rounded-2xl object-cover shadow-sm">
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Pourquoi nous rejoindre -->
            <div class="max-w-6xl mx-auto px-6 -mt-2 pb-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach ([
                        ['icon' => 'shield', 'titre' => $site->avantage_1_titre, 'texte' => $site->avantage_1_texte],
                        ['icon' => 'heart', 'titre' => $site->avantage_2_titre, 'texte' => $site->avantage_2_texte],
                        ['icon' => 'check-circle', 'titre' => $site->avantage_3_titre, 'texte' => $site->avantage_3_texte],
                        ['icon' => 'bolt', 'titre' => $site->avantage_4_titre, 'texte' => $site->avantage_4_texte],
                    ] as $avantage)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-5 text-center">
                            <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 mx-auto mb-3">
                                <x-icon :name="$avantage['icon']" class="w-5 h-5" />
                            </div>
                            <p class="font-bold text-gray-900 dark:text-white text-sm mb-1">{{ $avantage['titre'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $avantage['texte'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Qui sommes-nous -->
            <div class="max-w-6xl mx-auto px-6 py-20">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <p class="text-xs font-bold text-primary-600 uppercase tracking-widest mb-3">À propos de nous</p>
                        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-5">Qui sommes-nous ?</h2>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                            {{ $site->a_propos_texte }}
                        </p>
                        <div class="flex items-center gap-2 mt-6 text-sm text-gray-500 dark:text-gray-500">
                            <x-icon name="location" class="w-4 h-4 text-primary-500" />
                            Siège social : {{ $site->siege_social }}
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-primary-50 to-white dark:from-gray-800 dark:to-gray-800 rounded-3xl border border-primary-100 dark:border-gray-700 p-8">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-5">
                            <x-icon name="target" class="w-5 h-5 text-primary-600" />
                            Nos objectifs
                        </h3>
                        <ul class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                            @foreach ([
                                "Promouvoir l'épanouissement socio-économique des instituteurs",
                                "Réduire l'extrême pauvreté et mettre en commun l'épargne des membres",
                                'Consentir des prêts à des conditions convenables',
                                'Rendre des services de qualité, adaptés au milieu',
                                'Accompagner les démarches administratives et professionnelles des membres',
                                'Favoriser un réseau autonome entre instituteurs',
                            ] as $objectif)
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-0.5 flex-shrink-0 flex items-center justify-center w-4 h-4 rounded-full bg-primary-500 text-white">
                                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    </span>
                                    {{ $objectif }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div class="bg-gray-50 dark:bg-gray-950/40 py-20">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="text-center mb-12">
                        <p class="text-xs font-bold text-primary-600 uppercase tracking-widest mb-3">Espace en ligne</p>
                        <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Ce que vous pouvez faire en ligne</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach ([
                            ['icon' => 'cash', 'titre' => $site->feature_1_titre, 'texte' => $site->feature_1_texte],
                            ['icon' => 'alert', 'titre' => $site->feature_2_titre, 'texte' => $site->feature_2_texte],
                            ['icon' => 'user-group', 'titre' => $site->feature_3_titre, 'texte' => $site->feature_3_texte],
                        ] as $feature)
                            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-7 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all duration-200">
                                <div class="absolute top-0 left-7 right-7 h-1 rounded-full bg-primary-500 scale-x-0 group-hover:scale-x-100 transition-transform origin-left"></div>
                                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-500 text-white mb-5">
                                    <x-icon :name="$feature['icon']" class="w-6 h-6" />
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ $feature['titre'] }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ $feature['texte'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="max-w-6xl mx-auto px-6 py-20">
                <div class="text-center mb-12">
                    <p class="text-xs font-bold text-primary-600 uppercase tracking-widest mb-3">Une question ?</p>
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Nous contacter</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                    <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-7 hover:border-primary-200 dark:hover:border-primary-800 transition">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-50 dark:bg-gray-800 text-primary-600 mx-auto mb-4">
                            <x-icon name="phone" class="w-5 h-5" />
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Téléphone</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $site->contact_telephone_1 }}</p>
                        @if ($site->contact_telephone_2)
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $site->contact_telephone_2 }}</p>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-7 hover:border-primary-200 dark:hover:border-primary-800 transition">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-50 dark:bg-gray-800 text-primary-600 mx-auto mb-4">
                            <x-icon name="mail" class="w-5 h-5" />
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Email</p>
                        <p class="font-semibold text-gray-900 dark:text-white break-all">{{ $site->contact_email }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-7 hover:border-primary-200 dark:hover:border-primary-800 transition">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-50 dark:bg-gray-800 text-primary-600 mx-auto mb-4">
                            <x-icon name="location" class="w-5 h-5" />
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Siège social</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $site->siege_social }}</p>
                    </div>
                </div>
            </div>

            <!-- CTA final -->
            <div class="bg-gradient-to-br from-primary-700 to-primary-900 mx-6 mb-16 rounded-3xl max-w-6xl lg:mx-auto px-8 py-14 text-center text-white">
                <h2 class="text-2xl md:text-3xl font-extrabold mb-3">{{ $site->cta_titre }}</h2>
                <p class="text-primary-100/90 mb-8 max-w-xl mx-auto">
                    {{ $site->cta_texte }}
                </p>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-2 bg-white text-primary-700 font-bold px-8 py-4 rounded-2xl hover:bg-primary-50 transition text-lg">
                    Je m'inscris maintenant
                    <x-icon name="arrow-right" class="w-5 h-5" />
                </a>
            </div>

            <footer class="border-t border-gray-100 dark:border-gray-800 py-12">
                <div class="max-w-6xl mx-auto px-6 grid sm:grid-cols-3 gap-8 mb-8">
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="flex items-center justify-center w-11 h-11 rounded-lg bg-primary-50 p-1">
                                <img src="{{ asset('images/logo.png') }}" alt="MIACI" class="w-full h-full object-contain">
                            </span>
                            <span class="font-bold text-gray-900 dark:text-white">MIACI</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-500 leading-relaxed">
                            {{ $site->footer_texte }}
                        </p>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white mb-3">Liens rapides</p>
                        <ul class="space-y-2 text-sm text-gray-500 dark:text-gray-500">
                            <li><a href="{{ route('login') }}" class="hover:text-primary-600 transition">Connexion</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-primary-600 transition">Inscription</a></li>
                            <li><a href="{{ route('statuts') }}" class="hover:text-primary-600 transition">Nos statuts (PDF)</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-sm text-gray-900 dark:text-white mb-3">Contact</p>
                        <ul class="space-y-2 text-sm text-gray-500 dark:text-gray-500">
                            <li>{{ $site->contact_telephone_1 }}{{ $site->contact_telephone_2 ? ' · '.$site->contact_telephone_2 : '' }}</li>
                            <li>{{ $site->contact_email }}</li>
                            <li>{{ $site->siege_social }}</li>
                        </ul>
                    </div>
                </div>
                <div class="max-w-6xl mx-auto px-6 pt-6 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-400 dark:text-gray-600 text-center">
                    &copy; {{ now()->year }} MIACI — Mutuelle des Instituteurs et Assimilés de Côte d'Ivoire
                </div>
            </footer>
        </div>
    </body>
</html>
