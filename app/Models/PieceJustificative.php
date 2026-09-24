<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'justificable_type', 'justificable_id', 'piece_requise_type_sinistre_id',
    'fichier_path', 'nom_original', 'type_mime', 'taille', 'uploaded_by',
])]
class PieceJustificative extends Model
{
    protected $table = 'pieces_justificatives';

    public function justificable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function pieceRequise(): BelongsTo
    {
        return $this->belongsTo(PieceRequiseTypeSinistre::class, 'piece_requise_type_sinistre_id');
    }
}
