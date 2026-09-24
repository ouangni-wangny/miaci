<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Export générique des lignes sélectionnées dans un DataTable : les
 * en-têtes et les valeurs sont ceux des colonnes affichées à l'écran (voir
 * App\Livewire\Tables\DataTable::exporterSelection()).
 */
class DataTableExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  array<int, string>  $entetes
     * @param  array<int, array<int, string>>  $lignes
     */
    public function __construct(private array $entetes, private array $lignes) {}

    public function array(): array
    {
        return $this->lignes;
    }

    public function headings(): array
    {
        return $this->entetes;
    }
}
