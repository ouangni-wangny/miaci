<?php

namespace Tests\Feature;

use App\Enums\StatutCotisation;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\ParametreCotisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SoldeCarteTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_url_shows_solde_without_authentication(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->subYear(), 'actif' => true,
        ]);

        $adherent = Adherent::factory()->create(['date_adhesion' => now()->subYear()]);
        Cotisation::create([
            'adherent_id' => $adherent->id, 'montant' => 4500, 'date_paiement' => now(),
            'periode_debut' => now()->startOfMonth(), 'periode_fin' => now()->endOfMonth(),
            'mode_paiement' => 'especes', 'statut' => StatutCotisation::Valide,
        ]);

        $url = URL::signedRoute('carte.solde', ['adherent' => $adherent]);

        $this->get($url)
            ->assertOk()
            ->assertSee($adherent->matricule)
            ->assertSee('4 500', false);
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $adherent = Adherent::factory()->create();

        $this->get(route('carte.solde', ['adherent' => $adherent]))
            ->assertForbidden();
    }

    public function test_tampering_with_adherent_id_invalidates_signature(): void
    {
        $adherentA = Adherent::factory()->create();
        $adherentB = Adherent::factory()->create();

        $urlPourA = URL::signedRoute('carte.solde', ['adherent' => $adherentA]);
        $urlTrafiquee = str_replace((string) $adherentA->id, (string) $adherentB->id, $urlPourA);

        $this->get($urlTrafiquee)->assertForbidden();
    }
}
