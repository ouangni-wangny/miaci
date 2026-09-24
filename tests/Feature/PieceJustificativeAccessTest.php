<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\PieceJustificative;
use App\Models\TypeSinistre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PieceJustificativeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
        Storage::fake('local');
    }

    private function creerPiece(): array
    {
        $userProprietaire = User::factory()->create();
        $userProprietaire->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $userProprietaire->id]);
        $type = TypeSinistre::factory()->create();

        $demande = DemandeSinistre::factory()->create([
            'adherent_id' => $adherent->id,
            'type_sinistre_id' => $type->id,
        ]);

        Storage::disk('local')->put('sinistres/'.$demande->id.'/piece.pdf', 'contenu-test');

        $piece = PieceJustificative::create([
            'justificable_type' => DemandeSinistre::class,
            'justificable_id' => $demande->id,
            'fichier_path' => 'sinistres/'.$demande->id.'/piece.pdf',
            'nom_original' => 'piece.pdf',
            'type_mime' => 'application/pdf',
            'taille' => 13,
            'uploaded_by' => $userProprietaire->id,
        ]);

        return [$piece, $userProprietaire];
    }

    public function test_owner_can_download_own_piece(): void
    {
        [$piece, $proprietaire] = $this->creerPiece();

        $this->actingAs($proprietaire)
            ->get(route('pieces-justificatives.telecharger', $piece))
            ->assertOk();
    }

    public function test_gestionnaire_can_download_any_piece(): void
    {
        [$piece] = $this->creerPiece();

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->get(route('pieces-justificatives.telecharger', $piece))
            ->assertOk();
    }

    public function test_other_adherent_cannot_download_piece(): void
    {
        [$piece] = $this->creerPiece();

        $autreUser = User::factory()->create();
        $autreUser->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $autreUser->id]);

        $this->actingAs($autreUser)
            ->get(route('pieces-justificatives.telecharger', $piece))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_piece(): void
    {
        [$piece] = $this->creerPiece();

        $this->get(route('pieces-justificatives.telecharger', $piece))
            ->assertRedirect('/connexion');
    }
}
