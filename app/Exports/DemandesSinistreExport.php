<?php

namespace App\Exports;

use App\Models\DemandeSinistre;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * La description détaillée et les motifs (texte libre, pouvant contenir des
 * informations sensibles de santé) sont volontairement exclus de l'export :
 * ils restent consultables uniquement depuis la fiche de la demande.
 */
class DemandesSinistreExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return DemandeSinistre::with(['adherent', 'typeSinistre'])->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return ['Matricule', 'Adhérent tuteur', 'Type de sinistre', 'Bénéficiaire', "Date de l'événement", 'Montant demandé', 'Montant accordé', 'Statut', 'Soumise le'];
    }

    public function map($demande): array
    {
        return [
            $demande->adherent->matricule,
            $demande->adherent->nomComplet(),
            $demande->typeSinistre->libelle,
            $demande->beneficiaire_type->libelle(),
            $demande->date_evenement->format('d/m/Y'),
            $demande->montant_demande,
            $demande->montant_accorde,
            $demande->statut->libelle(),
            $demande->created_at->format('d/m/Y'),
        ];
    }
}
