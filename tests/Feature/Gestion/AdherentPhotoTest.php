<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Livewire\Gestion\Adherents\Formulaire;
use App\Models\Adherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdherentPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
        Storage::fake('public');
    }

    public function test_gestionnaire_can_upload_a_photo_when_creating_adherent(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class)
            ->set('nom', 'Kouassi')
            ->set('prenom', 'Marc')
            ->set('sexe', 'M')
            ->set('date_naissance', '1990-01-01')
            ->set('date_adhesion', '2020-01-01')
            ->set('creerCompteAcces', false)
            ->set('photo', UploadedFile::fake()->image('portrait.jpg', 600, 750))
            ->call('enregistrer')
            ->assertHasNoErrors();

        $adherent = Adherent::where('nom', 'Kouassi')->firstOrFail();
        $this->assertNotNull($adherent->photo_path);
        Storage::disk('public')->assertExists($adherent->photo_path);
    }
}
