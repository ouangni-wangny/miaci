<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="{{ asset('images/logo.png') }}">
        <title>Solde de cotisation — {{ $adherent->nomComplet() }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-gray-50 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="flex flex-col items-center mb-6">
                <span class="flex items-center justify-center w-20 h-20 rounded-2xl bg-white shadow-sm p-2.5 mb-3">
                    <img src="{{ asset('images/logo.png') }}" alt="MIACI" class="w-full h-full object-contain">
                </span>
                <p class="font-bold text-gray-900">MIACI</p>
                <p class="text-xs text-gray-500">Vérification de solde par carte de membre</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <p class="text-sm text-gray-500">Adhérent tuteur</p>
                <p class="text-lg font-bold text-gray-900 mb-1">{{ $adherent->nomComplet() }}</p>
                <p class="text-xs text-gray-400 mb-5">{{ $adherent->matricule }}</p>

                @if ($solde['parametre'] === null)
                    <div class="bg-yellow-50 text-yellow-800 text-sm rounded-xl p-4">
                        Le paramétrage de cotisation n'est pas encore configuré : le solde ne peut pas être calculé.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 font-medium">Cotisé</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($solde['paye'], 0, ',', ' ') }}</p>
                            <p class="text-xs text-gray-400">FCFA</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-xs text-gray-500 font-medium">Dû au total</p>
                            <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($solde['du'], 0, ',', ' ') }}</p>
                            <p class="text-xs text-gray-400">FCFA</p>
                        </div>
                    </div>

                    <div @class([
                        'rounded-xl p-4 text-center',
                        'bg-green-50' => $estAJour,
                        'bg-red-50' => ! $estAJour,
                    ])>
                        <p class="text-xs font-medium mb-1 {{ $estAJour ? 'text-green-700' : 'text-red-700' }}">
                            {{ $estAJour ? 'À jour de cotisation' : 'Reste à payer' }}
                        </p>
                        <p @class([
                            'text-2xl font-bold',
                            'text-green-700' => $estAJour,
                            'text-red-700' => ! $estAJour,
                        ])>
                            {{ number_format($solde['reste'], 0, ',', ' ') }} FCFA
                        </p>
                    </div>

                    @if ($derniereCotisation)
                        <p class="text-xs text-gray-400 text-center mt-4">
                            Dernier paiement enregistré le {{ $derniereCotisation->date_paiement->format('d/m/Y') }}
                        </p>
                    @endif
                @endif
            </div>

            <p class="text-xs text-gray-400 text-center mt-6">
                En cas de désaccord avec ces montants, contactez la mutuelle :
                07 08 15 08 30 · mutuellemiaci@hotmail.com
            </p>
        </div>
    </body>
</html>
