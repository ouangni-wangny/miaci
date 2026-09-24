<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/connexion');
    }

    public function test_admin_sees_gestion_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Adhérents tuteurs actifs');
    }

    public function test_adherent_sees_personal_dashboard(): void
    {
        $adherent = User::factory()->create();
        $adherent->assignRole(Role::Adherent->value);

        $this->actingAs($adherent)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mon profil adhérent');
    }
}
