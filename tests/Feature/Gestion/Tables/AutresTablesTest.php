<?php

namespace Tests\Feature\Gestion\Tables;

use App\Enums\FrequenceCotisation;
use App\Enums\Role;
use App\Enums\StatutDemandeSinistre;
use App\Enums\StatutTransaction;
use App\Livewire\Gestion\Audit\JournalTable;
use App\Livewire\Gestion\Cotisations\SoldesTable;
use App\Livewire\Gestion\DonsFinAnnee\DonsTable;
use App\Livewire\Gestion\Paiements\TransactionsTable;
use App\Livewire\Gestion\Parametres\TypesSinistre\TypesTable;
use App\Livewire\Gestion\Sinistres\DemandesTable;
use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\DonFinAnnee;
use App\Models\JournalAudit;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Models\TransactionPaiement;
use App\Models\TypeSinistre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Les tables de l'administration hors adhérents (voir AdherentsTableTest) :
 * chacune est testée sur son contrôle d'accès, son affichage et ce qui la
 * distingue (filtres propres, actions).
 */
class AutresTablesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function utilisateur(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function transaction(Adherent $adherent, array $attributs = []): TransactionPaiement
    {
        return TransactionPaiement::create($attributs + [
            'adherent_id' => $adherent->id,
            'montant' => 1000,
            'fournisseur' => 'cinetpay',
            'reference_interne' => 'MIACI-'.fake()->unique()->numerify('####'),
            'statut' => StatutTransaction::EnAttente,
            'initie_le' => now(),
        ]);
    }

    // ------------------------------------------------------------ Paiements

    public function test_paiements_table_lists_and_filters_transactions(): void
    {
        $traore = Adherent::factory()->create(['nom' => 'Traore']);
        $diomande = Adherent::factory()->create(['nom' => 'Diomande']);
        $this->transaction($traore, ['reference_interne' => 'REF-TRAORE', 'statut' => StatutTransaction::Reussi]);
        $this->transaction($diomande, ['reference_interne' => 'REF-DIOMANDE', 'statut' => StatutTransaction::Echoue]);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(TransactionsTable::class);

        $table->assertSee('REF-TRAORE')->assertSee('REF-DIOMANDE');
        $table->set('filterComponents.statut', StatutTransaction::Reussi->value)
            ->assertSee('REF-TRAORE')->assertDontSee('REF-DIOMANDE');
    }

    public function test_paiements_table_searches_by_adherent_and_reference(): void
    {
        $this->transaction(Adherent::factory()->create(['nom' => 'Traore']), ['reference_interne' => 'REF-AAA']);
        $this->transaction(Adherent::factory()->create(['nom' => 'Diomande']), ['reference_interne' => 'REF-BBB']);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(TransactionsTable::class);

        $table->set('search', 'Diomande')->assertSee('REF-BBB')->assertDontSee('REF-AAA');
        $table->set('search', 'REF-AAA')->assertSee('REF-AAA')->assertDontSee('REF-BBB');
    }

    public function test_paiements_table_sorts_by_adherent_name(): void
    {
        $this->transaction(Adherent::factory()->create(['nom' => 'Zzz']), ['reference_interne' => 'REF-Z']);
        $this->transaction(Adherent::factory()->create(['nom' => 'Aaa']), ['reference_interne' => 'REF-A']);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(TransactionsTable::class)
            ->call('sortBy', 'adherent-tuteur')
            ->assertSeeInOrder(['REF-A', 'REF-Z'])
            ->call('sortBy', 'adherent-tuteur')
            ->assertSeeInOrder(['REF-Z', 'REF-A']);
    }

    public function test_paiements_table_is_forbidden_to_adherents(): void
    {
        Livewire::actingAs($this->utilisateur(Role::Adherent))
            ->test(TransactionsTable::class)
            ->assertForbidden();
    }

    // ------------------------------------------------------------ Sinistres

    public function test_sinistres_table_filters_by_statut_and_type(): void
    {
        $deces = TypeSinistre::factory()->create(['libelle' => 'Décès test']);
        $dot = TypeSinistre::factory()->create(['libelle' => 'Dot test']);
        DemandeSinistre::factory()->create([
            'adherent_id' => Adherent::factory()->create(['nom' => 'Traore']),
            'type_sinistre_id' => $deces->id,
            'statut' => StatutDemandeSinistre::Approuvee,
        ]);
        DemandeSinistre::factory()->create([
            'adherent_id' => Adherent::factory()->create(['nom' => 'Diomande']),
            'type_sinistre_id' => $dot->id,
            'statut' => StatutDemandeSinistre::Rejetee,
        ]);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(DemandesTable::class);

        $table->assertSee('Traore')->assertSee('Diomande')->assertSee('Examiner');
        $table->set('filterComponents.statut', StatutDemandeSinistre::Rejetee->value)
            ->assertSee('Diomande')->assertDontSee('Traore');
        $table->set('filterComponents.statut', '')
            ->set('filterComponents.type_sinistre', (string) $deces->id)
            ->assertSee('Traore')->assertDontSee('Diomande');
    }

    public function test_sinistres_table_searches_by_type_label(): void
    {
        $deces = TypeSinistre::factory()->create(['libelle' => 'Décès test']);
        $dot = TypeSinistre::factory()->create(['libelle' => 'Dot test']);
        DemandeSinistre::factory()->create(['adherent_id' => Adherent::factory()->create(['nom' => 'Traore']), 'type_sinistre_id' => $deces->id]);
        DemandeSinistre::factory()->create(['adherent_id' => Adherent::factory()->create(['nom' => 'Diomande']), 'type_sinistre_id' => $dot->id]);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(DemandesTable::class)
            ->set('search', 'Dot test')
            ->assertSee('Diomande')
            ->assertDontSee('Traore');
    }

    // ---------------------------------------------------------------- Audit

    public function test_audit_table_is_reserved_to_admins(): void
    {
        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(JournalTable::class)
            ->assertForbidden();

        Livewire::actingAs($this->utilisateur(Role::Admin))
            ->test(JournalTable::class)
            ->assertOk();
    }

    public function test_audit_table_shows_entries_and_filters_by_user_and_action(): void
    {
        $admin = $this->utilisateur(Role::Admin);
        $autre = User::factory()->create(['name' => 'Kouame Autre']);
        $adherent = Adherent::factory()->create();

        JournalAudit::create(['user_id' => $admin->id, 'action' => 'adherent.supprime', 'entite_type' => Adherent::class, 'entite_id' => $adherent->id, 'donnees_avant' => ['nom' => 'AVANT-X']]);
        JournalAudit::create(['user_id' => $autre->id, 'action' => 'sinistre.approuve', 'entite_type' => DemandeSinistre::class, 'entite_id' => 7]);
        JournalAudit::create(['user_id' => null, 'action' => 'systeme.tache', 'entite_type' => Adherent::class, 'entite_id' => 9]);

        $table = Livewire::actingAs($admin)->test(JournalTable::class);

        // Le panneau de filtres liste toutes les actions dans ses <option> :
        // on contrôle donc les lignes retournées, pas le texte de la page.
        $actions = fn () => $table->instance()->getRows()->pluck('action')->sort()->values()->all();

        $this->assertSame(['adherent.supprime', 'sinistre.approuve', 'systeme.tache'], $actions());
        $table->assertSee('Avant : {&quot;nom&quot;:&quot;AVANT-X&quot;}', false);

        $table->set('filterComponents.utilisateur', (string) $autre->id);
        $this->assertSame(['sinistre.approuve'], $actions());

        $table->set('filterComponents.utilisateur', 'systeme');
        $this->assertSame(['systeme.tache'], $actions());

        $table->set('filterComponents.utilisateur', '')->set('filterComponents.action_filtre', 'adherent.supprime');
        $this->assertSame(['adherent.supprime'], $actions());
    }

    public function test_audit_table_escapes_recorded_data(): void
    {
        $admin = $this->utilisateur(Role::Admin);
        $adherent = Adherent::factory()->create();
        JournalAudit::create(['user_id' => $admin->id, 'action' => 'test.xss', 'entite_type' => Adherent::class, 'entite_id' => $adherent->id, 'donnees_apres' => ['nom' => '<script>alert(1)</script>']]);

        Livewire::actingAs($admin)
            ->test(JournalTable::class)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ---------------------------------------------------------- Cotisations

    public function test_soldes_table_lists_active_adherents_and_filters_by_situation(): void
    {
        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(2),
        ]);
        $enRetard = Adherent::factory()->create(['nom' => 'Retardataire', 'date_adhesion' => now()->subYears(2)]);
        Adherent::factory()->create(['nom' => 'Inactif', 'statut' => 'radie']);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(SoldesTable::class);

        $table->assertSee('Retardataire')->assertDontSee('Inactif')->assertSee('Voir la fiche');
        $table->set('filterComponents.situation', 'retard')->assertSee('Retardataire');
        $table->set('filterComponents.situation', 'a_jour')->assertDontSee('Retardataire');

        $this->assertNotNull($enRetard);
    }

    // ------------------------------------------------------ Types de sinistre

    public function test_types_table_toggles_a_type_and_is_not_exportable(): void
    {
        $type = TypeSinistre::factory()->create(['libelle' => 'Type test', 'actif' => true]);

        $table = Livewire::actingAs($this->utilisateur(Role::Admin))
            ->test(TypesTable::class)
            ->assertSee('Type test')
            ->assertSee('Modifier');

        $this->assertSame([], $table->instance()->bulkActions());

        $table->call('basculerActif', $type->id);
        $this->assertFalse($type->fresh()->actif);
    }

    public function test_types_table_filters_active_and_inactive(): void
    {
        TypeSinistre::factory()->create(['libelle' => 'Type actif', 'actif' => true]);
        TypeSinistre::factory()->create(['libelle' => 'Type inactif', 'actif' => false]);

        Livewire::actingAs($this->utilisateur(Role::Admin))
            ->test(TypesTable::class)
            ->set('filterComponents.actif', '0')
            ->assertSee('Type inactif')
            ->assertDontSee('Type actif');
    }

    public function test_types_table_is_forbidden_to_gestionnaires(): void
    {
        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(TypesTable::class)
            ->assertForbidden();
    }

    // ------------------------------------------------------ Dons de fin d'année

    public function test_dons_table_marks_an_eligible_adherent_and_filters_by_versement(): void
    {
        $eligible = Adherent::factory()->create(['nom' => 'Eligible', 'date_adhesion' => now()->subYears(2)]);
        PersonneACharge::factory()->count(2)->validee()->create(['adherent_id' => $eligible->id, 'date_adhesion' => now()->subYears(2)]);

        $gestionnaire = $this->utilisateur(Role::Gestionnaire);

        $table = Livewire::actingAs($gestionnaire)
            ->test(DonsTable::class, ['annee' => (string) now()->year])
            ->assertSee('Eligible')
            ->assertSee('À verser')
            ->call('marquerVerse', $eligible->id)
            ->assertSee("Don de fin d'année enregistré pour")
            ->assertSee('Versé');

        $this->assertDatabaseHas('dons_fin_annee', ['adherent_id' => $eligible->id, 'annee' => now()->year]);

        $table->set('filterComponents.versement', 'a_verser')->assertDontSee('Eligible');
        $table->set('filterComponents.versement', 'verse')->assertSee('Eligible');
        $this->assertSame(1, DonFinAnnee::count());
    }
}
