<?php

namespace Tests\Feature\Gestion;

use App\Enums\FrequenceCotisation;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Services\CotisationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarenceReglementaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParametreCotisation::factory()->create([
            'montant' => 4500,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(3),
        ]);
    }

    public function test_carence_de_base_est_de_8_mois_pour_un_adherent_ne_apres_1956(): void
    {
        $adherent = Adherent::factory()->create(['date_naissance' => '1980-05-10']);

        $this->assertSame(8, app(CotisationService::class)->delaiCarenceBaseAge($adherent));
    }

    public function test_carence_de_base_est_de_12_mois_pour_un_adherent_ne_avant_ou_en_1956(): void
    {
        $adherent = Adherent::factory()->create(['date_naissance' => '1950-05-10']);
        $this->assertSame(12, app(CotisationService::class)->delaiCarenceBaseAge($adherent));

        $adherentNe1956 = Adherent::factory()->create(['date_naissance' => '1956-01-01']);
        $this->assertSame(12, app(CotisationService::class)->delaiCarenceBaseAge($adherentNe1956));
    }

    public function test_aucune_penalite_de_retard_si_adherent_a_jour(): void
    {
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(2)->startOfMonth(),
        ]);

        for ($i = 2; $i >= 0; $i--) {
            $debut = now()->subMonths($i)->startOfMonth();
            Cotisation::factory()->create([
                'adherent_id' => $adherent->id,
                'montant' => 4500,
                'date_paiement' => $debut,
                'periode_debut' => $debut,
                'periode_fin' => $debut->copy()->endOfMonth(),
            ]);
        }

        $service = app(CotisationService::class);

        $this->assertSame(0, $service->penaliteRetardMois($adherent));
        $this->assertSame(8, $service->delaiCarenceMinimum($adherent));
    }

    public function test_arrieres_non_payes_nentrainent_aucune_penalite_de_carence(): void
    {
        // Tant qu'une période n'a jamais été réglée, elle relève de
        // l'arriéré (article 8, risque de radiation) mais pas encore de la
        // pénalité de carence (article 7), qui ne se déclenche qu'au moment
        // où le règlement intervient effectivement, en retard.
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(4)->startOfMonth(),
        ]);
        // Aucun paiement du tout : toutes les périodes précédentes sont en arriéré.

        $service = app(CotisationService::class);

        $this->assertGreaterThan(1, $service->moisArrieres($adherent));
        $this->assertSame(0, $service->penaliteRetardMois($adherent));
        $this->assertSame(8, $service->delaiCarenceMinimum($adherent));
    }

    public function test_penalite_de_3_mois_si_plusieurs_periodes_payees_en_retard(): void
    {
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(3)->startOfMonth(),
        ]);

        // Les 3 périodes échues sont réglées, mais chacune bien après son
        // propre délai du 5 du mois suivant : 2 périodes ou plus en retard
        // plafonne la pénalité à 3 mois.
        foreach ([3, 2, 1] as $moisAvant) {
            $debut = now()->subMonths($moisAvant)->startOfMonth();
            Cotisation::factory()->create([
                'adherent_id' => $adherent->id,
                'montant' => 4500,
                'date_paiement' => $debut->copy()->endOfMonth()->addDays(45),
                'periode_debut' => $debut,
                'periode_fin' => $debut->copy()->endOfMonth(),
            ]);
        }

        $service = app(CotisationService::class);

        $this->assertSame(0, $service->moisArrieres($adherent));
        $this->assertSame(3, $service->penaliteRetardMois($adherent));
        $this->assertSame(11, $service->delaiCarenceMinimum($adherent)); // 8 + 3
    }

    public function test_signalement_radiation_au_dela_de_3_mois_darrieres(): void
    {
        $adherentRecent = Adherent::factory()->create(['date_adhesion' => now()->subMonths(2)->startOfMonth()]);
        $adherentAncien = Adherent::factory()->create(['date_adhesion' => now()->subMonths(6)->startOfMonth()]);

        $service = app(CotisationService::class);

        $this->assertFalse($service->doitEtreSignalePourArrieres($adherentRecent));
        $this->assertTrue($service->doitEtreSignalePourArrieres($adherentAncien));
    }

    public function test_adhesion_avant_le_15_doit_la_cotisation_du_mois_en_cours(): void
    {
        $this->travelTo(now()->setDate(2026, 6, 1)->startOfDay());

        // Adhésion le 10 avril (avant le 15) : doit avril, mai, juin = 3 mois.
        $adherent = Adherent::factory()->create(['date_adhesion' => '2026-04-10']);

        $du = app(CotisationService::class)->calculerSolde($adherent)['du'];

        $this->assertSame(3 * 4500, $du);
    }

    public function test_adhesion_le_15_ou_apres_ne_doit_qua_partir_du_mois_suivant(): void
    {
        $this->travelTo(now()->setDate(2026, 6, 1)->startOfDay());

        // Adhésion le 20 avril (à partir du 15) : ne doit qu'à partir de
        // mai, donc mai + juin = 2 mois (pas avril).
        $adherent = Adherent::factory()->create(['date_adhesion' => '2026-04-20']);

        $du = app(CotisationService::class)->calculerSolde($adherent)['du'];

        $this->assertSame(2 * 4500, $du);
    }

    public function test_carence_de_base_dune_personne_a_charge_suit_sa_propre_annotation_nee_apres_1956(): void
    {
        $adherent = Adherent::factory()->create(['date_naissance' => '1980-01-01']);
        $service = app(CotisationService::class);

        $personneAncienne = PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'date_naissance' => null,
            'nee_apres_1956' => false,
        ]);
        $this->assertSame(12, $service->delaiCarenceBaseAge($personneAncienne));

        $personneRecente = PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'date_naissance' => null,
            'nee_apres_1956' => true,
        ]);
        $this->assertSame(8, $service->delaiCarenceBaseAge($personneRecente));
    }

    public function test_delai_de_grace_jusquau_5_du_mois_avant_penalite_de_retard(): void
    {
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonth()->startOfMonth(),
        ]);
        // Aucun paiement : le mois précédent (seule période passée) n'est pas payé.

        $service = app(CotisationService::class);

        // Le 3 du mois : encore dans le délai de grâce jusqu'au 5, le mois
        // précédent n'est pas encore considéré en retard.
        $this->travelTo(now()->startOfMonth()->addDays(2));
        $this->assertSame(0, $service->moisArrieres($adherent));
        $this->assertSame(0, $service->penaliteRetardMois($adherent));

        // Le 6 du mois : le délai de grâce est dépassé, le mois précédent
        // compte désormais comme en arriéré — mais tant qu'il n'est pas
        // réglé, la pénalité de carence (liée à l'acte de payer en retard)
        // ne s'applique pas encore.
        $this->travelTo(now()->startOfMonth()->addDays(5));
        $this->assertSame(1, $service->moisArrieres($adherent));
        $this->assertSame(0, $service->penaliteRetardMois($adherent));

        // Le mutualiste règle finalement ce mois, après le délai du 5 : la
        // pénalité de carence se déclenche à ce moment précis.
        $debut = now()->subMonth()->startOfMonth();
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 4500,
            'date_paiement' => now(),
            'periode_debut' => $debut,
            'periode_fin' => $debut->copy()->endOfMonth(),
        ]);

        $this->assertSame(0, $service->moisArrieres($adherent));
        $this->assertSame(1, $service->penaliteRetardMois($adherent));
    }

    public function test_penalite_de_retard_reste_acquise_meme_apres_reglement_complet(): void
    {
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(2)->startOfMonth(),
        ]);

        // Mois -2 : payé, mais 45 jours après la fin de la période, donc
        // bien après le délai du 5 du mois suivant.
        $debutM2 = now()->subMonths(2)->startOfMonth();
        $finM2 = $debutM2->copy()->endOfMonth();
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 4500,
            'date_paiement' => $finM2->copy()->addDays(45),
            'periode_debut' => $debutM2,
            'periode_fin' => $finM2,
        ]);

        // Mois -1 : payé à temps.
        $debutM1 = now()->subMonths(1)->startOfMonth();
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 4500,
            'date_paiement' => $debutM1,
            'periode_debut' => $debutM1,
            'periode_fin' => $debutM1->copy()->endOfMonth(),
        ]);

        $service = app(CotisationService::class);

        // Aucune dette aujourd'hui (tout est payé) : le signalement pour
        // arriérés actuels ne doit pas se déclencher...
        $this->assertSame(0, $service->moisArrieres($adherent));
        $this->assertFalse($service->doitEtreSignalePourArrieres($adherent));

        // ...mais le retard du mois -2 a bien eu lieu : la pénalité de
        // carence reste acquise, elle ne s'efface pas parce qu'on s'est
        // rattrapé depuis (Article 7 : « verront leurs délais de carence
        // augmentés d'un mois », l'acte d'avoir payé en retard suffit).
        $this->assertSame(1, $service->moisPayesEnRetard($adherent));
        $this->assertSame(1, $service->penaliteRetardMois($adherent));
        $this->assertSame(9, $service->delaiCarenceMinimum($adherent)); // 8 + 1
    }

    public function test_date_fin_carence_fixee_manuellement_remplace_le_calcul_automatique_pour_ladherent(): void
    {
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(2)->startOfMonth(),
            // Sans cette date forcée, le calcul automatique donnerait 8 mois
            // (né après 1956, aucune pénalité) : la date forcée doit primer.
            'date_fin_carence_indicative' => now()->addMonth()->startOfMonth(),
        ]);

        $service = app(CotisationService::class);

        $this->assertTrue($service->dateFinCarence($adherent)->isSameDay(now()->addMonth()->startOfMonth()));
    }

    public function test_date_fin_carence_fixee_manuellement_remplace_le_calcul_automatique_pour_une_personne_a_charge(): void
    {
        $adherent = Adherent::factory()->create(['date_naissance' => '1980-01-01']);
        $personne = PersonneACharge::factory()->create([
            'adherent_id' => $adherent->id,
            'date_naissance' => '2015-01-01',
            'date_adhesion' => now()->subMonths(2)->startOfMonth(),
            'date_fin_carence_indicative' => now()->addYear()->startOfMonth(),
        ]);

        $service = app(CotisationService::class);

        $this->assertTrue($service->dateFinCarence($adherent, $personne)->isSameDay(now()->addYear()->startOfMonth()));
    }

    public function test_une_penalite_de_retard_survenant_apres_la_date_fixee_manuellement_continue_de_sappliquer(): void
    {
        // La date forcée sert de base corrigée : elle ne fige pas la
        // carence pour l'avenir. Un retard de paiement survenant même
        // après avoir été fixée doit toujours repousser la date de fin
        // de carence, exactement comme pour un adhérent sans date forcée.
        $adherent = Adherent::factory()->create([
            'date_naissance' => '1980-01-01',
            'date_adhesion' => now()->subMonths(2)->startOfMonth(),
            'date_fin_carence_indicative' => now()->addMonth()->startOfMonth(),
        ]);

        $service = app(CotisationService::class);
        $this->assertSame(0, $service->penaliteRetardMois($adherent));
        $this->assertTrue($service->dateFinCarence($adherent)->isSameDay(now()->addMonth()->startOfMonth()));

        // Le mois précédent est réglé bien après le délai du 5 : pénalité
        // de +1 mois, acquise après coup.
        $debut = now()->subMonth()->startOfMonth();
        Cotisation::factory()->create([
            'adherent_id' => $adherent->id,
            'montant' => 4500,
            'date_paiement' => $debut->copy()->endOfMonth()->addDays(45),
            'periode_debut' => $debut,
            'periode_fin' => $debut->copy()->endOfMonth(),
        ]);

        $this->assertSame(1, $service->penaliteRetardMois($adherent));
        $this->assertTrue($service->dateFinCarence($adherent)->isSameDay(now()->addMonths(2)->startOfMonth()));
    }
}
