<?php

namespace App\Models;

use App\Enums\ModePaiement;
use App\Enums\StatutCotisation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'adherent_id', 'montant', 'date_paiement', 'periode_debut', 'periode_fin',
    'mode_paiement', 'reference', 'enregistre_par', 'statut', 'transaction_paiement_id',
])]
class Cotisation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'mode_paiement' => ModePaiement::class,
            'statut' => StatutCotisation::class,
            'date_paiement' => 'date',
            'periode_debut' => 'date',
            'periode_fin' => 'date',
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

    public function transactionPaiement(): BelongsTo
    {
        return $this->belongsTo(TransactionPaiement::class);
    }

    public function scopeValides(Builder $query): Builder
    {
        return $query->where('statut', StatutCotisation::Valide);
    }
}
