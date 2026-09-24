<?php

namespace App\Models;

use App\Enums\FournisseurPaiement;
use App\Enums\StatutTransaction;
use App\Enums\TypeTransactionPaiement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'adherent_id', 'type', 'payable_type', 'payable_id', 'cotisation_id', 'montant',
    'fournisseur', 'reference_fournisseur', 'reference_interne', 'statut',
    'payload_retour', 'initie_le', 'complete_le',
])]
class TransactionPaiement extends Model
{
    use HasFactory;

    protected $table = 'transactions_paiement';

    protected function casts(): array
    {
        return [
            'type' => TypeTransactionPaiement::class,
            'fournisseur' => FournisseurPaiement::class,
            'statut' => StatutTransaction::class,
            'payload_retour' => 'array',
            'initie_le' => 'datetime',
            'complete_le' => 'datetime',
        ];
    }

    public function adherent(): BelongsTo
    {
        return $this->belongsTo(Adherent::class);
    }

    public function cotisation(): BelongsTo
    {
        return $this->belongsTo(Cotisation::class);
    }

    /**
     * Bénéficiaire du droit d'adhésion payé (l'adhérent lui-même ou l'une
     * de ses personnes à charge). Sans objet pour une transaction de type
     * cotisation.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
