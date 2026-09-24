@if ($adherent->photo_path)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($adherent->photo_path) }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow-sm">
@else
    <span class="flex items-center justify-center h-9 w-9 rounded-full bg-primary-50 text-xs font-semibold text-primary-700">
        {{ strtoupper(mb_substr($adherent->prenom, 0, 1).mb_substr($adherent->nom, 0, 1)) }}
    </span>
@endif
