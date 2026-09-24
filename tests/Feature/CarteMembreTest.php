<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Adherent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarteMembreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_adherent_can_download_own_card(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Adherent->value);
        $adherent = Adherent::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('adherents.carte', $adherent));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_adherent_cannot_download_anothers_card(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole(Role::Adherent->value);
        Adherent::factory()->create(['user_id' => $userA->id]);

        $userB = User::factory()->create();
        $userB->assignRole(Role::Adherent->value);
        $adherentB = Adherent::factory()->create(['user_id' => $userB->id]);

        $this->actingAs($userA)
            ->get(route('adherents.carte', $adherentB))
            ->assertForbidden();
    }

    public function test_gestionnaire_can_download_any_card(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create();

        $this->actingAs($gestionnaire)
            ->get(route('adherents.carte', $adherent))
            ->assertOk();
    }

    public function test_card_renders_with_a_photo(): void
    {
        Storage::fake('public');

        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $file = UploadedFile::fake()->image('photo.jpg', 200, 200);
        $path = $file->store('adherents/photos', 'public');

        $adherent = Adherent::factory()->create(['photo_path' => $path]);

        $this->actingAs($gestionnaire)
            ->get(route('adherents.carte', $adherent))
            ->assertOk();
    }

    public function test_card_renders_with_an_ayant_droit(): void
    {
        $gestionnaire = User::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $adherent = Adherent::factory()->create([
            'ayant_droit_nom' => 'Marie Kouassi',
            'ayant_droit_telephone' => '0708091011',
        ]);

        $this->actingAs($gestionnaire)
            ->get(route('adherents.carte', $adherent))
            ->assertOk();
    }

    /**
     * Verso à hauteur fixe (CR80) : le contact « En cas de perte » doit avoir sa
     * propre zone ancrée en bas. Suivant le flux du texte, il passait sous le pied
     * de page dès qu'un ayant droit ou un nom long ajoutait une ligne.
     */
    private function versoHtml(array $attributs): \DOMXPath
    {
        $html = view('pdf.carte-membre', [
            'adherent' => Adherent::factory()->make($attributs),
            'logo' => '',
            'cachet' => null,
            'photo' => null,
            'qr' => null,
        ])->render();

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        return new \DOMXPath($dom);
    }

    public function test_verso_contact_block_is_separate_from_the_flowing_body(): void
    {
        $xpath = $this->versoHtml([
            'ayant_droit_nom' => 'YOBOUET AYA HÉLÈNE ÉPOUSE TOURÉ',
            'ayant_droit_telephone' => '0707523771',
        ]);

        $corps = $xpath->query("//div[contains(@class,'verso-corps')]")->item(0)->textContent;
        $contact = $xpath->query("//div[contains(@class,'verso-contact')]")->item(0)->textContent;

        $this->assertStringContainsString('YOBOUET AYA', $corps);
        $this->assertStringNotContainsString('En cas de perte', $corps, 'le contact ne doit plus dépendre du flux du corps');
        $this->assertStringContainsString('En cas de perte', $contact);
        $this->assertStringContainsString('07 08 15 08 30 / 01 03 38 01 10', $contact);
        $this->assertStringContainsString('mutuellemiaci@hotmail.com', $contact);
    }

    public function test_verso_body_gets_the_dense_class_only_for_long_names(): void
    {
        $court = $this->versoHtml(['prenom' => 'Aya', 'nom' => 'Koffi', 'ayant_droit_nom' => null]);
        $this->assertSame(0, $court->query("//div[contains(@class,'verso-corps') and contains(@class,'dense')]")->length);

        $longTuteur = $this->versoHtml(['prenom' => 'ACHILLE ROMUALD KOUAKOU', 'nom' => "N'GUESSAN DE LA TOUR"]);
        $this->assertSame(1, $longTuteur->query("//div[contains(@class,'verso-corps') and contains(@class,'dense')]")->length);

        $longAyantDroit = $this->versoHtml(['prenom' => 'Aya', 'nom' => 'Koffi', 'ayant_droit_nom' => 'YOBOUET AYA HÉLÈNE ÉPOUSE TOURÉ']);
        $this->assertSame(1, $longAyantDroit->query("//div[contains(@class,'verso-corps') and contains(@class,'dense')]")->length);
    }

    /**
     * Recto à hauteur fixe : la bande bleue du bas masque ce qui dépasse. Le nom,
     * seul élément de hauteur variable, doit réduire sa taille avec sa longueur
     * pour que « Contact » reste visible.
     */
    public function test_recto_name_shrinks_with_its_length(): void
    {
        $classe = function (string $prenom, string $nom): string {
            $xpath = $this->versoHtml(['prenom' => $prenom, 'nom' => $nom]);

            return $xpath->query("//div[contains(@class,'infos')]/div[contains(@class,'nom')]")->item(0)->getAttribute('class');
        };

        $this->assertSame('nom', trim($classe('AYA', 'KOFFI')));                                  // 9 car.
        $this->assertStringContainsString('nom-moyen', $classe('KOUASSI', 'DIABATE'));            // 15 car.
        $this->assertStringContainsString('nom-petit', $classe('AYA CATHERINE', 'KOUAME'));       // 20 car. (fiche 195)
        $this->assertStringContainsString('nom-long', $classe('KLOWELE ROLLAND PACOME', 'TOURE')); // 28 car.
        $this->assertStringContainsString('nom-tres-long', $classe('ACHILLE ROMUALD KOUAKOU', "N'GUESSAN DE LA TOUR"));
    }

    public function test_recto_free_text_fields_stay_on_one_line(): void
    {
        $xpath = $this->versoHtml([
            'fonction' => 'Directeur adjoint chargé de la coordination des examens et concours nationaux',
            'ville' => 'Yamoussoukro',
        ]);

        $valeurs = $xpath->query("//div[contains(@class,'infos')]/div[contains(@class,'champ-valeur')]");
        $fonction = $valeurs->item(1);

        $this->assertLessThanOrEqual(33, mb_strlen(trim($fonction->textContent)), 'la fonction est coupée pour ne pas passer à la ligne');
        $this->assertStringEndsWith('...', trim($fonction->textContent));
        $this->assertStringContainsString('compact', $fonction->getAttribute('class'));
        $this->assertStringNotContainsString('compact', $valeurs->item(2)->getAttribute('class'), 'une ville courte garde la taille normale');
    }
}
