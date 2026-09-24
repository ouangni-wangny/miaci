<?php

namespace App\Models;

use App\Enums\StatutDroitAdhesion;
use App\Enums\StatutPersonneACharge;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'adherent_id', 'nom', 'prenom', 'date_naissance', 'nee_apres_1956', 'lien_parente',
    'date_adhesion', 'date_fin_carence_indicative', 'statut', 'piece_justificative_path',
    'valide_par_gestionnaire', 'valide_par', 'valide_le',
])]
class PersonneACharge extends Model
{
    use HasFactory;

    protected $table = 'personnes_a_charge';

    protected function casts(): array
    {
        return [
            'statut' => StatutPersonneACharge::class,
            'date_naissance' => 'date',
            'nee_apres_1956' => 'boolean',
            'date_adhesion' => 'date',
            'date_fin_carence_indicative' => 'date',
            'valide_par_gestionnaire' => 'boolean',
            'valide_le' => 'datetime',
            'droit_adhesion_exonere' => 'boolean',
        ];
    }

    public function adherent(): BelongsTo
    {
        return $this->belongsTo(Adherent::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function piecesJustificatives(): MorphMany
    {
        return $this->morphMany(PieceJustificative::class, 'justificable');
    }

    public function droitsAdhesion(): MorphMany
    {
        return $this->morphMany(DroitAdhesion::class, 'payable');
    }

    public function droitAdhesionPaye(): bool
    {
        if ($this->droit_adhesion_exonere) {
            return true;
        }

        $droits = $this->relationLoaded('droitsAdhesion') ? $this->droitsAdhesion : $this->droitsAdhesion()->get();

        return $droits->contains(fn (DroitAdhesion $d) => $d->statut === StatutDroitAdhesion::Valide);
    }

    /**
     * Une personne à charge est comptée comme cotisante dès que son droit
     * d'adhésion est payé (ou exonéré) — il n'y a plus de validation
     * manuelle par un gestionnaire.
     */
    public function estActiveEtValidee(): bool
    {
        return $this->statut === StatutPersonneACharge::Active && $this->droitAdhesionPaye();
    }

    public function nomComplet(): string
    {
        return "{$this->prenom} {$this->nom}";
    }
}
