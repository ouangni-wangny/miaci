<?php

namespace Tests\Feature\MonEspace;

use App\Enums\Role;
use App\Livewire\MonEspace\Profil;
use App\Models\Adherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_adherent_can_change_own_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('ancien-mot-de-passe')]);
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('mot_de_passe_actuel', 'ancien-mot-de-passe')
            ->set('nouveau_mot_de_passe', 'nouveau-mot-de-passe-123')
            ->set('nouveau_mot_de_passe_confirmation', 'nouveau-mot-de-passe-123')
            ->call('changerMotDePasse')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nouveau-mot-de-passe-123', $user->fresh()->password));
    }

    /**
     * Cas réel : vider le champ email de « Mon profil » recopiait une chaîne
     * vide dans le compte de connexion, qui ne pouvait alors plus se connecter.
     */
    public function test_clearing_the_contact_email_does_not_blank_the_account_email(): void
    {
        $user = User::factory()->create(['email' => 'aya@example.ci']);
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id, 'email' => 'aya@example.ci']);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('email', '')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertSame('aya@example.ci', $user->fresh()->email, "l'email du compte ne doit jamais être vidé");
        $this->assertNull($adherent->fresh()->email, "l'email de contact vidé est enregistré comme absent (null), pas comme une chaîne vide");
    }

    public function test_changing_the_contact_email_updates_the_account_email(): void
    {
        $user = User::factory()->create(['email' => 'ancien@example.ci']);
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $user->id, 'email' => 'ancien@example.ci']);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('email', 'nouveau@example.ci')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertSame('nouveau@example.ci', $user->fresh()->email);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('ancien-mot-de-passe')]);
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('mot_de_passe_actuel', 'mauvais-mot-de-passe')
            ->set('nouveau_mot_de_passe', 'nouveau-mot-de-passe-123')
            ->set('nouveau_mot_de_passe_confirmation', 'nouveau-mot-de-passe-123')
            ->call('changerMotDePasse')
            ->assertHasErrors(['mot_de_passe_actuel']);

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $user->fresh()->password));
    }

    public function test_password_change_requires_matching_confirmation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('ancien-mot-de-passe')]);
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('mot_de_passe_actuel', 'ancien-mot-de-passe')
            ->set('nouveau_mot_de_passe', 'nouveau-mot-de-passe-123')
            ->set('nouveau_mot_de_passe_confirmation', 'ne-correspond-pas')
            ->call('changerMotDePasse')
            ->assertHasErrors(['nouveau_mot_de_passe']);
    }

    public function test_updating_contact_email_syncs_login_email(): void
    {
        $user = User::factory()->create(['email' => 'miaci-00042@miaci.local']);
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id, 'email' => null]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('email', 'vrai.email@example.ci')
            ->set('telephone', '0708091011')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $this->assertSame('vrai.email@example.ci', $adherent->fresh()->email);
        $this->assertSame('vrai.email@example.ci', $user->fresh()->email);
    }

    public function test_adherent_can_update_full_profil_and_upload_a_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->set('nom', 'Nouveau Nom')
            ->set('prenom', 'Nouveau Prénom')
            ->set('sexe', 'F')
            ->set('date_naissance', '1990-05-12')
            ->set('etablissement', 'EPP Test')
            ->set('ville', 'Bouaké')
            ->set('fonction', 'Instituteur')
            ->set('photo', UploadedFile::fake()->image('photo.jpg', 600, 750))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $adherent->refresh();

        $this->assertSame('Nouveau Nom', $adherent->nom);
        $this->assertSame('Nouveau Prénom', $adherent->prenom);
        $this->assertSame('F', $adherent->sexe->value);
        $this->assertSame('EPP Test', $adherent->etablissement);
        $this->assertSame('Bouaké', $adherent->ville);
        $this->assertNotNull($adherent->photo_path);
        Storage::disk('public')->assertExists($adherent->photo_path);
    }

    public function test_adherent_cannot_change_matricule_or_statut(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id, 'matricule' => 'MIACI-12345']);

        $component = Livewire::actingAs($user)->test(Profil::class);

        $this->assertFalse(property_exists($component->instance(), 'matricule'));
        $this->assertFalse(property_exists($component->instance(), 'statut'));

        $this->assertSame('MIACI-12345', $adherent->fresh()->matricule);
    }

    public function test_adherent_can_add_modify_and_remove_own_ayant_droit(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->call('ouvrirFormulaireAyantDroit')
            ->set('ayant_droit_nom', 'Marie Kouassi')
            ->set('ayant_droit_telephone', '0708091011')
            ->call('enregistrerAyantDroit')
            ->assertHasNoErrors();

        $adherent->refresh();
        $this->assertSame('Marie Kouassi', $adherent->ayant_droit_nom);
        $this->assertSame('0708091011', $adherent->ayant_droit_telephone);
        $this->assertTrue($adherent->aUnAyantDroit());

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->call('supprimerAyantDroit');

        $adherent->refresh();
        $this->assertNull($adherent->ayant_droit_nom);
        $this->assertFalse($adherent->aUnAyantDroit());
    }

    public function test_ayant_droit_requires_nom_and_telephone(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Profil::class)
            ->call('ouvrirFormulaireAyantDroit')
            ->call('enregistrerAyantDroit')
            ->assertHasErrors(['ayant_droit_nom', 'ayant_droit_telephone']);
    }
}
