<?php

namespace App\Exports;

use App\Models\Cotisation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CotisationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Cotisation::with('adherent')->orderByDesc('date_paiement')->get();
    }

    public function headings(): array
    {
        return ['Matricule', 'Adhérent tuteur', 'Montant', 'Date de paiement', 'Période début', 'Période fin', 'Mode de paiement', 'Référence', 'Statut'];
    }

    public function map($cotisation): array
    {
        return [
            $cotisation->adherent->matricule,
            $cotisation->adherent->nomComplet(),
            $cotisation->montant,
            $cotisation->date_paiement->format('d/m/Y'),
            $cotisation->periode_debut->format('d/m/Y'),
            $cotisation->periode_fin->format('d/m/Y'),
            $cotisation->mode_paiement->libelle(),
            $cotisation->reference,
            $cotisation->statut->libelle(),
        ];
    }
}
