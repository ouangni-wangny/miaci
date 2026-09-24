<?php

namespace App\Exports;

use App\Models\Adherent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AdherentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Adherent::orderBy('nom')->get();
    }

    public function headings(): array
    {
        return [
            'Matricule', 'Nom', 'Prénom', 'Sexe', 'Date de naissance', 'Téléphone', 'Email',
            'Établissement', 'Fonction', "Date d'adhésion", 'Statut',
        ];
    }

    public function map($adherent): array
    {
        return [
            $adherent->matricule,
            $adherent->nom,
            $adherent->prenom,
            $adherent->sexe?->libelle() ?? '—',
            $adherent->date_naissance?->format('d/m/Y') ?? '—',
            $adherent->telephone,
            $adherent->email,
            $adherent->etablissement,
            $adherent->fonction,
            $adherent->date_adhesion->format('d/m/Y'),
            $adherent->statut->libelle(),
        ];
    }
}
