<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Image de bannière affichée sur la page d'accueil publique, ajoutée
 * librement par l'admin depuis le back-office (Gestion > Paramètres > Site).
 */
#[Fillable(['image_path', 'ordre', 'actif'])]
class BanniereSite extends Model
{
    protected $table = 'bannieres_site';

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('actif', true)->orderBy('ordre');
    }
}
