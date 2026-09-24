<?php

namespace App\Models;

use App\Enums\FrequenceCotisation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['montant', 'droit_adhesion', 'frequence', 'date_debut', 'actif', 'created_by'])]
class ParametreCotisation extends Model
{
    use HasFactory;

    protected $table = 'parametres_cotisation';

    protected function casts(): array
    {
        return [
            'frequence' => FrequenceCotisation::class,
            'date_debut' => 'date',
            'actif' => 'boolean',
        ];
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mis en cache pour la durée de la requête (via le helper once()) : ce
     * paramétrage est lu très souvent (une fois par adhérent sur des écrans
     * qui les parcourent tous, ex. tableau de bord) alors qu'il ne change
     * qu'exceptionnellement. Tout code qui modifie le paramétrage actif doit
     * appeler oublierCacheActuel() juste après, sous peine de relire ici une
     * valeur périmée pour le reste de la requête (voir
     * Gestion\Parametres\Cotisation).
     */
    public static function actuel(): ?self
    {
        return once(fn () => static::query()->where('actif', true)->latest('date_debut')->first());
    }

    public static function oublierCacheActuel(): void
    {
        \Illuminate\Support\Once::flush();
    }
}
