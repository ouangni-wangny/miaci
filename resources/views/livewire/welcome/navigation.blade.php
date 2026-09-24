<nav class="flex items-center gap-2">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="rounded-xl px-4 py-2 text-sm font-semibold bg-white/15 text-white hover:bg-white/25 transition"
        >
            Tableau de bord
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-white hover:bg-white/10 transition"
        >
            Connexion
        </a>
        <a
            href="{{ route('register') }}"
            class="rounded-xl px-4 py-2 text-sm font-semibold bg-white text-primary-600 hover:bg-primary-50 transition"
        >
            S'inscrire
        </a>
    @endauth
</nav>
