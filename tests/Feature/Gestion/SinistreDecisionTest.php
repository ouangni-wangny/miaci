<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Enums\StatutCotisation;
use App\Enums\StatutDemandeSinistre;
use App\Livewire\Gestion\Sinistres\Fiche;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\DemandeSinistre;
use App\Models\ParametreCotisation;
use App\Models\TypeSinistre;
use App\Models\User;
use App\Notifications\StatutDemandeSinistreModifie;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class SinistreDecisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function creerDemande(array $attrs = []): DemandeSinistre
    {
        $userAdherent = User::factory()->create();
        $userAdherent->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $userAdherent->id]);
        $type = TypeSinistre::factory()->create(['plafond_montant' => 100000]);

        return DemandeSinistre::factory()->create([
            'adherent_id' => $adherent->id,
            'type_sinistre_id' => $type->id,
            'montant_demande' => 80000,
            'statut' => StatutDemandeSinistre::Soumise,
            ...$attrs,
        ]);
    }

    public function test_gestionnaire_can_approve_within_plafond_and_notifies_adherent(): void
    {
        Notification::fake();

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = $this->creerDemande();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->set('montant_accorde', 80000)
            ->call('approuver')
            ->assertHasNoErrors();

        $demande->refresh();
        $this->assertEquals(StatutDemandeSinistre::Approuvee, $demande->statut);
        $this->assertEquals(80000, $demande->montant_accorde);
        $this->assertEquals($gestionnaire->id, $demande->traite_par);

        Notification::assertSentTo($demande->adherent->user, StatutDemandeSinistreModifie::class);
    }

    public function test_montant_accorde_cannot_exceed_plafond(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = $this->creerDemande(); // plafond 100000, montant_demande 80000

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->set('montant_accorde', 90000) // > montant_demande
            ->call('approuver')
            ->assertHasErrors('montant_accorde');

        $this->assertEquals(StatutDemandeSinistre::Soumise, $demande->fresh()->statut);
    }

    public function test_gestionnaire_can_reject_with_motif(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = $this->creerDemande();

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->set('motif_decision', 'Pièces insuffisantes.')
            ->call('rejeter')
            ->assertHasNoErrors();

        $demande->refresh();
        $this->assertEquals(StatutDemandeSinistre::Rejetee, $demande->statut);
        $this->assertNull($demande->montant_accorde);
    }

    public function test_adherent_cannot_decide_on_a_demande(): void
    {
        $demande = $this->creerDemande();

        Livewire::actingAs($demande->adherent->user)
            ->test(Fiche::class, ['demande' => $demande])
            ->call('approuver')
            ->assertForbidden();
    }

    public function test_deces_montant_accorde_is_prefilled_net_of_current_month_cotisation(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->subYear(), 'actif' => true,
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = $this->creerDemande([
            'montant_demande' => 1000000,
        ]);
        $demande->typeSinistre()->update(['code' => 'DECES', 'plafond_montant' => 1000000]);
        $demande->adherent->update(['date_adhesion' => now()->subMonths(6)->startOfMonth()]);
        // Rien de payé pour le mois en cours : la cotisation du mois en
        // cours (4500) doit être déduite du montant proposé.

        $component = Livewire::actingAs($gestionnaire)->test(Fiche::class, ['demande' => $demande]);

        $this->assertSame('995500', $component->get('montant_accorde'));
    }

    public function test_non_deces_demande_has_no_deduction(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->subYear(), 'actif' => true,
        ]);

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = $this->creerDemande(['montant_demande' => 50000]);
        $demande->typeSinistre()->update(['code' => 'DOT']);

        $component = Livewire::actingAs($gestionnaire)->test(Fiche::class, ['demande' => $demande]);

        $this->assertSame('50000', $component->get('montant_accorde'));
    }

    public function test_deadlines_are_shown_for_deces_demande(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = $this->creerDemande(['date_evenement' => now()->subDays(5)]);
        $demande->typeSinistre()->update(['code' => 'DECES']);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->assertViewHas('dateLimiteDocuments', fn ($date) => $date->isSameDay($demande->date_evenement->copy()->addDays(21)))
            ->assertViewHas('dateLimiteTraitement', fn ($date) => $date->isSameDay($demande->created_at->copy()->addDays(14)));
    }

    public function test_deadline_skips_december_when_submitted_during_the_office_closure(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->travelTo(now()->setDate(2026, 12, 5)->startOfDay());
        $demande = $this->creerDemande();
        $demande->typeSinistre()->update(['code' => 'DECES']);

        // Soumise le 5 décembre : les 14 jours ne commencent à courir qu'à
        // partir de la réouverture du bureau, le 1er janvier.
        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->assertViewHas('dateLimiteTraitement', fn ($date) => $date->isSameDay(Carbon::create(2027, 1, 14)));
    }

    public function test_deadline_partially_absorbs_december_when_submitted_late_november(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->travelTo(now()->setDate(2026, 11, 25)->startOfDay());
        $demande = $this->creerDemande();
        $demande->typeSinistre()->update(['code' => 'DECES']);

        // 5 jours utiles fin novembre (26 au 30), puis décembre entièrement
        // ignoré, puis 9 jours supplémentaires en janvier pour totaliser 14.
        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->assertViewHas('dateLimiteTraitement', fn ($date) => $date->isSameDay(Carbon::create(2027, 1, 9)));
    }
}
