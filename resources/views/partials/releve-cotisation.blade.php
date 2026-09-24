{{-- Relevé période par période (un mois si la cotisation est mensuelle).
     Attend une variable $releves ; $sansTitre = true quand le conteneur
     (ex. un <x-panel>) porte déjà le titre. --}}
<div>
    @unless ($sansTitre ?? false)
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Relevé mensuel</h3>
    @endunless

    @if (empty($releves))
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Le paramétrage de cotisation n'a pas encore été défini par la mutuelle.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                        <th class="py-2">Période</th>
                        <th class="py-2">Dû</th>
                        <th class="py-2">Payé</th>
                        <th class="py-2">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($releves as $releve)
                        <tr>
                            <td class="py-2 text-gray-900 dark:text-gray-100">
                                {{ ucfirst($releve['debut']->locale('fr')->isoFormat('MMMM YYYY')) }}
                            </td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ number_format($releve['du'], 0, ',', ' ') }} FCFA</td>
                            <td class="py-2 text-gray-700 dark:text-gray-300">{{ number_format($releve['paye'], 0, ',', ' ') }} FCFA</td>
                            <td class="py-2">
                                <span @class([
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    'bg-green-100 text-green-800' => $releve['statut']->value === 'paye',
                                    'bg-yellow-100 text-yellow-800' => $releve['statut']->value === 'partiel',
                                    'bg-red-100 text-red-800' => $releve['statut']->value === 'impaye',
                                ])>
                                    {{ $releve['statut']->libelle() }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
