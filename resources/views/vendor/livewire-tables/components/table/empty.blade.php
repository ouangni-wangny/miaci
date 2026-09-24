@aware(['isTailwind','isBootstrap','localisationPath'])

@php($attributes = $attributes->merge(['wire:key' => 'empty-message-'.$this->getId()]))

@if ($isTailwind)
    <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            <div class="flex flex-col items-center justify-center gap-2 py-12 text-center">
                <x-heroicon-o-magnifying-glass class="w-8 h-8 text-gray-300" />
                <span class="text-sm font-medium text-gray-500">{{ $this->getEmptyMessage() }}</span>
                @if ($this->hasSearch() || $this->hasAppliedFiltersWithValues())
                    <span class="text-xs text-gray-400">{{ __($localisationPath.'No items found, try to broaden your search') }}</span>
                @endif
            </div>
        </td>
    </tr>
@elseif ($isBootstrap)
     <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            {{ $this->getEmptyMessage() }}
        </td>
    </tr>
@endif
