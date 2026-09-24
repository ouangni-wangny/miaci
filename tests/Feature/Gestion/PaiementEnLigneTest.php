<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Enums\StatutTransaction;
use App\Enums\TypeTransactionPaiement;
use App\Models\Adherent;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Models\TransactionPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaiementEnLigneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);

        config([
            'services.cinetpay.api_key' => 'test-key',
            'services.cinetpay.site_id' => 'test-site',
            'services.cinetpay.base_url' => 'https://api-checkout.cinetpay.com',
            'services.cinetpay.notify_url' => 'https://miaci.test/paiement/webhook/cinetpay',
            'services.cinetpay.return_url' => 'https://miaci.test/paiement/retour',
        ]);
    }

    private function creerAdherentConnecte(): Adherent
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);

        return Adherent::factory()->create(['user_id' => $user->id]);
    }

    public function test_adherent_can_initiate_online_payment(): void
    {
        Http::fake([
            'api-checkout.cinetpay.com/v2/payment' => Http::response([
                'code' => '201',
                'message' => 'CREATED',
                'data' => ['payment_url' => 'https://checkout.cinetpay.com/payment/abc123'],
            ]),
        ]);

        $adherent = $this->creerAdherentConnecte();

        $response = $this->actingAs($adherent->user)
            ->post(route('paiement.initier'), ['montant' => 1000]);

        $response->assertRedirect('https://checkout.cinetpay.com/payment/abc123');

        $this->assertDatabaseHas('transactions_paiement', [
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'statut' => StatutTransaction::EnAttente->value,
        ]);
    }

    public function test_non_adherent_cannot_initiate_payment(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->post(route('paiement.initier'), ['montant' => 1000])
            ->assertForbidden();
    }

    public function test_webhook_confirms_payment_and_creates_cotisation(): void
    {
        $adherent = $this->creerAdherentConnecte();

        $transaction = TransactionPaiement::create([
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'fournisseur' => 'cinetpay',
            'reference_interne' => 'MIACI-TEST-0001',
            'statut' => StatutTransaction::EnAttente,
            'initie_le' => now(),
        ]);

        Http::fake([
            'api-checkout.cinetpay.com/v2/payment/check' => Http::response([
                'code' => '00',
                'message' => 'SUCCES',
                'data' => ['status' => 'ACCEPTED', 'operator_id' => 'OP123', 'payment_method' => 'ORANGE MONEY CI'],
            ]),
        ]);

        $this->postJson(route('paiement.webhook.cinetpay'), [
            'cpm_trans_id' => 'MIACI-TEST-0001',
        ])->assertOk();

        $transaction->refresh();

        $this->assertEquals(StatutTransaction::Reussi, $transaction->statut);
        $this->assertNotNull($transaction->cotisation_id);
        $this->assertDatabaseHas('cotisations', [
            'id' => $transaction->cotisation_id,
            'adherent_id' => $adherent->id,
            'montant' => 1000,
        ]);
    }

    public function test_webhook_marks_failed_payment_without_creating_cotisation(): void
    {
        $adherent = $this->creerAdherentConnecte();

        $transaction = TransactionPaiement::create([
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'fournisseur' => 'cinetpay',
            'reference_interne' => 'MIACI-TEST-0002',
            'statut' => StatutTransaction::EnAttente,
            'initie_le' => now(),
        ]);

        Http::fake([
            'api-checkout.cinetpay.com/v2/payment/check' => Http::response([
                'code' => '00',
                'message' => 'SUCCES',
                'data' => ['status' => 'REFUSED'],
            ]),
        ]);

        $this->postJson(route('paiement.webhook.cinetpay'), [
            'cpm_trans_id' => 'MIACI-TEST-0002',
        ])->assertOk();

        $transaction->refresh();

        $this->assertEquals(StatutTransaction::Echoue, $transaction->statut);
        $this->assertNull($transaction->cotisation_id);
        $this->assertDatabaseCount('cotisations', 0);
    }

    public function test_webhook_does_not_reverify_an_already_settled_transaction(): void
    {
        $adherent = $this->creerAdherentConnecte();

        $transaction = TransactionPaiement::create([
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'fournisseur' => 'cinetpay',
            'reference_interne' => 'MIACI-TEST-0003',
            'statut' => StatutTransaction::Reussi,
            'initie_le' => now(),
            'complete_le' => now(),
        ]);

        Http::fake([
            'api-checkout.cinetpay.com/v2/payment/check' => Http::response(['code' => '00', 'data' => ['status' => 'ACCEPTED']]),
        ]);

        $this->postJson(route('paiement.webhook.cinetpay'), [
            'cpm_trans_id' => 'MIACI-TEST-0003',
        ])->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseCount('cotisations', 0);
    }

    public function test_adherent_can_initiate_droit_adhesion_payment_for_a_personne_a_charge(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->subYear(), 'actif' => true,
        ]);

        Http::fake([
            'api-checkout.cinetpay.com/v2/payment' => Http::response([
                'code' => '201', 'message' => 'CREATED',
                'data' => ['payment_url' => 'https://checkout.cinetpay.com/payment/xyz789'],
            ]),
        ]);

        $adherent = $this->creerAdherentConnecte();
        $personne = PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        $response = $this->actingAs($adherent->user)
            ->post(route('paiement.initier-droit-adhesion'), [
                'beneficiaire' => 'personne',
                'personne_a_charge_id' => $personne->id,
            ]);

        $response->assertRedirect('https://checkout.cinetpay.com/payment/xyz789');

        $this->assertDatabaseHas('transactions_paiement', [
            'adherent_id' => $adherent->id,
            'type' => TypeTransactionPaiement::DroitAdhesion->value,
            'payable_type' => PersonneACharge::class,
            'payable_id' => $personne->id,
            'montant' => 11000,
        ]);
    }

    public function test_cannot_pay_droit_adhesion_already_settled(): void
    {
        ParametreCotisation::create([
            'montant' => 4500, 'droit_adhesion' => 11000,
            'frequence' => 'mensuelle', 'date_debut' => now()->subYear(), 'actif' => true,
        ]);

        $adherent = $this->creerAdherentConnecte();
        $personne = PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'droit_adhesion_exonere' => true,
        ]);

        $this->actingAs($adherent->user)
            ->post(route('paiement.initier-droit-adhesion'), [
                'beneficiaire' => 'personne',
                'personne_a_charge_id' => $personne->id,
            ])
            ->assertForbidden();
    }

    public function test_adherent_cannot_pay_droit_adhesion_for_someone_elses_personne_a_charge(): void
    {
        $adherent = $this->creerAdherentConnecte();
        $autreAdherent = $this->creerAdherentConnecte();
        $personne = PersonneACharge::factory()->create(['adherent_id' => $autreAdherent->id]);

        $this->actingAs($adherent->user)
            ->post(route('paiement.initier-droit-adhesion'), [
                'beneficiaire' => 'personne',
                'personne_a_charge_id' => $personne->id,
            ])
            ->assertNotFound();
    }

    public function test_webhook_confirms_droit_adhesion_payment_for_personne_a_charge(): void
    {
        $adherent = $this->creerAdherentConnecte();
        $personne = PersonneACharge::factory()->create(['adherent_id' => $adherent->id]);

        $transaction = TransactionPaiement::create([
            'adherent_id' => $adherent->id,
            'type' => TypeTransactionPaiement::DroitAdhesion,
            'payable_type' => PersonneACharge::class,
            'payable_id' => $personne->id,
            'montant' => 11000,
            'fournisseur' => 'cinetpay',
            'reference_interne' => 'MIACI-TEST-DA-0001',
            'statut' => StatutTransaction::EnAttente,
            'initie_le' => now(),
        ]);

        Http::fake([
            'api-checkout.cinetpay.com/v2/payment/check' => Http::response([
                'code' => '00', 'message' => 'SUCCES',
                'data' => ['status' => 'ACCEPTED', 'operator_id' => 'OP456', 'payment_method' => 'ORANGE MONEY CI'],
            ]),
        ]);

        $this->postJson(route('paiement.webhook.cinetpay'), [
            'cpm_trans_id' => 'MIACI-TEST-DA-0001',
        ])->assertOk();

        $transaction->refresh();

        $this->assertEquals(StatutTransaction::Reussi, $transaction->statut);
        $this->assertTrue($personne->fresh()->droitAdhesionPaye());
        $this->assertDatabaseHas('droits_adhesion', [
            'payable_type' => PersonneACharge::class,
            'payable_id' => $personne->id,
            'montant' => 11000,
        ]);
    }
}
