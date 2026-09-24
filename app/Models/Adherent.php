<?php

namespace App\Models;

use App\Enums\Sexe;
use App\Enums\StatutAdherent;
use App\Enums\StatutDroitAdhesion;
use App\Services\CotisationService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

#[Fillable([
    'user_id', 'matricule', 'nom', 'prenom', 'sexe', 'date_naissance', 'nee_apres_1956',
    'telephone', 'email', 'etablissement', 'ville', 'fonction', 'date_adhesion',
    'date_fin_carence_indicative', 'statut', 'photo_path',
    'ayant_droit_nom', 'ayant_droit_telephone',
])]
class Adherent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sexe' => Sexe::class,
            'statut' => StatutAdherent::class,
            'date_naissance' => 'date',
            'date_adhesion' => 'date',
            'date_fin_carence_indicative' => 'date',
            'nee_apres_1956' => 'boolean',
            'droit_adhesion_exonere' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

        // Relation déjà chargée (eager load explicite d'un appelant en
        // lecture seule, ex. tableau de bord) sinon requête neuve à chaque
        // fois — jamais mise en cache nous-mêmes ici, voir le commentaire
        // équivalent dans CotisationService::personnesACharge().
        $droits = $this->relationLoaded('droitsAdhesion') ? $this->droitsAdhesion : $this->droitsAdhesion()->get();

        return $droits->contains(fn (DroitAdhesion $d) => $d->statut === StatutDroitAdhesion::Valide);
    }

    public function personnesACharge(): HasMany
    {
        return $this->hasMany(PersonneACharge::class);
    }

    public function cotisations(): HasMany
    {
        return $this->hasMany(Cotisation::class);
    }

    public function transactionsPaiement(): HasMany
    {
        return $this->hasMany(TransactionPaiement::class);
    }

    public function demandesSinistre(): HasMany
    {
        return $this->hasMany(DemandeSinistre::class);
    }

    public function donsFinAnnee(): HasMany
    {
        return $this->hasMany(DonFinAnnee::class);
    }

    public function nomComplet(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    /**
     * Personne à qui remettre le montant cotisé par l'adhérent en cas de
     * décès ou de disparition (Article 7 : assistance décès versée au
     * bénéficiaire déclaré).
     */
    public function aUnAyantDroit(): bool
    {
        return filled($this->ayant_droit_nom);
    }

    /**
     * Nombre de personnes à charge validées ("carnet"), déterminant pour
     * les seuils du règlement (dons de fin d'année, barème des assistances).
     */
    public function tailleCarnet(): int
    {
        return $this->personnesAChargeChargees()
            ->filter(fn (PersonneACharge $personne) => $personne->estActiveEtValidee())
            ->count();
    }

    /**
     * Relation déjà chargée si un appelant en lecture seule l'a fait
     * explicitement (eager load), sinon requête neuve — jamais mise en
     * cache ici (voir CotisationService::personnesACharge() pour le détail
     * du raisonnement).
     *
     * @return Collection<int, PersonneACharge>
     */
    private function personnesAChargeChargees(): Collection
    {
        return $this->relationLoaded('personnesACharge') ? $this->personnesACharge : $this->personnesACharge()->get();
    }

    /**
     * Article 7 : le don de fin d'année fait partie de la liste des
     * "Assistances", introduite par une condition commune à toutes —
     * « Tous les membres à jour de leurs cotisations, remplissant les
     * conditions de délais de carence... recevront (...) Dons de 10 000f (...)
     * pour les adhérents ayant au moins trois (03) membres dans leur
     * carnet. » Le "carnet" compte l'adhérent lui-même comme premier
     * membre : il faut donc au moins 2 personnes à charge en plus de lui
     * pour atteindre les 3 membres — pas 3 personnes à charge en plus de
     * lui, et pas davantage même si le carnet en compte plus de 2 (un
     * adhérent peut avoir un carnet de plusieurs dizaines de personnes :
     * seules 2 d'entre elles, au minimum, doivent avoir fini leur propre
     * délai de carence).
     *
     * Conditions vérifiées : l'adhérent actif, à jour de cotisation
     * (foyer), ayant lui-même fini son délai de carence (Article 7 : 8 ou
     * 12 mois selon l'âge, majoré d'une éventuelle pénalité de retard), et
     * dont au moins 2 personnes à charge validées ont elles aussi fini leur
     * propre délai de carence (chacune cotise comme un membre à part
     * entière, selon sa propre date de naissance et sa propre date
     * d'adhésion au carnet).
     */
    public function eligibleDonFinAnnee(CotisationService $cotisationService): bool
    {
        if (! $this->estActif() || $this->tailleCarnet() < 2) {
            return false;
        }

        if (! $cotisationService->estAJour($this)) {
            return false;
        }

        if (now()->lt($cotisationService->dateFinCarence($this))) {
            return false;
        }

        $personnesCarenceEcoulee = $this->personnesAChargeChargees()
            ->filter(fn (PersonneACharge $personne) => $personne->estActiveEtValidee())
            ->filter(fn (PersonneACharge $personne) => now()->gte($cotisationService->dateFinCarence($this, $personne)))
            ->count();

        return $personnesCarenceEcoulee >= 2;
    }

    /**
     * Un adhérent ayant déjà un historique (cotisations, sinistres,
     * personnes à charge, dons...) ne peut pas être supprimé sans perdre
     * cet historique financier réel : seule la radiation (statut) est
     * alors possible.
     */
    public function possedeHistorique(): bool
    {
        return $this->personnesACharge()->exists()
            || $this->cotisations()->exists()
            || $this->demandesSinistre()->exists()
            || $this->transactionsPaiement()->exists()
            || $this->donsFinAnnee()->exists()
            || $this->droitsAdhesion()->exists();
    }

    public function estActif(): bool
    {
        return $this->statut === StatutAdherent::Actif;
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', StatutAdherent::Actif);
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        if (blank($terme)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($terme) {
            $q->where('matricule', 'like', "%{$terme}%")
                ->orWhere('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('etablissement', 'like', "%{$terme}%");
        });
    }
}
