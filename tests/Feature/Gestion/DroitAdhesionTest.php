<?php

namespace Tests\Feature\Gestion;

use App\Enums\FrequenceCotisation;
use App\Enums\Role;
use App\Livewire\Gestion\Adherents\Fiche;
use App\Livewire\Gestion\Adherents\Formulaire;
use App\Models\Adherent;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge as PersonneAChargeModel;
use App\Models\User;
use App\Services\CotisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class DroitAdhesionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);

        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'droit_adhesion' => 11000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(2),
        ]);
    }

    public function test_new_adherent_owes_droit_adhesion_by_default(): void
    {
        $adherent = Adherent::factory()->create();

        $this->assertFalse($adherent->droitAdhesionPaye());
    }

    public function test_new_personne_a_charge_owes_droit_adhesion_by_default(): void
    {
        $adherent = Adherent::factory()->create();
        $personne = PersonneAChargeModel::factory()->create(['adherent_id' => $adherent->id]);

        $this->assertFalse($personne->droitAdhesionPaye());
    }

    public function test_gestionnaire_personne_a_charge_creation_sets_date_adhesion_to_today(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);
        $adherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulairePersonne')
            ->set('personne_nom', 'Kouassi')
            ->set('personne_prenom', 'Marie')
            ->set('personne_date_naissance', '2010-01-01')
            ->set('personne_lien_parente', 'Enfant')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();

        $personne = PersonneAChargeModel::where('nom', 'Kouassi')->firstOrFail();
        $this->assertSame(now()->toDateString(), $personne->date_adhesion->toDateString());
        $this->assertFalse($personne->droitAdhesionPaye());
    }

    public function test_a_personne_a_charge_cannot_be_registered_under_two_different_adherents(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $premierAdherent = Adherent::factory()->create();
        PersonneAChargeModel::factory()->create([
            'adherent_id' => $premierAdherent->id,
            'nom' => 'Kouassi',
            'prenom' => 'Marie',
            'date_naissance' => '2010-01-01',
        ]);

        $autreAdherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $autreAdherent])
            ->call('ouvrirFormulairePersonne')
            ->set('personne_nom', 'kouassi')
            ->set('personne_prenom', 'MARIE')
            ->set('personne_date_naissance', '2010-01-01')
            ->call('enregistrerPersonne')
            ->assertHasErrors('personne_nom');

        $this->assertSame(1, PersonneAChargeModel::where('nom', 'Kouassi')->count());
    }

    public function test_a_personne_a_charge_with_a_different_date_naissance_is_not_considered_a_duplicate(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $premierAdherent = Adherent::factory()->create();
        PersonneAChargeModel::factory()->create([
            'adherent_id' => $premierAdherent->id,
            'nom' => 'Kouassi',
            'prenom' => 'Marie',
            'date_naissance' => '2010-01-01',
        ]);

        $autreAdherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $autreAdherent])
            ->call('ouvrirFormulairePersonne')
            ->set('personne_nom', 'Kouassi')
            ->set('personne_prenom', 'Marie')
            ->set('personne_date_naissance', '2015-06-20')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();

        $this->assertSame(2, PersonneAChargeModel::where('nom', 'Kouassi')->count());
    }

    public function test_a_personne_a_charge_is_not_flagged_as_duplicate_when_the_existing_record_has_no_date_naissance_on_file(): void
    {
        // Contrairement à l'adhérent tuteur (où nom+prénom seuls suffisent
        // en repli faute de date), une personne à charge n'est signalée
        // comme doublon que si nom, prénom ET date de naissance
        // correspondent tous les trois : des homonymies existent déjà entre
        // adhérents différents dans les données importées (noms très
        // courants), sans être de vrais doublons.
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $premierAdherent = Adherent::factory()->create();
        PersonneAChargeModel::factory()->create([
            'adherent_id' => $premierAdherent->id,
            'nom' => 'Kouassi',
            'prenom' => 'Marie',
            'date_naissance' => null,
        ]);

        $autreAdherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $autreAdherent])
            ->call('ouvrirFormulairePersonne')
            ->set('personne_nom', 'Kouassi')
            ->set('personne_prenom', 'Marie')
            ->set('personne_date_naissance', '2010-01-01')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();
    }

    public function test_a_personne_a_charge_without_any_date_naissance_entered_is_never_flagged_as_duplicate(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $premierAdherent = Adherent::factory()->create();
        PersonneAChargeModel::factory()->create([
            'adherent_id' => $premierAdherent->id,
            'nom' => 'Kouassi',
            'prenom' => 'Marie',
            'date_naissance' => '2010-01-01',
        ]);

        $autreAdherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $autreAdherent])
            ->call('ouvrirFormulairePersonne')
            ->set('personne_nom', 'Kouassi')
            ->set('personne_prenom', 'Marie')
            ->set('personne_date_naissance', '')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();
    }

    public function test_editing_an_existing_personne_a_charge_never_triggers_the_duplicate_check(): void
    {
        // La vérification ne s'applique qu'à la création (voir
        // enregistrerPersonne()) : des homonymies entre adhérents différents
        // existent déjà dans les données importées (noms courants) sans être
        // de vrais doublons — les revalider à chaque modification bloquerait
        // des retouches anodines sur des fiches déjà correctement saisies.
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $autreAdherent = Adherent::factory()->create();
        PersonneAChargeModel::factory()->create([
            'adherent_id' => $autreAdherent->id,
            'nom' => 'Kouassi',
            'prenom' => 'Marie',
            'date_naissance' => '2010-01-01',
        ]);

        $adherent = Adherent::factory()->create();
        $personne = PersonneAChargeModel::factory()->create([
            'adherent_id' => $adherent->id,
            'nom' => 'Kouassi',
            'prenom' => 'Marie',
            'date_naissance' => '2010-01-01',
        ]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulairePersonne', $personne->id)
            ->set('personne_lien_parente', 'Fille')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();

        $this->assertSame('Fille', $personne->fresh()->lien_parente);
    }

    public function test_gestionnaire_can_change_date_adhesion_of_a_personne_a_charge(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);
        $adherent = Adherent::factory()->create();

        $personne = PersonneAChargeModel::factory()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => '2024-01-01',
        ]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulairePersonne', $personne->id)
            ->assertSet('personne_date_adhesion', '2024-01-01')
            ->set('personne_date_adhesion', '2020-06-15')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();

        $this->assertSame('2020-06-15', $personne->fresh()->date_adhesion->format('Y-m-d'));
    }

    public function test_admin_can_set_date_fin_carence_of_the_adherent(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create(['date_fin_carence_indicative' => null]);

        Livewire::actingAs($admin)
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->set('date_fin_carence_indicative', '2027-03-01')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertSame('2027-03-01', $adherent->fresh()->date_fin_carence_indicative->format('Y-m-d'));
    }

    public function test_admin_can_clear_date_fin_carence_of_the_adherent(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create(['date_fin_carence_indicative' => '2027-03-01']);

        Livewire::actingAs($admin)
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->set('date_fin_carence_indicative', '')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertNull($adherent->fresh()->date_fin_carence_indicative);
    }

    public function test_gestionnaire_can_set_date_fin_carence_of_a_personne_a_charge(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);
        $adherent = Adherent::factory()->create();

        $personne = PersonneAChargeModel::factory()->create([
            'adherent_id' => $adherent->id,
            'date_fin_carence_indicative' => null,
        ]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulairePersonne', $personne->id)
            ->set('personne_date_fin_carence_indicative', '2027-03-01')
            ->call('enregistrerPersonne')
            ->assertHasNoErrors();

        $this->assertSame('2027-03-01', $personne->fresh()->date_fin_carence_indicative->format('Y-m-d'));
    }

    public function test_gestionnaire_can_record_droit_adhesion_for_the_adherent(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireDroitAdhesion', 'adherent')
            ->set('droit_adhesion_date_paiement', now()->format('Y-m-d'))
            ->set('droit_adhesion_mode_paiement', 'especes')
            ->call('enregistrerDroitAdhesion')
            ->assertHasNoErrors();

        $this->assertTrue($adherent->fresh()->droitAdhesionPaye());
        $this->assertDatabaseHas('droits_adhesion', [
            'payable_type' => Adherent::class,
            'payable_id' => $adherent->id,
            'montant' => 11000,
            'enregistre_par' => $gestionnaire->id,
        ]);
    }

    public function test_gestionnaire_can_record_droit_adhesion_for_a_personne_a_charge(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();
        $personne = PersonneAChargeModel::factory()->create(['adherent_id' => $adherent->id]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireDroitAdhesion', 'personne', $personne->id)
            ->set('droit_adhesion_date_paiement', now()->format('Y-m-d'))
            ->set('droit_adhesion_mode_paiement', 'especes')
            ->call('enregistrerDroitAdhesion')
            ->assertHasNoErrors();

        $this->assertTrue($personne->fresh()->droitAdhesionPaye());
    }

    public function test_admin_can_cancel_droit_adhesion_of_the_adherent(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create();
        $adherent->droitsAdhesion()->create([
            'montant' => 11000,
            'date_paiement' => now()->format('Y-m-d'),
            'mode_paiement' => 'especes',
        ]);

        $this->assertTrue($adherent->fresh()->droitAdhesionPaye());

        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerDroitAdhesion', 'adherent', null)
            ->assertHasNoErrors();

        $this->assertFalse($adherent->fresh()->droitAdhesionPaye());
    }

    public function test_admin_can_cancel_droit_adhesion_of_a_personne_a_charge(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create();
        $personne = PersonneAChargeModel::factory()->create(['adherent_id' => $adherent->id]);
        $personne->droitsAdhesion()->create([
            'montant' => 11000,
            'date_paiement' => now()->format('Y-m-d'),
            'mode_paiement' => 'especes',
        ]);

        $this->assertTrue($personne->fresh()->droitAdhesionPaye());

        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerDroitAdhesion', 'personne', $personne->id)
            ->assertHasNoErrors();

        $this->assertFalse($personne->fresh()->droitAdhesionPaye());
    }

    public function test_gestionnaire_cannot_cancel_droit_adhesion(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();
        $adherent->droitsAdhesion()->create([
            'montant' => 11000,
            'date_paiement' => now()->format('Y-m-d'),
            'mode_paiement' => 'especes',
        ]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerDroitAdhesion', 'adherent', null)
            ->assertForbidden();

        $this->assertTrue($adherent->fresh()->droitAdhesionPaye());
    }

    public function test_a_new_droit_adhesion_can_be_registered_after_cancelling_the_previous_one(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create();
        $adherent->droitsAdhesion()->create([
            'montant' => 11000,
            'date_paiement' => now()->subDay()->format('Y-m-d'),
            'mode_paiement' => 'especes',
        ]);

        Livewire::actingAs($admin)->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerDroitAdhesion', 'adherent', null);

        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireDroitAdhesion', 'adherent')
            ->set('droit_adhesion_date_paiement', now()->format('Y-m-d'))
            ->set('droit_adhesion_mode_paiement', 'especes')
            ->call('enregistrerDroitAdhesion')
            ->assertHasNoErrors();

        $this->assertTrue($adherent->fresh()->droitAdhesionPaye());
    }

    public function test_solde_counts_each_validated_personne_a_charge_as_a_full_cotisant(): void
    {
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subMonths(2)->startOfMonth()]);

        // Une personne à charge validée : cotise aussi depuis la même date.
        PersonneAChargeModel::factory()->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => $adherent->date_adhesion,
        ]);
        // Une personne à charge non validée : ne doit pas compter.
        PersonneAChargeModel::factory()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => $adherent->date_adhesion,
        ]);

        $solde = app(CotisationService::class)->calculerSolde($adherent);

        // 3 périodes échues (mois courant + 2 précédents) × 4500 × 2 cotisants.
        $this->assertSame(3 * 4500 * 2, $solde['du']);
    }
}
