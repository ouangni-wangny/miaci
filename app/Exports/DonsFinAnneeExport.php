<?php

namespace App\Exports;

use App\Models\Adherent;
use App\Models\DonFinAnnee;
use App\Services\CotisationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Bénéficiaires du don de fin d'année (Article 7) : adhérents actifs, à
 * jour de leurs cotisations, ayant respecté leur délai de carence, dont le
 * carnet compte au moins 3 membres (eux-mêmes + au moins 2 personnes à
 * charge validées) et dont au moins 2 de ces personnes à charge ont
 * elles-mêmes fini leur propre délai de carence.
 */
class DonsFinAnneeExport implements FromCollection, WithHeadings
{
    public function __construct(private readonly int $annee) {}

    public function collection(): Collection
    {
        $cotisationService = app(CotisationService::class);

        $eligibles = Adherent::query()
            ->actifs()
            ->with(['personnesACharge.droitsAdhesion', 'cotisations'])
            ->get()
            ->filter(fn (Adherent $a) => $a->eligibleDonFinAnnee($cotisationService))
            ->sortBy('nom')
            ->values();

        $verses = DonFinAnnee::where('annee', $this->annee)->pluck('adherent_id')->all();

        return $eligibles->values()->map(fn (Adherent $a, int $index) => [
            $index + 1,
            $a->matricule,
            $a->nomComplet(),
            $a->telephone ?: '—',
            $a->tailleCarnet(),
            $a->ville ?: '—',
            in_array($a->id, $verses) ? 'Versé' : 'En attente',
        ]);
    }

    public function headings(): array
    {
        return ["N° d'ordre", 'Matricule', 'Nom complet', 'Téléphone', 'Personnes à charge', 'Ville', "Statut {$this->annee}"];
    }
}
