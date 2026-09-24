<?php

namespace App\Models;

use App\Enums\BeneficiaireType;
use App\Enums\PreresultatEligibilite;
use App\Enums\StatutDemandeSinistre;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'adherent_id', 'type_sinistre_id', 'beneficiaire_type', 'personne_a_charge_id',
    'description', 'montant_demande', 'montant_accorde', 'date_evenement', 'statut',
    'preresultat_eligibilite', 'motif_preresultat', 'motif_decision', 'traite_par', 'traite_le',
])]
class DemandeSinistre extends Model
{
    use HasFactory;

    protected $table = 'demandes_sinistre';

    protected function casts(): array
    {
        return [
            'beneficiaire_type' => BeneficiaireType::class,
            'statut' => StatutDemandeSinistre::class,
            'preresultat_eligibilite' => PreresultatEligibilite::class,
            'date_evenement' => 'date',
            'traite_le' => 'datetime',
        ];
    }

    public function adherent(): BelongsTo
    {
        return $this->belongsTo(Adherent::class);
    }

    public function typeSinistre(): BelongsTo
    {
        return $this->belongsTo(TypeSinistre::class);
    }

    public function personneACharge(): BelongsTo
    {
        return $this->belongsTo(PersonneACharge::class);
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function piecesJustificatives(): MorphMany
    {
        return $this->morphMany(PieceJustificative::class, 'justificable');
    }

    public function nomBeneficiaire(): string
    {
        return $this->beneficiaire_type === BeneficiaireType::Adherent
            ? $this->adherent->nomComplet()
            : $this->personneACharge->nomComplet();
    }
}
