<?php

namespace Tests\Feature\MonEspace;

use App\Enums\FrequenceCotisation;
use App\Enums\PreresultatEligibilite;
use App\Enums\Role;
use App\Livewire\MonEspace\Sinistres\Soumettre;
use App\Models\Adherent;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Models\TypeSinistre;
use App\Models\User;
use App\Services\CotisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class SinistreSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);

        ParametreCotisation::factory()->create([
            'montant' => 1000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(2),
        ]);
    }

    private function creerAdherentConnecte(array $attrs = []): Adherent
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);

        return Adherent::factory()->create([
            'user_id' => $user->id,
            'date_adhesion' => now()->subYears(2),
            ...$attrs,
        ]);
    }

    public function test_submission_is_probably_eligible_when_all_conditions_met(): void
    {
        $adherent = $this->creerAdherentConnecte();
        // À jour de cotisation : tous les mois échus (hors mois en cours)
        // réellement couverts, via l'allocation période par période — un
        // montant global sur une seule période ne suffit plus (voir
        // CotisationService::estAJour, basé sur moisArrieres()).
        app(CotisationService::class)->allouerPaiement(
            $adherent, 1000 * 25, now(), 'especes', null, null,
        );

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 6, 'cotisation_a_jour_requise' => true]);
        $type->piecesRequises()->create(['libelle' => 'Certificat médical', 'obligatoire' => true]);

        $demande = Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'adherent')
            ->set('description', 'Hospitalisation suite à un accident.')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->set('fichiers.'.$type->piecesRequises->first()->id, UploadedFile::fake()->create('certificat.pdf', 100))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('demandes_sinistre', [
            'adherent_id' => $adherent->id,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementEligible->value,
        ]);
    }

    public function test_submission_is_probably_not_eligible_when_piece_missing(): void
    {
        $adherent = $this->creerAdherentConnecte();

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 0, 'cotisation_a_jour_requise' => false]);
        $type->piecesRequises()->create(['libelle' => 'Certificat médical', 'obligatoire' => true]);

        Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'adherent')
            ->set('description', 'Test')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->call('enregistrer')
            ->assertHasErrors(); // la pièce obligatoire manquante bloque la soumission
    }

    public function test_submission_is_probably_not_eligible_when_carence_not_respected(): void
    {
        $adherent = $this->creerAdherentConnecte(['date_adhesion' => now()->subMonth()]);

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 12, 'cotisation_a_jour_requise' => false]);

        Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'adherent')
            ->set('description', 'Test')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('demandes_sinistre', [
            'adherent_id' => $adherent->id,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementNonEligible->value,
        ]);
    }

    public function test_manually_set_date_fin_carence_overrides_the_automatic_carence_check(): void
    {
        // Né après 1956 : carence réglementaire de base = 8 mois. Sans la
        // date forcée, 1 mois d'ancienneté ne suffirait pas (voir
        // test_submission_is_probably_not_eligible_when_carence_not_respected).
        // delai_carence_mois: 0 isole ce test de l'exigence propre au type
        // de sinistre, pour ne tester que la carence réglementaire
        // (overridable) de la mutuelle — même construction que les tests
        // de carence par âge ci-dessous.
        $adherent = $this->creerAdherentConnecte([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonth(),
            'date_fin_carence_indicative' => now()->subDay(),
        ]);

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 0, 'cotisation_a_jour_requise' => false]);

        Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'adherent')
            ->set('description', 'Test')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('demandes_sinistre', [
            'adherent_id' => $adherent->id,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementEligible->value,
        ]);
    }

    public function test_carence_is_evaluated_on_the_personne_a_charge_own_age_and_seniority(): void
    {
        // Le tuteur est ancien et sans problème de carence : si le bug
        // consistant à toujours vérifier son ancienneté à lui persistait, la
        // demande passerait à tort.
        $adherent = $this->creerAdherentConnecte(['date_adhesion' => now()->subYears(5)]);

        // La personne à charge, elle, est arrivée récemment et est née
        // avant 1956 (carence de base : 12 mois) : à 9 mois d'ancienneté
        // propre, elle n'est pas encore éligible.
        $personne = PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'date_naissance' => '1950-01-01',
            'date_adhesion' => now()->subMonths(9),
            'valide_par_gestionnaire' => true,
        ]);

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 0, 'cotisation_a_jour_requise' => false]);

        Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'personne_a_charge')
            ->set('personne_a_charge_id', (string) $personne->id)
            ->set('description', 'Test')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('demandes_sinistre', [
            'personne_a_charge_id' => $personne->id,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementNonEligible->value,
        ]);
    }

    public function test_carence_is_met_when_personne_a_charge_own_seniority_covers_her_own_delay(): void
    {
        $adherent = $this->creerAdherentConnecte(['date_adhesion' => now()->subYears(5)]);
        // Foyer à jour de cotisation, mois par mois depuis le début du
        // paramétrage : aucune pénalité de retard ne doit s'ajouter au délai
        // de carence de base testé ici (moisArrieres() vérifie l'alignement
        // période par période, pas seulement le total payé).
        for ($i = 0; $i < 24; $i++) {
            $debutPeriode = now()->subYears(2)->addMonths($i)->startOfMonth();
            $adherent->cotisations()->create([
                'montant' => 1000, 'date_paiement' => $debutPeriode, 'periode_debut' => $debutPeriode,
                'periode_fin' => $debutPeriode->copy()->endOfMonth(), 'mode_paiement' => 'especes', 'statut' => 'valide',
            ]);
        }

        // Née après 1956 (carence de base : 8 mois) et arrivée depuis 9
        // mois : sa propre ancienneté couvre son propre délai.
        $personne = PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'date_naissance' => '1990-01-01',
            'date_adhesion' => now()->subMonths(9),
            'valide_par_gestionnaire' => true,
        ]);

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 0, 'cotisation_a_jour_requise' => false]);

        Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'personne_a_charge')
            ->set('personne_a_charge_id', (string) $personne->id)
            ->set('description', 'Test')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('demandes_sinistre', [
            'personne_a_charge_id' => $personne->id,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementEligible->value,
        ]);
    }

    public function test_cannot_submit_for_unvalidated_personne_a_charge(): void
    {
        $adherent = $this->creerAdherentConnecte();
        $personne = PersonneACharge::factory()->create(['adherent_id' => $adherent->id, 'valide_par_gestionnaire' => false]);

        $type = TypeSinistre::factory()->create(['delai_carence_mois' => 0, 'cotisation_a_jour_requise' => false]);

        Livewire::actingAs($adherent->user)
            ->test(Soumettre::class)
            ->set('type_sinistre_id', (string) $type->id)
            ->set('beneficiaire_type', 'personne_a_charge')
            ->set('personne_a_charge_id', (string) $personne->id)
            ->set('description', 'Test')
            ->set('montant_demande', 50000)
            ->set('date_evenement', now()->format('Y-m-d'))
            ->call('enregistrer')
            ->assertStatus(422);
    }
}
