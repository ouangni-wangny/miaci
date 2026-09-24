{{-- Champs d'adhésion : statut, date d'adhésion, fin de carence forcée
     (partagés création / modification). --}}
<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="matricule" value="Matricule" />
        @if ($adherent)
            <x-text-input :value="$matricule" id="matricule" class="mt-1 block w-full bg-gray-50" disabled />
            <p class="mt-1 text-xs text-gray-500">Le matricule n'est pas modifiable.</p>
        @else
            <p class="mt-1 flex items-center h-[42px] px-3 rounded-xl border border-dashed border-gray-300 text-sm text-gray-500">
                Généré à partir de la date d'adhésion
            </p>
        @endif
    </div>

    <div>
        <x-input-label for="statut" value="Statut" />
        <x-select-input wire:model="statut" id="statut" class="mt-1 block w-full">
            @foreach ($statuts as $s)
                <option value="{{ $s->value }}">{{ $s->libelle() }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('statut')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date_adhesion" value="Date d'adhésion" />
        <x-text-input type="date" wire:model="date_adhesion" id="date_adhesion" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('date_adhesion')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date_fin_carence_indicative" value="Date de fin de carence (forcée)" />
        <x-text-input type="date" wire:model="date_fin_carence_indicative" id="date_fin_carence_indicative" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('date_fin_carence_indicative')" class="mt-2" />
    </div>

    <p class="sm:col-span-2 flex items-start gap-2 rounded-xl bg-gray-50 p-3 text-xs text-gray-600">
        <x-heroicon-o-information-circle class="w-4 h-4 shrink-0 text-gray-400" />
        <span>
            Laisser la fin de carence vide pour un calcul automatique selon l'âge. Une date saisie ici remplace ce calcul
            partout (sinistres, don de fin d'année) — mais toute pénalité de retard de cotisation (passée ou future)
            continue de s'ajouter par-dessus automatiquement, ne l'inclus pas dans la date saisie.
        </span>
    </p>
</div>
