<?php

namespace Tests\Feature\Auth;

use App\Models\Adherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/connexion');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_users_can_authenticate_using_their_matricule(): void
    {
        $user = User::factory()->create(['password' => Hash::make('mot-de-passe-sur')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => 'MIACI-00001']);

        $component = Volt::test('pages.auth.login')
            ->set('form.login', 'MIACI-00001')
            ->set('form.password', 'mot-de-passe-sur');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    private function tenterConnexion(string $login, string $motDePasse)
    {
        return Volt::test('pages.auth.login')
            ->set('form.login', $login)
            ->set('form.password', $motDePasse)
            ->call('login');
    }

    /**
     * Cas réel : compte dont l'email est vide. La connexion par matricule
     * passait par l'email du compte et échouait avec « identifiants incorrects »
     * alors que matricule et mot de passe étaient bons.
     */
    public function test_account_with_an_empty_email_can_log_in_with_its_matricule(): void
    {
        $user = User::factory()->create(['email' => '', 'password' => Hash::make('Bonjour@2022')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => '2021-MIACI-0208A']);

        $this->tenterConnexion('2021-MIACI-0208A', 'Bonjour@2022')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_account_with_an_empty_email_still_needs_the_right_password(): void
    {
        $user = User::factory()->create(['email' => '', 'password' => Hash::make('Bonjour@2022')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => '2021-MIACI-0208A']);

        $this->tenterConnexion('2021-MIACI-0208A', 'mauvais-mot-de-passe')
            ->assertHasErrors(['form.login']);

        $this->assertGuest();
    }

    public function test_identifier_whitespace_from_copy_paste_is_ignored(): void
    {
        $user = User::factory()->create(['email' => 'aya@example.ci', 'password' => Hash::make('mot-de-passe-sur')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => 'MIACI-00001']);

        foreach (['  aya@example.ci', "aya@example.ci \t", "\u{00A0}aya@example.ci\u{00A0}", ' MIACI-00001 ', "\u{200B}MIACI-00001"] as $saisie) {
            $this->tenterConnexion($saisie, 'mot-de-passe-sur')->assertHasNoErrors();
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_password_whitespace_is_never_trimmed(): void
    {
        $user = User::factory()->create(['password' => Hash::make('mot-de-passe-sur')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => 'MIACI-00001']);

        $this->tenterConnexion('MIACI-00001', 'mot-de-passe-sur ')->assertHasErrors(['form.login']);
        $this->tenterConnexion('MIACI-00001', ' mot-de-passe-sur')->assertHasErrors(['form.login']);

        $this->assertGuest();
    }

    public function test_unknown_matricule_and_adherent_without_account_are_refused(): void
    {
        Adherent::factory()->create(['user_id' => null, 'matricule' => 'MIACI-00002']);

        $this->tenterConnexion('MIACI-99999', 'nimporte')->assertHasErrors(['form.login']);
        $this->tenterConnexion('MIACI-00002', 'nimporte')->assertHasErrors(['form.login']);
        $this->tenterConnexion('   ', 'nimporte')->assertHasErrors();

        $this->assertGuest();
    }

    public function test_account_email_contact_email_and_matricule_all_identify_the_same_account(): void
    {
        $user = User::factory()->create(['email' => 'compte@example.ci', 'password' => Hash::make('mot-de-passe-sur')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => 'MIACI-00007', 'email' => 'contact@example.ci']);

        foreach (['compte@example.ci', 'contact@example.ci', 'MIACI-00007'] as $identifiant) {
            $this->tenterConnexion($identifiant, 'mot-de-passe-sur')->assertHasNoErrors();
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_contact_email_works_when_the_account_has_no_email(): void
    {
        $user = User::factory()->create(['email' => '', 'password' => Hash::make('mot-de-passe-sur')]);
        Adherent::factory()->create(['user_id' => $user->id, 'matricule' => 'MIACI-00008', 'email' => 'contact@example.ci']);

        $this->tenterConnexion('contact@example.ci', 'mot-de-passe-sur')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_shared_contact_email_is_resolved_by_the_password_not_arbitrarily(): void
    {
        $premier = User::factory()->create(['email' => 'un@example.ci', 'password' => Hash::make('mdp-du-premier')]);
        $second = User::factory()->create(['email' => 'deux@example.ci', 'password' => Hash::make('mdp-du-second')]);
        Adherent::factory()->create(['user_id' => $premier->id, 'email' => 'partage@example.ci']);
        Adherent::factory()->create(['user_id' => $second->id, 'email' => 'partage@example.ci']);

        $this->tenterConnexion('partage@example.ci', 'mdp-du-second')->assertHasNoErrors();
        $this->assertAuthenticatedAs($second);
        auth()->logout();

        $this->tenterConnexion('partage@example.ci', 'mdp-du-premier')->assertHasNoErrors();
        $this->assertAuthenticatedAs($premier);
        auth()->logout();

        $this->tenterConnexion('partage@example.ci', 'autre-mot-de-passe')->assertHasErrors(['form.login']);
        $this->assertGuest();
    }

    public function test_staff_account_without_adherent_still_logs_in_by_email(): void
    {
        $user = User::factory()->create(['email' => 'gestion@example.ci', 'password' => Hash::make('mot-de-passe-sur')]);

        $this->tenterConnexion('gestion@example.ci', 'mot-de-passe-sur')->assertHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_fields_offer_a_show_hide_toggle(): void
    {
        foreach (['login', 'register'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('Afficher le mot de passe')
                ->assertSee("x-bind:type=\"visible ? 'text' : 'password'\"", false);
        }

        $this->get(route('register'))->assertSee('Confirmer le mot de passe');
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
