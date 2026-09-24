<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Livewire\Gestion\Parametres\TypesSinistre\Formulaire;
use App\Models\TypeSinistre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class TypeSinistreManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_admin_can_create_type_sinistre_with_pieces_requises(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        Livewire::actingAs($admin)
            ->test(Formulaire::class)
            ->set('code', 'DECES')
            ->set('libelle', 'Décès')
            ->set('plafond_montant', 300000)
            ->set('delai_carence_mois', 6)
            ->set('pieces.0.libelle', 'Acte de décès')
            ->set('pieces.0.obligatoire', true)
            ->call('ajouterPiece')
            ->set('pieces.1.libelle', 'Pièce d\'identité')
            ->set('pieces.1.obligatoire', false)
            ->call('enregistrer')
            ->assertHasNoErrors();

        $type = TypeSinistre::where('code', 'DECES')->firstOrFail();
        $this->assertCount(2, $type->piecesRequises);
        $this->assertTrue($type->piecesRequises->firstWhere('libelle', 'Acte de décès')->obligatoire);
    }

    public function test_gestionnaire_cannot_create_type_sinistre(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->get(route('gestion.parametres.types-sinistre.creer'))
            ->assertForbidden();
    }
}
