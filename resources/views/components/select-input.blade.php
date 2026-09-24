@props(['disabled' => false])

{{-- Liste déroulante de formulaire, au même style que <x-text-input>.
     <x-select-input wire:model="statut" id="statut"> <option …> </x-select-input> --}}
<select @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-xl shadow-sm py-2.5']) }}>
    {{ $slot }}
</select>
