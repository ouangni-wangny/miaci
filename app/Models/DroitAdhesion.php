<?php

namespace App\Models;

use App\Enums\ModePaiement;
use App\Enums\StatutDroitAdhesion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Paiement (unique) du droit d'adhésion, dû par chaque adhérent et chaque
 * personne à charge lors de son entrée dans la mutuelle.
 */
#[Fillable([
    'payable_type', 'payable_id', 'montant', 'date_paiement',
    'mode_paiement', 'reference', 'enregistre_par', 'statut',
])]
class DroitAdhesion extends Model
{
    use HasFactory;

    protected $table = 'droits_adhesion';

    protected function casts(): array
    {
        return [
            'mode_paiement' => ModePaiement::class,
            'date_paiement' => 'date',
            'statut' => StatutDroitAdhesion::class,
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }
}
