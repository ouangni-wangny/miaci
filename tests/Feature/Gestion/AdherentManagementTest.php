<?php

namespace Tests\Feature\Gestion;

use App\Enums\ModePaiement;
use App\Enums\Role;
use App\Enums\StatutCotisation;
use App\Livewire\Gestion\Adherents\Fiche;
use App\Livewire\Gestion\Adherents\AdherentsTable;
use App\Livewire\Gestion\Adherents\Formulaire;
use App\Models\Adherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdherentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_adherent_cannot_access_gestion_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);

        $this->actingAs($user)->get(route('gestion.adherents.index'))->assertForbidden();
    }

    public function test_gestionnaire_can_view_adherent_list(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Adherent::factory()->count(3)->create();

        $this->actingAs($gestionnaire)
            ->get(route('gestion.adherents.index'))
            ->assertOk();
    }

    public function test_gestionnaire_can_filter_adherents_by_ville(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Adherent::factory()->create(['nom' => 'Traore', 'ville' => 'Bouaké']);
        Adherent::factory()->create(['nom' => 'Diomande', 'ville' => 'Abidjan']);

        Livewire::actingAs($gestionnaire)
            ->test(AdherentsTable::class)
            ->set('filterComponents.ville', 'Bouaké')
            ->assertSee('Traore')
            ->assertDontSee('Diomande');
    }

    public function test_repartition_par_ville_counts_adherents(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Adherent::factory()->count(2)->create(['ville' => 'Bouaké']);
        Adherent::factory()->create(['ville' => 'Abidjan']);

        Livewire::actingAs($gestionnaire)
            ->test(AdherentsTable::class)
            ->assertSee('Bouaké · 2')
            ->assertSee('Abidjan · 1');
    }

    public function test_gestionnaire_can_search_adherents(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Adherent::factory()->create(['nom' => 'Traore', 'prenom' => 'Salif']);
        Adherent::factory()->create(['nom' => 'Diomande', 'prenom' => 'Awa']);

        Livewire::actingAs($gestionnaire)
            ->test(AdherentsTable::class)
            ->set('search', 'Traore')
            ->assertSee('Salif')
            ->assertDontSee('Awa');
    }

    public function test_gestionnaire_can_create_adherent_without_login_account(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class)
            ->set('nom', 'Kone')
            ->set('prenom', 'Aicha')
            ->set('sexe', 'F')
            ->set('date_naissance', '1990-01-01')
            ->set('date_adhesion', '2020-01-01')
            ->set('ville', 'Abidjan')
            ->set('creerCompteAcces', false)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('adherents', [
            'matricule' => '2020-MIACI-0101A',
            'nom' => 'Kone',
            'ville' => 'Abidjan',
        ]);
    }

    public function test_two_adherents_joining_the_same_day_get_differentiated_matricules(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Adherent::factory()->create(['matricule' => '2020-MIACI-0101A', 'date_adhesion' => '2020-01-01']);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class)
            ->set('nom', 'Kone')
            ->set('prenom', 'Aicha')
            ->set('sexe', 'F')
            ->set('date_naissance', '1990-01-01')
            ->set('date_adhesion', '2020-01-01')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('adherents', [
            'matricule' => '2020-MIACI-0101A2',
            'nom' => 'Kone',
        ]);
    }

    public function test_adherent_can_view_own_fiche(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('mon-espace.profil'))
            ->assertOk()
            ->assertSee($adherent->matricule);
    }

    public function test_admin_can_delete_adherent_without_history(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherentUser = User::factory()->create();
        $adherentUser->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $adherentUser->id]);

        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('supprimer');

        $this->assertDatabaseMissing('adherents', ['id' => $adherent->id]);
        $this->assertDatabaseMissing('users', ['id' => $adherentUser->id]);
    }

    public function test_gestionnaire_cannot_delete_adherent(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('supprimer')
            ->assertForbidden();

        $this->assertDatabaseHas('adherents', ['id' => $adherent->id]);
    }

    public function test_deletion_is_blocked_when_adherent_has_cotisations(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create();
        $adherent->cotisations()->create([
            'montant' => 4500,
            'date_paiement' => now(),
            'periode_debut' => now()->startOfMonth(),
            'periode_fin' => now()->endOfMonth(),
            'mode_paiement' => ModePaiement::Especes,
            'statut' => StatutCotisation::Valide,
        ]);

        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('supprimer');

        $this->assertDatabaseHas('adherents', ['id' => $adherent->id]);
    }

    public function test_gestionnaire_can_set_adherent_password_from_edit_form(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherentUser = User::factory()->create(['password' => Hash::make('ancien-mot-de-passe')]);
        $adherentUser->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $adherentUser->id]);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->set('nouveau_mot_de_passe', 'mot-de-passe-choisi-123')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('mot-de-passe-choisi-123', $adherentUser->fresh()->password));
    }

    public function test_creating_an_adherent_with_account_access_uses_the_default_password(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class)
            ->set('nom', 'Kone')
            ->set('prenom', 'Aicha')
            ->set('sexe', 'F')
            ->set('date_naissance', '1990-01-01')
            ->set('date_adhesion', '2020-01-01')
            ->set('creerCompteAcces', true)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $adherent = Adherent::where('nom', 'Kone')->firstOrFail();

        $this->assertNotNull($adherent->user_id);
        $this->assertTrue(Hash::check(User::MOT_DE_PASSE_PAR_DEFAUT, $adherent->user->password));
    }

    public function test_leaving_password_field_blank_keeps_it_unchanged(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherentUser = User::factory()->create(['password' => Hash::make('mot-de-passe-existant')]);
        $adherentUser->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $adherentUser->id]);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('mot-de-passe-existant', $adherentUser->fresh()->password));
    }

    public function test_gestionnaire_can_add_ayant_droit_for_adherent(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireAyantDroit')
            ->set('ayant_droit_nom', 'Jean Kouadio')
            ->set('ayant_droit_telephone', '0102030405')
            ->call('enregistrerAyantDroit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('adherents', [
            'id' => $adherent->id,
            'ayant_droit_nom' => 'Jean Kouadio',
            'ayant_droit_telephone' => '0102030405',
        ]);
    }
}
