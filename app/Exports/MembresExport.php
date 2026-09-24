<?php

namespace App\Exports;

use App\Models\Adherent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Liste exhaustive de tous les membres de la mutuelle — adhérents et
 * personnes à charge — numérotée en continu, avec la tutelle de chaque
 * personne à charge indiquée par le matricule de son adhérent.
 */
class MembresExport implements FromCollection, WithHeadings
{
    public function collection(): Collection
    {
        $adherents = Adherent::with(['personnesACharge' => fn ($q) => $q->orderBy('nom')])
            ->orderBy('nom')->orderBy('prenom')
            ->get();

        $lignes = collect();
        $ordre = 0;

        foreach ($adherents as $adherent) {
            $ordre++;
            $lignes->push([
                $ordre,
                'Adhérent tuteur',
                $adherent->matricule,
                $adherent->nomComplet(),
                '—',
                $adherent->telephone ?: '—',
                $adherent->ville ?: '—',
                $adherent->statut->libelle(),
                $adherent->date_adhesion->format('d/m/Y'),
            ]);

            foreach ($adherent->personnesACharge as $pac) {
                $ordre++;
                $lignes->push([
                    $ordre,
                    'Personne à charge',
                    $adherent->matricule,
                    $pac->nomComplet(),
                    $pac->lien_parente ?: '—',
                    $adherent->telephone ?: '—',
                    $adherent->ville ?: '—',
                    $pac->statut->value === 'active' ? 'Active' : 'Inactive',
                    $pac->date_adhesion?->format('d/m/Y') ?? '—',
                ]);
            }
        }

        $totalAdherents = $adherents->count();
        $totalPac = $adherents->sum(fn (Adherent $a) => $a->personnesACharge->count());

        $lignes->push(['', '', '', '', '', '', '', '', '']);
        $lignes->push([
            'TOTAL',
            $ordre.' membres',
            $totalAdherents.' adhérents',
            $totalPac.' personnes à charge',
            '', '', '', '', '',
        ]);

        return $lignes;
    }

    public function headings(): array
    {
        return [
            "N° d'ordre", 'Type', 'Matricule (ou tutelle pour une personne à charge)',
            'Nom complet', 'Lien de parenté', 'Téléphone', 'Ville', 'Statut', "Date d'adhésion",
        ];
    }
}
