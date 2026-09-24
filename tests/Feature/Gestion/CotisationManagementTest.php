<?php

namespace Tests\Feature\Gestion;

use App\Enums\FrequenceCotisation;
use App\Enums\Role;
use App\Enums\StatutRelevePeriode;
use App\Livewire\Gestion\Adherents\Fiche;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\ParametreCotisation;
use App\Models\User;
use App\Services\CotisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class CotisationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_solde_is_zero_without_parametre(): void
    {
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subMonths(3)]);

        $solde = app(CotisationService::class)->calculerSolde($adherent);

        $this->assertSame(0, $solde['du']);
        $this->assertNull($solde['parametre']);
    }

    public function test_solde_accounts_for_elapsed_periods_and_payments(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 1000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subMonths(2)->startOfMonth()]);
        Cotisation::factory()->create(['adherent_id' => $adherent->id, 'montant' => 1000]);

        $solde = app(CotisationService::class)->calculerSolde($adherent);

        // 3 périodes échues (mois courant + 2 précédents) à 1000, 1000 déjà payé.
        $this->assertSame(3000, $solde['du']);
        $this->assertSame(1000, $solde['paye']);
        $this->assertSame(2000, $solde['reste']);
    }

    public function test_releves_periodes_reflects_paid_partial_and_unpaid_months(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 1000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subMonths(2)->startOfMonth()]);

        // Mois -2 : payé intégralement.
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'periode_debut' => now()->subMonths(2)->startOfMonth(),
            'periode_fin' => now()->subMonths(2)->endOfMonth(),
        ]);
        // Mois -1 : payé partiellement.
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 400,
            'periode_debut' => now()->subMonth()->startOfMonth(),
            'periode_fin' => now()->subMonth()->endOfMonth(),
        ]);
        // Mois courant : rien payé.

        $releves = app(CotisationService::class)->relevesPeriodes($adherent);

        $this->assertCount(3, $releves);
        // Le plus récent (mois courant) est en tête.
        $this->assertSame(StatutRelevePeriode::Impaye, $releves[0]['statut']);
        $this->assertSame(0, $releves[0]['paye']);
        $this->assertSame(StatutRelevePeriode::Partiel, $releves[1]['statut']);
        $this->assertSame(400, $releves[1]['paye']);
        $this->assertSame(StatutRelevePeriode::Paye, $releves[2]['statut']);
        $this->assertSame(1000, $releves[2]['paye']);
    }

    public function test_gestionnaire_can_record_a_manual_payment(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 1000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->startOfMonth()]);
        $cle = now()->startOfMonth()->format('Y-m-d');

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireCotisation')
            ->set("periodesAPayer.{$cle}.selectionne", true)
            ->set('date_paiement', now()->format('Y-m-d'))
            ->set('mode_paiement', 'especes')
            ->call('enregistrerCotisation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cotisations', [
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'enregistre_par' => $gestionnaire->id,
        ]);
    }

    public function test_manual_payment_targets_exactly_the_selected_month(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        // Adhésion il y a 6 mois, seuls les 3 premiers mois sont payés
        // (via le service, pour poser l'état initial) : le reste est en arriéré.
        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subMonths(6)->startOfMonth()]);
        app(CotisationService::class)->allouerPaiement($adherent, 3 * 4500, now(), 'especes', null, $gestionnaire->id);

        $pluspAncienImpaye = collect(app(CotisationService::class)->relevesPeriodes($adherent))
            ->last(fn ($p) => $p['statut'] !== \App\Enums\StatutRelevePeriode::Paye);
        $cle = $pluspAncienImpaye['debut']->format('Y-m-d');

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireCotisation')
            ->set("periodesAPayer.{$cle}.selectionne", true)
            ->set('date_paiement', now()->format('Y-m-d'))
            ->set('mode_paiement', 'especes')
            ->call('enregistrerCotisation')
            ->assertHasNoErrors();

        $nouvellePeriode = $adherent->cotisations()->latest('id')->first();
        $this->assertTrue($nouvellePeriode->periode_debut->isSameDay($pluspAncienImpaye['debut']));
        $this->assertSame(4500, $nouvellePeriode->montant);
    }

    public function test_manual_payment_records_a_chosen_partial_amount_for_the_selected_month(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->startOfMonth()]);
        $cle = now()->startOfMonth()->format('Y-m-d');

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireCotisation')
            ->set("periodesAPayer.{$cle}.selectionne", true)
            ->set("periodesAPayer.{$cle}.type", 'partiel')
            ->set("periodesAPayer.{$cle}.montant_partiel", 2000)
            ->set('date_paiement', now()->format('Y-m-d'))
            ->set('mode_paiement', 'especes')
            ->call('enregistrerCotisation')
            ->assertHasNoErrors();

        $releves = app(CotisationService::class)->relevesPeriodes($adherent);
        $this->assertCount(1, $releves);
        $this->assertSame(\App\Enums\StatutRelevePeriode::Partiel, $releves[0]['statut']);
        $this->assertSame(2000, $releves[0]['paye']);
    }

    public function test_partial_amount_greater_than_or_equal_to_remaining_due_is_rejected(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->startOfMonth()]);
        $cle = now()->startOfMonth()->format('Y-m-d');

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireCotisation')
            ->set("periodesAPayer.{$cle}.selectionne", true)
            ->set("periodesAPayer.{$cle}.type", 'partiel')
            ->set("periodesAPayer.{$cle}.montant_partiel", 4500) // = montant dû, devrait être "Complet"
            ->set('date_paiement', now()->format('Y-m-d'))
            ->set('mode_paiement', 'especes')
            ->call('enregistrerCotisation')
            ->assertHasErrors("periodesAPayer.{$cle}.montant_partiel");

        $this->assertDatabaseCount('cotisations', 0);
    }

    public function test_no_month_selected_shows_an_error(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->startOfMonth()]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('ouvrirFormulaireCotisation')
            ->set('date_paiement', now()->format('Y-m-d'))
            ->set('mode_paiement', 'especes')
            ->call('enregistrerCotisation')
            ->assertHasErrors('periodesAPayer');
    }

    public function test_admin_can_cancel_a_payment_and_it_stops_counting_toward_the_balance(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->startOfMonth()]);
        $cotisation = Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 4500,
            'periode_debut' => now()->startOfMonth(),
            'periode_fin' => now()->endOfMonth(),
        ]);

        $this->assertSame(0, app(CotisationService::class)->calculerSolde($adherent)['reste']);

        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerCotisation', $cotisation->id);

        $this->assertSame('annule', $cotisation->fresh()->statut->value);
        // Le paiement annulé ne compte plus : le solde redevient dû.
        $this->assertSame(4500, app(CotisationService::class)->calculerSolde($adherent)['reste']);
    }

    public function test_gestionnaire_cannot_cancel_a_payment(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();
        $cotisation = Cotisation::factory()->create(['adherent_id' => $adherent->id]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerCotisation', $cotisation->id)
            ->assertForbidden();

        $this->assertSame('valide', $cotisation->fresh()->statut->value);
    }

    public function test_adherent_can_view_own_receipt_but_not_anothers(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole(Role::Adherent->value);
        $adherentA = Adherent::factory()->create(['user_id' => $userA->id]);
        $cotisationA = Cotisation::factory()->create(['adherent_id' => $adherentA->id]);

        $userB = User::factory()->create();
        $userB->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $userB->id]);

        $this->actingAs($userA)
            ->get(route('cotisations.recu', $cotisationA))
            ->assertOk();

        $this->actingAs($userB)
            ->get(route('cotisations.recu', $cotisationA))
            ->assertForbidden();
    }

    public function test_cancelling_a_late_payment_reports_the_carence_change(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYear(),
        ]);

        // L'annulation d'une cotisation est réservée à ADMIN (voir CotisationPolicy::annuler).
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonth()->startOfMonth(),
        ]);

        // Le mois précédent (seule période échue) a été payé en retard, bien
        // après le délai du 5 (Article 7) : la pénalité de carence est acquise.
        $periodeDebut = now()->subMonth()->startOfMonth();
        $cotisation = Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 4500,
            'periode_debut' => $periodeDebut,
            'periode_fin' => $periodeDebut->copy()->endOfMonth(),
            'date_paiement' => $periodeDebut->copy()->endOfMonth()->addDays(45),
        ]);

        $service = app(CotisationService::class);
        $this->assertSame(1, $service->penaliteRetardMois($adherent));

        // Annuler ce paiement en retard fait redevenir la période impayée :
        // elle n'a plus jamais été réglée, donc la pénalité qui lui était
        // liée disparaît (elle redevient un simple arriéré, article 8).
        Livewire::actingAs($admin)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('annulerCotisation', $cotisation->id)
            ->assertSee('Le délai de carence');

        $this->assertSame(0, $service->penaliteRetardMois($adherent->fresh()));
    }
}
