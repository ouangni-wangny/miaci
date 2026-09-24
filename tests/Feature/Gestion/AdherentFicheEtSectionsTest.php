<?php

namespace Tests\Feature\Gestion;

use App\Enums\Role;
use App\Enums\StatutAdherent;
use App\Livewire\Gestion\Adherents\Fiche;
use App\Livewire\Gestion\Adherents\Formulaire;
use App\Models\Adherent;
use App\Models\JournalAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fiche adhérent restructurée (onglets vue d'ensemble / cotisations /
 * personnes à charge / sécurité) et modification découpée en sections
 * indépendantes (profil, adhésion et statut) ; la sécurité — réinitialisation
 * du mot de passe — est un onglet de la fiche.
 */
class AdherentFicheEtSectionsTest extends TestCase
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

    private function adherentAvecCompte(string $motDePasse = 'ancien-mot-de-passe'): Adherent
    {
        $compte = User::factory()->create(['password' => Hash::make($motDePasse)]);
        $compte->assignRole(Role::Adherent->value);

        return Adherent::factory()->create(['user_id' => $compte->id]);
    }

    // ------------------------------------------------------------------ Fiche

    public function test_fiche_shows_identity_tabs_and_edit_shortcuts(): void
    {
        $adherent = Adherent::factory()->create(['nom' => 'Traore', 'prenom' => 'Salif', 'ville' => 'Bouaké']);

        $this->actingAs($this->utilisateur(Role::Gestionnaire))
            ->get(route('gestion.adherents.fiche', $adherent))
            ->assertOk()
            ->assertSee('Salif Traore')
            ->assertSee($adherent->matricule)
            ->assertSee("Vue d'ensemble")
            ->assertSee('Cotisations')
            ->assertSee('Personnes à charge')
            ->assertSee('Sécurité')
            ->assertSee('section=profil', false)
            ->assertSee('section=adhesion', false)
            ->assertSee('onglet=securite', false);
    }

    public function test_fiche_is_forbidden_to_adherents(): void
    {
        $adherent = $this->adherentAvecCompte();

        $this->actingAs($adherent->user)
            ->get(route('gestion.adherents.fiche', $adherent))
            ->assertForbidden();
    }

    public function test_danger_zone_is_only_shown_to_users_allowed_to_delete(): void
    {
        $adherent = Adherent::factory()->create();

        $this->actingAs($this->utilisateur(Role::Admin))
            ->get(route('gestion.adherents.fiche', $adherent))
            ->assertSee('Zone sensible');

        $this->actingAs($this->utilisateur(Role::Gestionnaire))
            ->get(route('gestion.adherents.fiche', $adherent))
            ->assertDontSee('Zone sensible');
    }

    public function test_fiche_never_displays_the_password_hash(): void
    {
        $adherent = $this->adherentAvecCompte('mot-de-passe-secret-42');

        $html = $this->actingAs($this->utilisateur(Role::Admin))
            ->get(route('gestion.adherents.fiche', $adherent))
            ->getContent();

        $this->assertStringNotContainsString('mot-de-passe-secret-42', $html);
        $this->assertStringNotContainsString($adherent->user->password, $html);
    }

    // ----------------------------------------------------- Sections : accès

    public function test_each_edit_section_page_renders(): void
    {
        $adherent = $this->adherentAvecCompte();
        $gestionnaire = $this->utilisateur(Role::Gestionnaire);

        $this->actingAs($gestionnaire)
            ->get(route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'profil']))
            ->assertOk()->assertSee('Enregistrer le profil');

        $this->actingAs($gestionnaire)
            ->get(route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'adhesion']))
            ->assertOk()->assertSee("Enregistrer l'adhésion", false);
    }

    public function test_old_security_section_link_redirects_to_the_fiche_tab(): void
    {
        $adherent = $this->adherentAvecCompte();

        $this->actingAs($this->utilisateur(Role::Gestionnaire))
            ->get(route('gestion.adherents.modifier', ['adherent' => $adherent, 'section' => 'securite']))
            ->assertRedirect(route('gestion.adherents.fiche', ['adherent' => $adherent, 'onglet' => 'securite']));
    }

    public function test_security_tab_of_the_fiche_offers_the_password_reset(): void
    {
        $adherent = $this->adherentAvecCompte();

        $this->actingAs($this->utilisateur(Role::Gestionnaire))
            ->get(route('gestion.adherents.fiche', $adherent))
            ->assertOk()
            ->assertSee('Réinitialiser le mot de passe')
            ->assertSee('Nouveau mot de passe')
            ->assertSee('Dernières opérations');
    }

    public function test_unknown_section_falls_back_to_profile(): void
    {
        $adherent = Adherent::factory()->create();

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->withQueryParams(['section' => 'nimporte-quoi'])
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->assertSet('section', 'profil');
    }

    public function test_security_tab_of_an_adherent_without_account_offers_no_password_form(): void
    {
        $adherent = Adherent::factory()->create(['user_id' => null]);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Fiche::class, ['adherent' => $adherent])
            ->assertSee("n'a pas de compte d'accès", false)
            ->assertDontSee('Nouveau mot de passe');
    }

    // ------------------------------------------------- Sections : indépendance

    public function test_saving_the_profile_does_not_touch_status_or_password(): void
    {
        $adherent = $this->adherentAvecCompte('mot-de-passe-existant');
        $adherent->update(['statut' => StatutAdherent::Actif, 'telephone' => '0100000000']);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->set('telephone', '0709080706')
            // Même si ces champs d'autres sections sont modifiés sans être enregistrés…
            ->set('statut', StatutAdherent::Radie->value)
            ->set('nouveau_mot_de_passe', 'un-autre-mot-de-passe')
            ->call('enregistrerProfil')
            ->assertHasNoErrors();

        $adherent->refresh();
        $this->assertSame('0709080706', $adherent->telephone);
        $this->assertSame(StatutAdherent::Actif, $adherent->statut);
        $this->assertTrue(Hash::check('mot-de-passe-existant', $adherent->user->password));
        $this->assertDatabaseMissing('journal_audit', ['action' => 'adherent.mot_de_passe_reinitialise']);
    }

    public function test_saving_the_membership_section_changes_status_and_is_audited(): void
    {
        $gestionnaire = $this->utilisateur(Role::Gestionnaire);
        $adherent = Adherent::factory()->create(['statut' => StatutAdherent::Actif, 'nom' => 'Traore']);

        Livewire::actingAs($gestionnaire)
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->set('nom', 'Modifie-sans-enregistrer')
            ->set('statut', StatutAdherent::Suspendu->value)
            ->call('enregistrerAdhesion')
            ->assertHasNoErrors();

        $adherent->refresh();
        $this->assertSame(StatutAdherent::Suspendu, $adherent->statut);
        $this->assertSame('Traore', $adherent->nom, "la section adhésion n'enregistre pas le profil");

        $this->assertDatabaseHas('journal_audit', [
            'action' => 'adherent.statut_modifie',
            'entite_id' => $adherent->id,
            'user_id' => $gestionnaire->id,
        ]);
    }

    public function test_membership_section_without_status_change_writes_no_status_audit(): void
    {
        $adherent = Adherent::factory()->create(['statut' => StatutAdherent::Actif]);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->set('ville', 'Abidjan')
            ->set('date_fin_carence_indicative', '2030-01-01')
            ->call('enregistrerAdhesion')
            ->assertHasNoErrors();

        $this->assertSame('2030-01-01', $adherent->fresh()->date_fin_carence_indicative->format('Y-m-d'));
        $this->assertDatabaseMissing('journal_audit', ['action' => 'adherent.statut_modifie']);
    }

    // ------------------------------------------------------------ Sécurité (onglet de la fiche)

    public function test_security_tab_resets_the_password_and_audits_without_leaking_it(): void
    {
        $gestionnaire = $this->utilisateur(Role::Gestionnaire);
        $adherent = $this->adherentAvecCompte('ancien-mot-de-passe');

        $composant = Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->set('nouveau_mot_de_passe', 'Nouveau-mdp-2026')
            ->call('enregistrerSecurite')
            ->assertHasNoErrors()
            ->assertSet('nouveau_mot_de_passe', '')
            ->assertSee('Mot de passe réinitialisé.');

        $adherent->refresh();
        $this->assertTrue(Hash::check('Nouveau-mdp-2026', $adherent->user->password));

        $entree = JournalAudit::where('action', 'adherent.mot_de_passe_reinitialise')->firstOrFail();
        $this->assertSame($gestionnaire->id, $entree->user_id);
        $this->assertStringNotContainsString('Nouveau-mdp-2026', json_encode($entree->toArray()));

        // Le mot de passe saisi n'est plus renvoyé au navigateur après l'enregistrement,
        // et l'opération apparaît dans « Dernières opérations ».
        $this->assertStringNotContainsString('Nouveau-mdp-2026', $composant->html());
        $composant->assertSee('Mot de passe réinitialisé')->assertSee($gestionnaire->name);
    }

    public function test_security_tab_requires_a_password_of_at_least_six_characters(): void
    {
        $adherent = $this->adherentAvecCompte('ancien-mot-de-passe');

        $composant = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Fiche::class, ['adherent' => $adherent]);

        $composant->set('nouveau_mot_de_passe', '')->call('enregistrerSecurite')
            ->assertHasErrors(['nouveau_mot_de_passe' => 'required']);

        $composant->set('nouveau_mot_de_passe', '12345')->call('enregistrerSecurite')
            ->assertHasErrors(['nouveau_mot_de_passe' => 'min']);

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $adherent->user->fresh()->password));
    }

    public function test_security_tab_is_refused_when_the_adherent_has_no_account(): void
    {
        $adherent = Adherent::factory()->create(['user_id' => null]);

        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Fiche::class, ['adherent' => $adherent])
            ->set('nouveau_mot_de_passe', 'Nouveau-mdp-2026')
            ->call('enregistrerSecurite')
            ->assertStatus(422);
    }

    public function test_generated_password_is_alphanumeric_and_long_enough(): void
    {
        $adherent = $this->adherentAvecCompte();

        $composant = Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('genererMotDePasse');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{10}$/', $composant->get('nouveau_mot_de_passe'));
        // Générer n'enregistre rien.
        $this->assertTrue(Hash::check('ancien-mot-de-passe', $adherent->user->fresh()->password));
    }

    public function test_status_change_shows_up_in_the_security_journal(): void
    {
        $gestionnaire = $this->utilisateur(Role::Gestionnaire);
        $adherent = Adherent::factory()->create(['statut' => StatutAdherent::Actif]);

        Livewire::actingAs($gestionnaire)
            ->test(Fiche::class, ['adherent' => $adherent])
            ->call('changerStatut', StatutAdherent::Suspendu->value)
            ->assertSee('Statut modifié');
    }

    public function test_only_this_adherents_operations_are_listed(): void
    {
        $gestionnaire = $this->utilisateur(Role::Gestionnaire);
        $adherent = Adherent::factory()->create(['statut' => StatutAdherent::Actif]);
        $autre = $this->adherentAvecCompte();

        JournalAudit::create(['user_id' => $gestionnaire->id, 'action' => 'adherent.mot_de_passe_reinitialise', 'entite_type' => Adherent::class, 'entite_id' => $autre->id]);

        $composant = Livewire::actingAs($gestionnaire)->test(Fiche::class, ['adherent' => $adherent]);

        $this->assertCount(0, $composant->viewData('journalSecurite'));
        $composant->assertSee('Aucune opération enregistrée');
    }

    public function test_sections_are_not_available_when_creating_an_adherent(): void
    {
        Livewire::actingAs($this->utilisateur(Role::Gestionnaire))
            ->test(Formulaire::class)
            ->call('enregistrerSecurite')
            ->assertStatus(404);
    }

    public function test_adherent_role_cannot_use_the_sections(): void
    {
        $adherent = $this->adherentAvecCompte();

        Livewire::actingAs($adherent->user)
            ->test(Formulaire::class, ['adherent' => $adherent])
            ->assertForbidden();
    }
}
