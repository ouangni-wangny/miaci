<?php

namespace Tests\Feature\Gestion\Tables;

use App\Enums\Role;
use App\Enums\StatutAdherent;
use App\Livewire\Gestion\Adherents\AdherentsTable;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class AdherentsTableTest extends TestCase
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

    public function test_renders_rows_and_actions(): void
    {
        $adherent = Adherent::factory()->create(['nom' => 'Traore', 'prenom' => 'Salif']);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->assertSee('Traore')
            ->assertSee($adherent->matricule)
            ->assertSee('Voir');
    }

    public function test_actions_column_has_a_header_and_a_menu_with_icons(): void
    {
        Adherent::factory()->create();

        $html = Livewire::actingAs($this->utilisateur(Role::Admin))
            ->test(AdherentsTable::class)
            ->html();

        $this->assertMatchesRegularExpression('/<th[^>]*>\s*(<[^>]+>\s*)*Actions/s', $html);
        $this->assertStringContainsString('role="menuitem"', $html);
        $this->assertStringContainsString('Voir la fiche', $html);
        $this->assertStringContainsString('Supprimer', $html);
        // une icône (svg) précède le nom de chaque action
        // (Livewire insère des commentaires <!--[if ENDBLOCK]--> après chaque @if)
        $this->assertMatchesRegularExpression('/<\/svg>(?:\s|<!--.*?-->)*Supprimer/s', $html);
    }

    public function test_gestionnaire_does_not_see_the_delete_action(): void
    {
        Adherent::factory()->create();

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->assertSee('Voir la fiche')
            ->assertDontSee('Supprimer');
    }

    public function test_adherent_cannot_view_the_table(): void
    {
        Livewire::actingAs($this->utilisateur(Role::Adherent))
            ->test(AdherentsTable::class)
            ->assertForbidden();
    }

    public function test_search_matches_matricule_telephone_and_ville(): void
    {
        Adherent::factory()->create(['nom' => 'Traore', 'matricule' => '2020-MIACI-0001A', 'telephone' => '0102030405', 'ville' => 'Bouaké']);
        Adherent::factory()->create(['nom' => 'Diomande', 'matricule' => '2021-MIACI-0002A', 'telephone' => '0708091011', 'ville' => 'Abidjan']);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(AdherentsTable::class);

        $table->set('search', '0002A')->assertSee('Diomande')->assertDontSee('Traore');
        $table->set('search', '0102030405')->assertSee('Traore')->assertDontSee('Diomande');
        $table->set('search', 'Bouaké')->assertSee('Traore')->assertDontSee('Diomande');
    }

    public function test_filters_by_statut(): void
    {
        Adherent::factory()->create(['nom' => 'Traore', 'statut' => StatutAdherent::Actif]);
        Adherent::factory()->create(['nom' => 'Diomande', 'statut' => StatutAdherent::Radie]);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->set('filterComponents.statut', StatutAdherent::Radie->value)
            ->assertSee('Diomande')
            ->assertDontSee('Traore');
    }

    public function test_filters_by_adhesion_period(): void
    {
        Adherent::factory()->create(['nom' => 'Ancien', 'date_adhesion' => '2019-03-01']);
        Adherent::factory()->create(['nom' => 'Recent', 'date_adhesion' => '2024-03-01']);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(AdherentsTable::class);

        $table->set('filterComponents.adhesion_depuis', '2023-01-01')->assertSee('Recent')->assertDontSee('Ancien');
        $table->set('filterComponents.adhesion_depuis', '')
            ->set('filterComponents.adhesion_jusqua', '2020-12-31')
            ->assertSee('Ancien')
            ->assertDontSee('Recent');
    }

    public function test_clicking_a_city_chip_toggles_the_ville_filter(): void
    {
        Adherent::factory()->create(['nom' => 'Traore', 'ville' => 'Bouaké']);
        Adherent::factory()->create(['nom' => 'Diomande', 'ville' => 'Abidjan']);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->call('filtrerParVille', 'Bouaké')
            ->assertSee('Traore')
            ->assertDontSee('Diomande')
            ->call('filtrerParVille', 'Bouaké')
            ->assertSee('Traore')
            ->assertSee('Diomande');
    }

    public function test_sorts_by_name_in_both_directions(): void
    {
        Adherent::factory()->create(['nom' => 'Aaa']);
        Adherent::factory()->create(['nom' => 'Zzz']);

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))->test(AdherentsTable::class);

        $table->assertSeeInOrder(['Aaa', 'Zzz']);
        $table->call('sortBy', 'nom')->assertSeeInOrder(['Zzz', 'Aaa']);
    }

    public function test_paginates_with_the_chosen_page_size(): void
    {
        Adherent::factory()->count(12)->create();

        $table = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->set('perPage', 10);

        $this->assertSame(10, $table->instance()->getRows()->count());
        $this->assertSame(12, $table->instance()->getRows()->total());
    }

    public function test_delete_removes_an_adherent_without_history(): void
    {
        $admin = $this->utilisateur(Role::Admin);
        $adherent = Adherent::factory()->create();

        Livewire::actingAs($admin)
            ->test(AdherentsTable::class)
            ->call('supprimer', $adherent->id)
            ->assertSee("Adhérent {$adherent->matricule} supprimé.");

        $this->assertDatabaseMissing('adherents', ['id' => $adherent->id]);
        $this->assertDatabaseHas('journal_audit', ['action' => 'adherent.supprime']);
    }

    public function test_delete_is_refused_when_the_adherent_has_history(): void
    {
        $admin = $this->utilisateur(Role::Admin);
        $adherent = Adherent::factory()->create();
        Cotisation::factory()->create(['adherent_id' => $adherent->id]);

        Livewire::actingAs($admin)
            ->test(AdherentsTable::class)
            ->call('supprimer', $adherent->id)
            ->assertSee('Impossible de supprimer');

        $this->assertDatabaseHas('adherents', ['id' => $adherent->id]);
    }

    public function test_gestionnaire_cannot_delete(): void
    {
        $adherent = Adherent::factory()->create();

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->call('supprimer', $adherent->id)
            ->assertForbidden();

        $this->assertDatabaseHas('adherents', ['id' => $adherent->id]);
    }

    public function test_exports_the_selected_rows_with_the_displayed_columns(): void
    {
        $retenu = Adherent::factory()->create(['nom' => 'Traore']);
        Adherent::factory()->create(['nom' => 'Diomande']);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(AdherentsTable::class)
            ->set('selected', [(string) $retenu->id])
            ->call('exporterSelection')
            ->assertFileDownloaded('adherents-selection-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_export_is_refused_without_the_export_permission(): void
    {
        $adherent = Adherent::factory()->create();
        $user = $this->utilisateur(Role::Gestionnaire);
        $user->givePermissionTo([]);
        $user->roles->first()->revokePermissionTo('exporter_donnees');

        Livewire::actingAs($user->fresh())
            ->test(AdherentsTable::class)
            ->set('selected', [(string) $adherent->id])
            ->call('exporterSelection')
            ->assertForbidden();
    }
}
