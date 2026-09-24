<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'libelle', 'description', 'plafond_montant', 'delai_carence_mois',
    'cotisation_a_jour_requise', 'actif',
])]
class TypeSinistre extends Model
{
    use HasFactory;

    protected $table = 'types_sinistre';

    protected function casts(): array
    {
        return [
            'cotisation_a_jour_requise' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function piecesRequises(): HasMany
    {
        return $this->hasMany(PieceRequiseTypeSinistre::class);
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeSinistre::class);
    }
}
