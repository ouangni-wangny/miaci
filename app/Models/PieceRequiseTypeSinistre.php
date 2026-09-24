<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type_sinistre_id', 'libelle', 'obligatoire'])]
class PieceRequiseTypeSinistre extends Model
{
    protected $table = 'pieces_requises_type_sinistre';

    protected function casts(): array
    {
        return ['obligatoire' => 'boolean'];
    }

    public function typeSinistre(): BelongsTo
    {
        return $this->belongsTo(TypeSinistre::class);
    }
}
