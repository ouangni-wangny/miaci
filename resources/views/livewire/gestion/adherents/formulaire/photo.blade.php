{{-- Photo de l'adhérent (partagée création / modification). --}}
@php
    $apercu = $photo ? rescue(fn () => $photo->temporaryUrl(), null, false) : null;
    $photoActuelle = $adherent?->photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($adherent->photo_path) : null;
@endphp

<div class="flex flex-wrap items-center gap-5">
    @if ($apercu || $photoActuelle)
        <img src="{{ $apercu ?? $photoActuelle }}" alt="Photo" class="h-24 w-24 rounded-2xl object-cover ring-1 ring-gray-200 shadow-sm">
    @else
        <span class="flex h-24 w-24 items-center justify-center rounded-2xl bg-gray-50 text-gray-300 ring-1 ring-gray-200">
            <x-heroicon-o-user class="w-10 h-10" />
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <x-input-label for="photo" value="Photo (optionnel)" />
        <input type="file" wire:model="photo" id="photo" accept="image/*"
               class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-xl file:border-0 file:bg-primary-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100">
        <p class="mt-1 text-xs text-gray-500">
            Format portrait, au moins 600 x 750 pixels (minimum accepté : 300 x 370), visage bien centré,
            pour un rendu net sur la carte de membre imprimée.
        </p>
        <div wire:loading wire:target="photo" class="mt-1 text-xs text-primary-600">Envoi de la photo…</div>
        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
    </div>
</div>
