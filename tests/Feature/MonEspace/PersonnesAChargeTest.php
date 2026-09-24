<?php

namespace Tests\Feature\MonEspace;

use App\Enums\Role;
use App\Livewire\MonEspace\PersonnesACharge as PersonnesAChargeComponent;
use App\Models\Adherent;
use App\Models\PersonneACharge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class PersonnesAChargeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_personnes_a_charge_page_is_read_only_for_the_adherent(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        Livewire::actingAs($user)
            ->test(PersonnesAChargeComponent::class)
            ->assertDontSee('Ajouter')
            ->assertDontSee('Modifier')
            ->assertDontSee('Retirer')
            ->assertSee('contactez un gestionnaire');
    }

    public function test_delai_de_carence_is_shown_for_each_personne_a_charge(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'date_naissance' => '2000-01-01',
            'date_adhesion' => '2026-01-01',
        ]);

        Livewire::actingAs($user)
            ->test(PersonnesAChargeComponent::class)
            ->assertSee('8 mois')
            ->assertSee('01/09/2026');
    }

    public function test_adherent_cannot_create_update_or_delete_a_personne_a_charge(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        $personne = PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        $this->assertFalse($user->can('create', [PersonneACharge::class, $adherent]));
        $this->assertFalse($user->can('update', $personne));
        $this->assertFalse($user->can('delete', $personne));
    }

    public function test_gestionnaire_can_still_create_update_and_delete_a_personne_a_charge(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();
        $personne = PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        $this->assertTrue($gestionnaire->can('create', [PersonneACharge::class, $adherent]));
        $this->assertTrue($gestionnaire->can('update', $personne));
        $this->assertTrue($gestionnaire->can('delete', $personne));
    }
}
