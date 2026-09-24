<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Livewire\Gestion\Sinistres\Fiche;
use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\TypeSinistre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AuditEtExportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_approving_a_claim_writes_an_audit_entry(): void
    {
        Notification::fake();

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $userAdherent = User::factory()->create();
        $userAdherent->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $userAdherent->id]);
        $type = TypeSinistre::factory()->create(['plafond_montant' => 100000]);
        $demande = DemandeSinistre::factory()->create([
            'adherent_id' => $adherent->id,
            'type_sinistre_id' => $type->id,
            'montant_demande' => 50000,
        ]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['demande' => $demande])
            ->set('montant_accorde', 50000)
            ->call('approuver');

        $this->assertDatabaseHas('journal_audit', [
            'user_id' => $gestionnaire->id,
            'action' => 'sinistre.approuve',
            'entite_type' => DemandeSinistre::class,
            'entite_id' => $demande->id,
        ]);
    }

    public function test_only_admin_can_view_audit_log(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->get(route('gestion.audit.index'))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->get(route('gestion.audit.index'))
            ->assertOk();
    }

    public function test_gestionnaire_can_export_adherents(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);
        Adherent::factory()->count(2)->create();

        $this->actingAs($gestionnaire)
            ->get(route('gestion.exports.adherents'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_adherent_cannot_access_exports(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);

        $this->actingAs($user)
            ->get(route('gestion.exports.adherents'))
            ->assertForbidden();
    }

    public function test_gestionnaire_can_export_liste_des_membres(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();
        \App\Models\PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        $this->actingAs($gestionnaire)
            ->get(route('gestion.exports.membres'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_gestionnaire_can_export_dons_fin_annee(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->get(route('gestion.exports.dons-fin-annee'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_adherent_cannot_access_membres_or_dons_exports(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);

        $this->actingAs($user)
            ->get(route('gestion.exports.membres'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion.exports.dons-fin-annee'))
            ->assertForbidden();
    }
}
