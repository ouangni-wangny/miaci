<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatutAdherent;
use App\Livewire\Auth\Inscription;
use App\Livewire\MonEspace\Sinistres\Soumettre;
use App\Models\Adherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_guest_can_view_registration_page(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_guest_can_register_as_pending_adherent(): void
    {
        Livewire::test(Inscription::class)
            ->set('nom', 'Traore')
            ->set('prenom', 'Salimata')
            ->set('sexe', 'F')
            ->set('date_naissance', '1992-05-10')
            ->set('email', 'salimata.traore@example.ci')
            ->set('ville', 'Bouaké')
            ->set('password', 'mot-de-passe-sur')
            ->set('password_confirmation', 'mot-de-passe-sur')
            ->call('inscrire')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'salimata.traore@example.ci')->firstOrFail();
        $this->assertTrue($user->hasRole(Role::Adherent->value));

        $adherent = Adherent::where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(StatutAdherent::EnAttente, $adherent->statut);
        $this->assertEquals('Bouaké', $adherent->ville);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'deja.utilise@example.ci']);

        Livewire::test(Inscription::class)
            ->set('nom', 'Kone')
            ->set('prenom', 'Awa')
            ->set('sexe', 'F')
            ->set('date_naissance', '1990-01-01')
            ->set('email', 'deja.utilise@example.ci')
            ->set('password', 'mot-de-passe-sur')
            ->set('password_confirmation', 'mot-de-passe-sur')
            ->call('inscrire')
            ->assertHasErrors(['email']);
    }

    public function test_registration_rejects_duplicate_nom_prenom_date_naissance(): void
    {
        Adherent::factory()->create([
            'nom' => 'Kone',
            'prenom' => 'Awa',
            'date_naissance' => '1990-01-01',
        ]);

        Livewire::test(Inscription::class)
            ->set('nom', 'KONE') // casse différente, doit quand même être détecté
            ->set('prenom', 'awa')
            ->set('sexe', 'F')
            ->set('date_naissance', '1990-01-01')
            ->set('email', 'awa.kone.nouvelle@example.ci')
            ->set('password', 'mot-de-passe-sur')
            ->set('password_confirmation', 'mot-de-passe-sur')
            ->call('inscrire')
            ->assertHasErrors(['nom']);

        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_nom_prenom_when_existing_record_has_no_date_naissance(): void
    {
        // Cas très fréquent pour les dossiers importés depuis l'ancien
        // suivi Excel de la mutuelle, qui ne comportait pas cette colonne :
        // la comparaison ne peut pas s'appuyer sur la date de naissance
        // pour ces adhérents, donc on compare sur nom + prénom seuls.
        Adherent::factory()->create([
            'nom' => 'Yao',
            'prenom' => 'Kouassi',
            'date_naissance' => null,
        ]);

        Livewire::test(Inscription::class)
            ->set('nom', 'Yao')
            ->set('prenom', 'Kouassi')
            ->set('sexe', 'M')
            ->set('date_naissance', '1990-01-01')
            ->set('email', 'kouassi.yao.nouveau@example.ci')
            ->set('password', 'mot-de-passe-sur')
            ->set('password_confirmation', 'mot-de-passe-sur')
            ->call('inscrire')
            ->assertHasErrors(['nom']);

        $this->assertGuest();
    }

    public function test_registration_allows_same_name_with_different_date_naissance(): void
    {
        Adherent::factory()->create([
            'nom' => 'Kone',
            'prenom' => 'Awa',
            'date_naissance' => '1990-01-01',
        ]);

        Livewire::test(Inscription::class)
            ->set('nom', 'Kone')
            ->set('prenom', 'Awa')
            ->set('sexe', 'F')
            ->set('date_naissance', '1985-03-15') // homonyme, personne différente
            ->set('email', 'autre.awa.kone@example.ci')
            ->set('password', 'mot-de-passe-sur')
            ->set('password_confirmation', 'mot-de-passe-sur')
            ->call('inscrire')
            ->assertHasNoErrors();
    }

    public function test_pending_adherent_cannot_submit_a_claim(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->enAttente()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('mon-espace.sinistres.soumettre'))
            ->assertForbidden();
    }

    public function test_pending_adherent_cannot_initiate_online_payment(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->enAttente()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('paiement.initier'), ['montant' => 1000])
            ->assertForbidden();
    }

    public function test_active_adherent_can_access_claim_submission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $user->id]); // actif par défaut

        Livewire::actingAs($user)->test(Soumettre::class)->assertOk();
    }
}
