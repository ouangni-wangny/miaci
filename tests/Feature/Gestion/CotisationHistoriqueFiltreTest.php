<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Livewire\Gestion\Adherents\Fiche;
use App\Livewire\MonEspace\Cotisations;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class CotisationHistoriqueFiltreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function creerCotisations(Adherent $adherent): void
    {
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'date_paiement' => '2026-01-15',
            'periode_debut' => '2026-01-01',
            'periode_fin' => '2026-01-31',
        ]);
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'date_paiement' => '2026-03-10',
            'periode_debut' => '2026-03-01',
            'periode_fin' => '2026-03-31',
        ]);
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'date_paiement' => '2025-03-10',
            'periode_debut' => '2025-03-01',
            'periode_fin' => '2025-03-31',
        ]);
    }

    public function test_gestionnaire_can_filter_adherent_history_by_year_and_month(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();
        $this->creerCotisations($adherent);

        $component = Livewire::actingAs($gestionnaire)->test(Fiche::class, ['adherent' => $adherent]);

        $component->assertViewHas('cotisations', fn ($c) => $c->count() === 3);

        $component->set('anneeFiltre', '2026')
            ->assertViewHas('cotisations', fn ($c) => $c->count() === 2);

        $component->set('moisFiltre', '3')
            ->assertViewHas('cotisations', fn ($c) => $c->count() === 1
                && $c->first()->date_paiement->format('Y-m-d') === '2026-03-10');
    }

    public function test_adherent_can_filter_own_history_by_year_and_month(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);
        $this->creerCotisations($adherent);

        Livewire::actingAs($user)->test(Cotisations::class)
            ->assertViewHas('cotisations', fn ($c) => $c->count() === 3)
            ->set('anneeFiltre', '2025')
            ->assertViewHas('cotisations', fn ($c) => $c->count() === 1);
    }
}
