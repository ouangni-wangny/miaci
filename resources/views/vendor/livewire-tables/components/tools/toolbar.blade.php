@aware([ 'tableName','isTailwind','isBootstrap'])
@props([])
@php($toolBarAttributes = $this->getToolBarAttributesBag)

{{-- Une seule ligne (qui passe à la ligne si l'écran est étroit) :
     recherche, filtres — chacun avec son libellé au-dessus — puis, à droite,
     les actions sur la sélection et le choix des colonnes. La taille de page
     est dans le pied de page (components/pagination.blade.php). --}}
<div
    {{
        $toolBarAttributes->merge()
        ->class([
            'flex flex-wrap items-end gap-3' => $isTailwind && ($toolBarAttributes['default-styling'] ?? true),
            'd-md-flex justify-content-between mb-3' => $isBootstrap && ($toolBarAttributes['default-styling'] ?? true),
        ])
        ->except(['default','default-styling','default-colors'])
    }}
>
    @if ($this->hasConfigurableAreaFor('toolbar-left-start'))
        <div x-cloak x-show="!currentlyReorderingStatus">
            @include($this->getConfigurableAreaFor('toolbar-left-start'), $this->getParametersForConfigurableArea('toolbar-left-start'))
        </div>
    @endif

    @if ($this->showReorderButton())
        <x-livewire-tables::tools.toolbar.items.reorder-buttons />
    @endif

    @if ($this->showSearchField())
        <div class="w-full sm:w-64">
            <label for="{{ $tableName }}-search" class="block text-sm font-medium leading-5 text-gray-700 mb-px">Recherche</label>
            <x-livewire-tables::tools.toolbar.items.search-field />
        </div>
    @endif

    @if ($this->filtersAreEnabled() && $this->filtersVisibilityIsEnabled() && $this->hasVisibleFilters())
        @foreach (collect($this->getFiltersByRow())->flatten() as $filter)
            <div class="w-full sm:w-36" id="{{ $tableName }}-filter-{{ $filter->getKey() }}-wrapper">
                {{ $filter->setGenericDisplayData($this->getFilterGenericData)->render() }}
            </div>
        @endforeach

        @if ($this->hasAppliedFiltersWithValues() || $this->hasSearch())
            <button type="button"
                    wire:click.prevent="setFilterDefaults"
                    x-on:click="$wire.clearSearch()"
                    class="self-end px-2 py-2 text-sm font-medium text-primary-600 hover:text-primary-800 hover:underline">
                Effacer
            </button>
        @endif
    @endif

    @if($this->showActionsInToolbarLeft())
        <x-livewire-tables::includes.actions/>
    @endif

    @if ($this->hasConfigurableAreaFor('toolbar-left-end'))
        <div x-cloak x-show="!currentlyReorderingStatus">
            @include($this->getConfigurableAreaFor('toolbar-left-end'), $this->getParametersForConfigurableArea('toolbar-left-end'))
        </div>
    @endif

    <div x-cloak x-show="!currentlyReorderingStatus" class="flex flex-wrap items-end gap-2 ml-auto">
        @includeWhen($this->hasConfigurableAreaFor('toolbar-right-start'), $this->getConfigurableAreaFor('toolbar-right-start'), $this->getParametersForConfigurableArea('toolbar-right-start'))

        @if($this->showActionsInToolbarRight())
            <x-livewire-tables::includes.actions/>
        @endif

        @if ($this->showBulkActionsDropdownAlpine() && $this->shouldAlwaysHideBulkActionsDropdownOption != true)
            <x-livewire-tables::tools.toolbar.items.bulk-actions />
        @endif

        @if ($this->columnSelectIsEnabled)
            <x-livewire-tables::tools.toolbar.items.column-select />
        @endif

        @includeWhen($this->hasConfigurableAreaFor('toolbar-right-end'), $this->getConfigurableAreaFor('toolbar-right-end'), $this->getParametersForConfigurableArea('toolbar-right-end'))
    </div>
</div>
