<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Enums\StatutCotisation;
use App\Livewire\Gestion\Bilan\Index;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\ParametreCotisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class BilanAnnuelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_adherent_cannot_view_bilan(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);

        $this->actingAs($user)->get(route('gestion.bilan.index'))->assertForbidden();
    }

    public function test_gestionnaire_can_view_bilan(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)->get(route('gestion.bilan.index'))->assertOk();
    }

    public function test_bilan_computes_expected_versus_realised_cotisations(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->startOfYear(), 'actif' => true,
        ]);

        // Un seul adhérent, adhérent depuis le début de l'année : sur les
        // (mois écoulés jusqu'à aujourd'hui) mois, il doit "mois écoulés x 4500".
        $adherent = Adherent::factory()->create([
            'statut' => 'actif',
            'date_adhesion' => now()->startOfYear(),
        ]);

        Cotisation::create([
            'adherent_id' => $adherent->id, 'montant' => 4500, 'date_paiement' => now(),
            'periode_debut' => now()->startOfMonth(), 'periode_fin' => now()->endOfMonth(),
            'mode_paiement' => 'especes', 'statut' => StatutCotisation::Valide,
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $moisEcoules = (int) now()->startOfYear()->diffInMonths(now()->startOfMonth()) + 1;

        Livewire::actingAs($gestionnaire)
            ->test(Index::class, ['annee' => now()->year])
            ->assertViewHas('bilan', function (array $bilan) use ($moisEcoules) {
                return $bilan['cotisationsAttendues'] === $moisEcoules * 4500
                    && $bilan['cotisationsRealisees'] === 4500;
            });
    }

    public function test_bilan_excludes_future_year_data(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->startOfYear(), 'actif' => true,
        ]);

        Adherent::factory()->create(['statut' => 'actif', 'date_adhesion' => now()->startOfYear()]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Livewire::actingAs($gestionnaire)
            ->test(Index::class, ['annee' => now()->year + 1])
            ->assertViewHas('bilan', fn (array $bilan) => $bilan['cotisationsAttendues'] === 0);
    }
}
