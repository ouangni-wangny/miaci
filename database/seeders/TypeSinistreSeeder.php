<?php

namespace Database\Seeders;

use App\Models\TypeSinistre;
use Illuminate\Database\Seeder;

/**
 * Types de sinistre (assistances) du règlement MIACI en vigueur depuis le
 * 15/12/2025 (Article 7 — Adhésions-Cotisations-Assistances).
 *
 * Le schéma ne stocke qu'un plafond unique par type alors que le règlement
 * prévoit un barème à 4 paliers pour Mariage/Naissance/Dot (seul·e ou en
 * couple d'adhérents, moins ou au moins 3 personnes dans le carnet) :
 * `plafond_montant` retient le palier le plus bas (seul·e, < 3 personnes),
 * le barème complet est documenté dans `description` pour que le
 * gestionnaire ajuste le montant accordé au cas par cas lors du traitement.
 */
class TypeSinistreSeeder extends Seeder
{
    public function run(): void
    {
        $deces = TypeSinistre::updateOrCreate(
            ['code' => 'DECES'],
            [
                'libelle' => 'Décès',
                'description' => "Montant forfaitaire unique de 1 000 000 F, versé au bénéficiaire déclaré (l'adhérent lui-même ou une personne à charge). La cotisation du mois en cours du bénéficiaire est déduite avant versement. Délai pour fournir les documents : 3 semaines à compter du décès. Délai de traitement de l'assistance : 2 semaines à compter de la réception du dossier complet.",
                'plafond_montant' => 1000000,
                'delai_carence_mois' => 0,
                'cotisation_a_jour_requise' => true,
                'actif' => true,
            ]
        );
        $deces->piecesRequises()->firstOrCreate(['libelle' => 'Acte de décès'], ['obligatoire' => true]);
        $deces->piecesRequises()->firstOrCreate(['libelle' => "Pièce d'identité du défunt"], ['obligatoire' => true]);

        $dot = TypeSinistre::updateOrCreate(
            ['code' => 'DOT'],
            [
                'libelle' => 'Dot',
                'description' => 'Barème par palier : 25 000 F si le/la marié·e est seul·e adhérent·e avec moins de 3 personnes dans son carnet ; 50 000 F si seul·e adhérent·e avec au moins 3 personnes dans son carnet ; 45 000 F par personne si les deux mariés sont adhérents avec chacun moins de 3 personnes dans son carnet ; 75 000 F par personne si les deux mariés sont adhérents avec chacun au moins 3 personnes dans son carnet. Montant ci-contre = palier le plus bas, à ajuster par le gestionnaire selon la situation du dossier.',
                'plafond_montant' => 25000,
                'delai_carence_mois' => 0,
                'cotisation_a_jour_requise' => true,
                'actif' => true,
            ]
        );
        $dot->piecesRequises()->firstOrCreate(['libelle' => 'Attestation ou acte de dot'], ['obligatoire' => true]);

        $mariage = TypeSinistre::updateOrCreate(
            ['code' => 'MARIAGE'],
            [
                'libelle' => 'Mariage',
                'description' => "Suspendue par le règlement en vigueur. Barème par palier (si réactivée) : 50 000 F seul·e adhérent·e avec moins de 3 personnes dans son carnet ; 200 000 F seul·e adhérent·e avec au moins 3 personnes dans son carnet ; 80 000 F par personne si les deux mariés sont adhérents avec chacun moins de 3 personnes dans son carnet ; 300 000 F par personne si les deux mariés sont adhérents avec chacun au moins 3 personnes dans son carnet. Montant ci-contre = palier le plus bas.",
                'plafond_montant' => 50000,
                'delai_carence_mois' => 0,
                'cotisation_a_jour_requise' => true,
                'actif' => false,
            ]
        );
        $mariage->piecesRequises()->firstOrCreate(['libelle' => 'Acte de mariage'], ['obligatoire' => true]);

        $naissance = TypeSinistre::updateOrCreate(
            ['code' => 'NAISSANCE'],
            [
                'libelle' => 'Naissance',
                'description' => "Suspendue par le règlement en vigueur. Barème par palier (si réactivée) : 35 000 F au parent seul adhérent avec moins de 3 personnes dans son carnet ; 100 000 F au parent seul adhérent avec au moins 3 personnes dans son carnet ; 80 000 F par personne si les deux parents sont adhérents avec chacun moins de 3 personnes dans son carnet ; 150 000 F par personne si les deux parents sont adhérents avec chacun au moins 3 personnes dans son carnet. Montant ci-contre = palier le plus bas.",
                'plafond_montant' => 35000,
                'delai_carence_mois' => 0,
                'cotisation_a_jour_requise' => true,
                'actif' => false,
            ]
        );
        $naissance->piecesRequises()->firstOrCreate(['libelle' => 'Acte de naissance'], ['obligatoire' => true]);

        TypeSinistre::updateOrCreate(
            ['code' => 'PRET'],
            [
                'libelle' => 'Prêt',
                'description' => "Suspendue par le règlement en vigueur. 200 000 F remboursable sur 6 mois sans intérêt. Il ne s'agit pas d'une assistance à fonds perdu mais d'une avance : le suivi des remboursements n'est pas géré par ce module (demandes de sinistre) et devra être ajouté séparément si cette assistance est réactivée.",
                'plafond_montant' => 200000,
                'delai_carence_mois' => 0,
                'cotisation_a_jour_requise' => true,
                'actif' => false,
            ]
        );
    }
}
