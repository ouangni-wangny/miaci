<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Livewire\Gestion\Parametres\Site;
use App\Models\BanniereSite;
use App\Models\ParametreSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ParametresSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_gestionnaire_cannot_access_site_settings(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->get(route('gestion.parametres.site'))
            ->assertForbidden();
    }

    public function test_admin_can_edit_site_text_content(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)
            ->test(Site::class)
            ->set('hero_titre_ligne1', 'Nouveau titre,')
            ->set('hero_titre_ligne2', 'nouvelle vision')
            ->set('contact_email', 'contact@miaci.ci')
            ->call('enregistrer')
            ->assertHasNoErrors();

        $site = ParametreSite::actuel();
        $this->assertSame('Nouveau titre,', $site->hero_titre_ligne1);
        $this->assertSame('nouvelle vision', $site->hero_titre_ligne2);
        $this->assertSame('contact@miaci.ci', $site->contact_email);
    }

    public function test_updated_content_appears_on_public_homepage(): void
    {
        ParametreSite::actuel()->update(['hero_titre_ligne1' => 'Titre personnalisé unique']);

        $this->get('/')->assertSee('Titre personnalisé unique');
    }

    public function test_admin_can_add_and_remove_a_banniere(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)
            ->test(Site::class)
            ->set('nouvelleBanniere', UploadedFile::fake()->image('banniere.jpg', 1200, 400))
            ->call('ajouterBanniere')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('bannieres_site', 1);
        $banniere = BanniereSite::first();
        Storage::disk('public')->assertExists($banniere->image_path);

        Livewire::actingAs($admin)
            ->test(Site::class)
            ->call('supprimerBanniere', $banniere->id);

        $this->assertDatabaseCount('bannieres_site', 0);
        Storage::disk('public')->assertMissing($banniere->image_path);
    }

    public function test_only_active_bannieres_appear_on_homepage(): void
    {
        Storage::fake('public');

        BanniereSite::create(['image_path' => 'bannieres/active.jpg', 'ordre' => 1, 'actif' => true]);
        BanniereSite::create(['image_path' => 'bannieres/inactive.jpg', 'ordre' => 2, 'actif' => false]);

        $response = $this->get('/');

        $response->assertSee('bannieres/active.jpg', false);
        $response->assertDontSee('bannieres/inactive.jpg', false);
    }
}
