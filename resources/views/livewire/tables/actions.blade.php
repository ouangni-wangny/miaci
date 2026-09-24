{{-- Cellule « Actions » d'une ligne de DataTable : un bouton « Actions » qui
     ouvre un menu (icône + nom de chaque action), via le composant <x-menu>
     (positionnement, fermeture et défilement : voir components/menu.blade.php).

     $actions : liste de
       ['libelle' => ..., 'icone' => 'eye', 'url' => ...]                                  (lien)
       ['libelle' => ..., 'icone' => 'trash', 'wire' => 'methode(id)', 'confirmation' => ...] (bouton)
     'icone' : nom d'une icône Heroicons « outline » (https://heroicons.com) ;
     'couleur' => 'red' pour une action destructive (séparée des autres). --}}
@if (count($actions) > 0)
    <x-menu label="Actions" size="sm" align="right" class="flex justify-end">
        @foreach ($actions as $action)
            @php($destructive = ($action['couleur'] ?? 'primary') === 'red')

            @if (isset($action['url']))
                <x-menu-item :href="$action['url']" :icon="$action['icone'] ?? 'chevron-right'" :danger="$destructive" :separated="$destructive && ! $loop->first">
                    {{ $action['libelle'] }}
                </x-menu-item>
            @else
                <x-menu-item :icon="$action['icone'] ?? 'chevron-right'"
                             :danger="$destructive"
                             :separated="$destructive && ! $loop->first"
                             wire:click="{{ $action['wire'] }}"
                             :wire:confirm="$action['confirmation'] ?? null">
                    {{ $action['libelle'] }}
                </x-menu-item>
            @endif
        @endforeach
    </x-menu>
@endif
