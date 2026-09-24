<?php

namespace Database\Seeders;

use App\Enums\BeneficiaireType;
use App\Enums\FrequenceCotisation;
use App\Enums\ModePaiement;
use App\Enums\PreresultatEligibilite;
use App\Enums\Role;
use App\Enums\StatutCotisation;
use App\Enums\StatutDemandeSinistre;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\DemandeSinistre;
use App\Models\DroitAdhesion;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Models\PieceJustificative;
use App\Models\PieceRequiseTypeSinistre;
use App\Models\TypeSinistre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Données de démonstration (adhérents, cotisations, sinistres...).
 * Construit progressivement au fil des modules.
 */
class DemoDataSeeder extends Seeder
{
    /** @var array<int, Adherent> */
    private array $adherentsNommes = [];

    public function run(): void
    {
        $this->seedAdherentsDemo();
        $this->seedCotisationsDemo();
        $this->seedSinistresDemo();
    }

    private function seedAdherentsDemo(): void
    {
        // Trois adhérents nommés, avec compte de connexion, pour la démonstration.
        // Dates d'adhésion choisies pour illustrer le délai de carence
        // réglementaire (8 mois minimum pour un adhérent né après 1956) :
        // Aya (10 mois) le dépasse, Fatoumata (3 mois) y est encore soumise.
        $comptes = [
            ['nom' => 'Koffi', 'prenom' => 'Aya', 'email' => 'aya.koffi@miaci.ci', 'sexe' => 'F', 'anciennete_mois' => 10],
            ['nom' => 'Ouattara', 'prenom' => 'Ibrahim', 'email' => 'ibrahim.ouattara@miaci.ci', 'sexe' => 'M', 'anciennete_mois' => 10],
            ['nom' => 'Bamba', 'prenom' => 'Fatoumata', 'email' => 'fatoumata.bamba@miaci.ci', 'sexe' => 'F', 'anciennete_mois' => 3],
        ];

        $adherentsNommes = [];

        foreach ($comptes as $i => $donnees) {
            $user = User::factory()->create([
                'name' => "{$donnees['prenom']} {$donnees['nom']}",
                'email' => $donnees['email'],
                'password' => Hash::make('password'),
            ]);
            $user->assignRole(Role::Adherent->value);

            $adherent = Adherent::factory()->create([
                'user_id' => $user->id,
                'matricule' => 'MIACI-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'nom' => $donnees['nom'],
                'prenom' => $donnees['prenom'],
                'sexe' => $donnees['sexe'],
                'email' => $donnees['email'],
                'date_naissance' => now()->subYears(35),
                'date_adhesion' => now()->subMonths($donnees['anciennete_mois'])->startOfMonth(),
            ]);

            $this->adherentsNommes[] = $adherent;
        }

        // Personnes à charge de démonstration (chacune cotise comme un membre
        // à part entière : date d'adhésion alignée sur celle du tuteur).
        PersonneACharge::factory()->count(2)->validee()->create([
            'adherent_id' => $this->adherentsNommes[0]->id,
            'date_adhesion' => $this->adherentsNommes[0]->date_adhesion,
        ]);
        PersonneACharge::factory()->create(['adherent_id' => $this->adherentsNommes[0]->id]);
        PersonneACharge::factory()->validee()->create([
            'adherent_id' => $this->adherentsNommes[1]->id,
            'date_adhesion' => $this->adherentsNommes[1]->date_adhesion,
        ]);
        PersonneACharge::factory()->validee()->create([
            'adherent_id' => $this->adherentsNommes[2]->id,
            'lien_parente' => 'Père',
            'date_adhesion' => $this->adherentsNommes[2]->date_adhesion,
        ]);

        // Adhérent auto-inscrit, en attente de validation par un gestionnaire.
        $userEnAttente = User::factory()->create([
            'name' => 'Jean Yao',
            'email' => 'jean.yao@miaci.ci',
            'password' => Hash::make('password'),
        ]);
        $userEnAttente->assignRole(Role::Adherent->value);
        Adherent::factory()->enAttente()->create([
            'user_id' => $userEnAttente->id,
            'matricule' => 'PROV-DEMO0001',
            'nom' => 'Yao',
            'prenom' => 'Jean',
            'email' => 'jean.yao@miaci.ci',
            'date_adhesion' => now(),
        ]);

        // Adhérents supplémentaires sans compte de connexion (import type CSV).
        Adherent::factory()->count(17)->create();
        Adherent::factory()->suspendu()->count(2)->create();
        Adherent::factory()->radie()->count(1)->create();
    }

    private function seedCotisationsDemo(): void
    {
        // Valeurs du règlement MIACI : cotisation plafonnée à 4 500 F/mois et
        // par cotisant (adhérent ou personne à charge), droit d'adhésion de
        // 11 000 F payé une fois par personne.
        ParametreCotisation::create([
            'montant' => 4500,
            'droit_adhesion' => 11000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(3)->startOfYear(),
            'actif' => true,
        ]);

        [$aya, $ibrahim, $fatoumata] = $this->adherentsNommes;

        // Aya (+ 2 personnes à charge validées, donc 3 cotisants) : à jour,
        // paie régulièrement depuis son adhésion (10 mois).
        for ($i = 9; $i >= 0; $i--) {
            $debut = now()->subMonths($i)->startOfMonth();
            Cotisation::create([
                'adherent_id' => $aya->id,
                'montant' => 4500 * 3,
                'date_paiement' => (clone $debut)->addDays(2),
                'periode_debut' => $debut,
                'periode_fin' => (clone $debut)->endOfMonth(),
                'mode_paiement' => ModePaiement::MobileMoney,
                'statut' => StatutCotisation::Valide,
            ]);
        }

        // Ibrahim (+ 1 personne à charge validée, donc 2 cotisants) :
        // paiements partiels, en retard.
        for ($i = 5; $i >= 3; $i--) {
            $debut = now()->subMonths($i)->startOfMonth();
            Cotisation::create([
                'adherent_id' => $ibrahim->id,
                'montant' => 4500 * 2,
                'date_paiement' => (clone $debut)->addDays(5),
                'periode_debut' => $debut,
                'periode_fin' => (clone $debut)->endOfMonth(),
                'mode_paiement' => ModePaiement::Especes,
                'enregistre_par' => null,
                'statut' => StatutCotisation::Valide,
            ]);
        }

        // Fatoumata : aucun paiement, en retard depuis son adhésion.

        $this->seedDroitsAdhesionDemo();
    }

    /**
     * Droit d'adhésion : Aya et ses personnes à charge validées sont à jour ;
     * Ibrahim, Fatoumata et la personne à charge d'Ibrahim ne le sont pas
     * encore, pour illustrer le suivi côté gestionnaire.
     */
    private function seedDroitsAdhesionDemo(): void
    {
        [$aya] = $this->adherentsNommes;

        DroitAdhesion::create([
            'payable_type' => Adherent::class,
            'payable_id' => $aya->id,
            'montant' => 11000,
            'date_paiement' => $aya->date_adhesion,
            'mode_paiement' => ModePaiement::MobileMoney,
        ]);

        $aya->personnesACharge()->where('valide_par_gestionnaire', true)->each(function (PersonneACharge $personne) {
            DroitAdhesion::create([
                'payable_type' => PersonneACharge::class,
                'payable_id' => $personne->id,
                'montant' => 11000,
                'date_paiement' => $personne->date_adhesion ?? $personne->created_at,
                'mode_paiement' => ModePaiement::MobileMoney,
            ]);
        });
    }

    /**
     * Types de sinistre et demandes de démonstration.
     * Types de sinistre conformes au règlement MIACI (Article 7). Le Décès
     * est le seul actuellement actif et versé sous forme de montant
     * forfaitaire unique. Mariage, Naissance, Dot et Prêt sont marqués
     * "Suspendue" par le règlement lui-même : ils sont créés inactifs, avec
     * le palier le plus bas du barème en plafond indicatif — le barème
     * complet par palier (adhésion du conjoint, nombre de personnes dans le
     * carnet) n'est pas encore implémenté, cf. mémoire de session.
     */
    private function seedSinistresDemo(): void
    {
        [$aya, $ibrahim, $fatoumata] = $this->adherentsNommes;

        $deces = TypeSinistre::create([
            'code' => 'DECES', 'libelle' => 'Décès',
            'description' => "Assistance décès : montant forfaitaire unique, versé au bénéficiaire déclaré (l'adhérent lui-même ou une personne à charge). La cotisation du mois en cours est déduite du versement.",
            'plafond_montant' => 1000000, 'delai_carence_mois' => 0,
            'cotisation_a_jour_requise' => true, 'actif' => true,
        ]);
        $deces->piecesRequises()->createMany([
            ['libelle' => 'Acte de décès', 'obligatoire' => true],
            ['libelle' => "Pièce d'identité du défunt", 'obligatoire' => true],
        ]);

        TypeSinistre::create([
            'code' => 'MARIAGE', 'libelle' => 'Mariage',
            'description' => 'Suspendue par le règlement en vigueur. Montant variable selon que le conjoint est aussi adhérent et le nombre de personnes dans le carnet (25 000 à 300 000 F) — barème par palier non encore configuré ici, plafond indicatif au palier le plus bas.',
            'plafond_montant' => 50000, 'delai_carence_mois' => 0,
            'cotisation_a_jour_requise' => true, 'actif' => false,
        ]);

        TypeSinistre::create([
            'code' => 'NAISSANCE', 'libelle' => 'Naissance',
            'description' => "Suspendue par le règlement en vigueur. Montant variable selon que l'autre parent est aussi adhérent et le nombre de personnes dans le carnet (35 000 à 150 000 F) — barème par palier non encore configuré ici, plafond indicatif au palier le plus bas.",
            'plafond_montant' => 35000, 'delai_carence_mois' => 0,
            'cotisation_a_jour_requise' => true, 'actif' => false,
        ]);

        TypeSinistre::create([
            'code' => 'DOT', 'libelle' => 'Dot',
            'description' => 'Active dans le règlement en vigueur. Montant variable selon que le conjoint est aussi adhérent et le nombre de personnes dans le carnet (25 000 à 75 000 F) — barème par palier non encore configuré ici, plafond indicatif au palier le plus bas.',
            'plafond_montant' => 25000, 'delai_carence_mois' => 0,
            'cotisation_a_jour_requise' => true, 'actif' => true,
        ]);

        TypeSinistre::create([
            'code' => 'PRET', 'libelle' => 'Prêt',
            'description' => 'Suspendue par le règlement en vigueur. 200 000 F remboursable sur 6 mois sans intérêt.',
            'plafond_montant' => 200000, 'delai_carence_mois' => 0,
            'cotisation_a_jour_requise' => true, 'actif' => false,
        ]);

        $pereAya = $aya->personnesACharge()->where('valide_par_gestionnaire', true)->first();
        $mereIbrahim = $ibrahim->personnesACharge()->where('valide_par_gestionnaire', true)->first();
        $pereFatoumata = $fatoumata->personnesACharge()->where('valide_par_gestionnaire', true)->first();

        // Aya (à jour de cotisation, 10 mois d'ancienneté) : décès de son
        // père (personne à charge), demande approuvée.
        $demandeAya = $aya->demandesSinistre()->create([
            'type_sinistre_id' => $deces->id,
            'beneficiaire_type' => BeneficiaireType::PersonneACharge,
            'personne_a_charge_id' => $pereAya?->id,
            'description' => 'Décès du père de l\'adhérente, personne à charge déclarée.',
            'montant_demande' => 1000000,
            'montant_accorde' => 1000000,
            'date_evenement' => now()->subWeeks(3),
            'statut' => StatutDemandeSinistre::Approuvee,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementEligible,
            'motif_decision' => 'Dossier complet, décès constaté, prise en charge accordée intégralement.',
            'traite_le' => now()->subWeeks(2),
        ]);
        $this->attacherPieceDemo($demandeAya, $deces->piecesRequises->first(), 'acte-de-deces.txt');
        $this->attacherPieceDemo($demandeAya, $deces->piecesRequises->last(), 'piece-identite-defunt.txt');

        // Ibrahim (retard de cotisation) : décès de sa mère (personne à
        // charge), demande en cours d'examen, pré-résultat défavorable.
        $ibrahim->demandesSinistre()->create([
            'type_sinistre_id' => $deces->id,
            'beneficiaire_type' => BeneficiaireType::PersonneACharge,
            'personne_a_charge_id' => $mereIbrahim?->id,
            'description' => 'Décès de la mère de l\'adhérent, personne à charge déclarée.',
            'montant_demande' => 1000000,
            'date_evenement' => now()->subDays(10),
            'statut' => StatutDemandeSinistre::EnCoursExamen,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementNonEligible,
            'motif_preresultat' => "L'adhérent n'est pas à jour de ses cotisations.",
        ]);

        // Fatoumata (3 mois d'ancienneté seulement) : décès de son père
        // (personne à charge), demande rejetée pour carence non respectée.
        $fatoumata->demandesSinistre()->create([
            'type_sinistre_id' => $deces->id,
            'beneficiaire_type' => BeneficiaireType::PersonneACharge,
            'personne_a_charge_id' => $pereFatoumata?->id,
            'description' => 'Décès du père de l\'adhérente, personne à charge déclarée.',
            'montant_demande' => 1000000,
            'date_evenement' => now()->subMonth(),
            'statut' => StatutDemandeSinistre::Rejetee,
            'preresultat_eligibilite' => PreresultatEligibilite::ProbablementNonEligible,
            'motif_preresultat' => 'Délai de carence non respecté.',
            'motif_decision' => "Ancienneté insuffisante à la date de l'événement (délai de carence réglementaire de 8 mois non atteint).",
            'traite_le' => now()->subWeeks(1),
        ]);
    }

    private function attacherPieceDemo(DemandeSinistre $demande, PieceRequiseTypeSinistre $pieceRequise, string $nomFichier): void
    {
        $chemin = 'sinistres/'.$demande->id.'/'.$nomFichier;
        Storage::disk('local')->put($chemin, "Document de démonstration — {$pieceRequise->libelle}.\nÀ remplacer par un vrai document lors d'une soumission réelle.");

        PieceJustificative::create([
            'justificable_type' => DemandeSinistre::class,
            'justificable_id' => $demande->id,
            'piece_requise_type_sinistre_id' => $pieceRequise->id,
            'fichier_path' => $chemin,
            'nom_original' => $nomFichier,
            'type_mime' => 'text/plain',
            'taille' => Storage::disk('local')->size($chemin),
        ]);
    }
}
