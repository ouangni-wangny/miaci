<?php

namespace Tests\Feature\Gestion;

use App\Enums\FrequenceCotisation;
use App\Enums\Role;
use App\Livewire\Gestion\DonsFinAnnee\DonsTable;
use App\Models\Adherent;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Models\User;
use App\Services\CotisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class DonFinAnneeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_adherent_with_less_than_2_personnes_a_charge_is_not_eligible(): void
    {
        // Carnet = l'adhérent + ses personnes à charge : il en faut au
        // moins 3 au total, donc au moins 2 personnes à charge en plus de
        // l'adhérent lui-même — 1 seule ne suffit pas.
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);

        $this->assertFalse($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
    }

    public function test_adherent_with_exactly_2_personnes_a_charge_out_of_carence_is_eligible(): void
    {
        // Ancienneté large pour ne pas être bloqué par le délai de carence
        // (l'adhérent comme les personnes à charge) — déjà couvert
        // précisément par CarenceReglementaireTest.
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);
        // Une non validée ne doit pas compter.
        PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        $this->assertTrue($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
        $this->assertSame(2, $adherent->tailleCarnet());
    }

    public function test_only_one_personne_a_charge_out_of_carence_is_not_eligible(): void
    {
        // Au moins 2 des personnes à charge doivent avoir elles-mêmes fini
        // leur propre carence — ici une seule sur les deux l'a fait.
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);
        PersonneACharge::factory()->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now(), // encore en carence
        ]);

        $this->assertFalse($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
    }

    public function test_only_2_of_a_much_larger_carnet_need_to_be_out_of_carence(): void
    {
        // Un adhérent peut avoir un carnet de plusieurs dizaines de
        // personnes à charge (cas réel observé : 41) — seules 2 d'entre
        // elles, au minimum, doivent avoir fini leur propre carence, pas
        // la totalité du carnet.
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);
        PersonneACharge::factory()->count(10)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now(), // toutes les autres, encore en carence
        ]);

        $this->assertSame(12, $adherent->tailleCarnet());
        $this->assertTrue($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
    }

    public function test_adherent_still_in_carence_is_not_eligible_even_with_enough_personnes_a_charge(): void
    {
        // Adhésion trop récente : l'adhérent lui-même n'a pas fini son
        // délai de carence (8 mois pour un né après 1956).
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(3),
        ]);
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);

        $this->assertFalse($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
    }

    public function test_manually_set_date_fin_carence_makes_an_otherwise_in_carence_adherent_eligible(): void
    {
        // Même situation que test_adherent_still_in_carence_is_not_eligible_
        // even_with_enough_personnes_a_charge (3 mois d'ancienneté, 8 requis),
        // mais un gestionnaire a corrigé manuellement la date de fin de
        // carence à une date déjà passée : elle doit primer.
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(3),
            'date_fin_carence_indicative' => now()->subDay(),
        ]);
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);

        $this->assertTrue($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
    }

    public function test_adherent_with_arrears_is_not_eligible_even_with_enough_personnes_a_charge(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(2),
        ]);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);
        // Aucune cotisation payée : arriéré, donc non éligible malgré le carnet suffisant.

        $this->assertFalse($adherent->eligibleDonFinAnnee(app(CotisationService::class)));
    }

    public function test_gestionnaire_can_mark_don_as_verse(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $adherent->id,
            'date_adhesion' => now()->subYears(2),
        ]);

        Livewire::actingAs($gestionnaire)
            ->test(DonsTable::class, ['annee' => (string) now()->year])
            ->assertSee($adherent->nomComplet())
            ->call('marquerVerse', $adherent->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dons_fin_annee', [
            'adherent_id' => $adherent->id,
            'montant' => 10000,
            'enregistre_par' => $gestionnaire->id,
        ]);
    }

    public function test_ineligible_adherent_cannot_be_marked(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();

        Livewire::actingAs($gestionnaire)
            ->test(DonsTable::class, ['annee' => (string) now()->year])
            ->call('marquerVerse', $adherent->id)
            ->assertForbidden();
    }
}
