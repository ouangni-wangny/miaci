<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Don de fin d'année (Article 7) : 10 000 F versés le 20 décembre aux
 * adhérents dont le carnet compte au moins 3 membres (eux-mêmes + au moins
 * 2 personnes à charge validées, dont au moins 2 hors carence) — voir
 * Adherent::eligibleDonFinAnnee().
 */
#[Fillable(['adherent_id', 'annee', 'montant', 'date_versement', 'enregistre_par'])]
class DonFinAnnee extends Model
{
    use HasFactory;

    protected $table = 'dons_fin_annee';

    protected function casts(): array
    {
        return [
            'date_versement' => 'date',
        ];
    }

    public function adherent(): BelongsTo
    {
        return $this->belongsTo(Adherent::class);
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}
