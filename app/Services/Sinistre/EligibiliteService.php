<?php

namespace App\Services\Sinistre;

use App\Enums\PreresultatEligibilite;
use App\Models\Adherent;
use App\Models\PersonneACharge;
use App\Models\TypeSinistre;
use App\Services\CotisationService;
use Carbon\Carbon;

/**
 * Vérification d'éligibilité indicative, exécutée à la soumission d'une
 * demande. Elle ne fait que pré-qualifier la demande (ancienneté, cotisation
 * à jour, pièces obligatoires fournies) : la décision finale reste toujours
 * humaine, prise par un gestionnaire (voir DemandeSinistre::statut).
 */
class EligibiliteService
{
    public function __construct(
        private readonly CotisationService $cotisationService,
    ) {}

    /**
     * @param  array<int>  $piecesRequisesFourniesIds  ids des PieceRequiseTypeSinistre pour lesquelles une pièce a été jointe
     * @return array{preresultat: PreresultatEligibilite, motifs: array<int, string>}
     */
    public function evaluer(
        Adherent $adherent,
        TypeSinistre $typeSinistre,
        Carbon $dateEvenement,
        array $piecesRequisesFourniesIds,
        ?PersonneACharge $personneACharge = null,
    ): array {
        $motifs = [];

        // Le bénéficiaire réel de l'assistance (l'adhérent ou l'une de ses
        // personnes à charge) cotise depuis sa propre date d'adhésion et a
        // son propre âge : c'est sur lui, pas systématiquement sur le
        // tuteur, que se vérifient l'ancienneté et le délai de carence par
        // âge. La pénalité de retard, elle, reste toujours celle du foyer
        // (les cotisations sont suivies au niveau de l'adhérent tuteur).
        $beneficiaire = $personneACharge ?? $adherent;
        $dateAdhesionBeneficiaire = Carbon::parse($beneficiaire->date_adhesion ?? $adherent->date_adhesion);

        // Le délai de carence effectif est le plus tardif entre celui
        // paramétré pour ce type de sinistre (converti en date depuis
        // l'adhésion) et la date de fin de carence du bénéficiaire — celle
        // fixée manuellement par un gestionnaire si elle existe, sinon
        // calculée depuis le règlement de la mutuelle (âge, majoré d'une
        // éventuelle pénalité de retard de cotisation du foyer).
        $dateFinCarenceType = $dateAdhesionBeneficiaire->copy()->addMonths($typeSinistre->delai_carence_mois);
        $dateFinCarenceReglementaire = $this->cotisationService->dateFinCarence($adherent, $beneficiaire);
        $dateFinCarenceRequise = $dateFinCarenceType->max($dateFinCarenceReglementaire);

        if ($dateEvenement->lt($dateFinCarenceRequise)) {
            $ancienneteMois = max(0, $dateAdhesionBeneficiaire->diffInMonths($dateEvenement));
            $delaiCarenceRequis = max($typeSinistre->delai_carence_mois, $this->cotisationService->delaiCarenceMinimum($adherent, $beneficiaire));

            $motifs[] = sprintf(
                "Délai de carence non respecté : %d mois d'ancienneté requis, %d mois constatés à la date de l'événement.",
                $delaiCarenceRequis,
                $ancienneteMois
            );
        }

        if ($beneficiaire->date_naissance === null && $beneficiaire->nee_apres_1956 === null) {
            $motifs[] = "Date de naissance manquante : le délai de carence n'a pas pu être vérifié avec certitude (valeur par défaut appliquée).";
        }

        if ($typeSinistre->cotisation_a_jour_requise && ! $this->cotisationService->estAJour($adherent)) {
            $motifs[] = "L'adhérent n'est pas à jour de ses cotisations.";
        }

        $piecesManquantes = $typeSinistre->piecesRequises()
            ->where('obligatoire', true)
            ->whereNotIn('id', $piecesRequisesFourniesIds)
            ->pluck('libelle');

        if ($piecesManquantes->isNotEmpty()) {
            $motifs[] = 'Pièce(s) obligatoire(s) manquante(s) : '.$piecesManquantes->implode(', ').'.';
        }

        return [
            'preresultat' => $motifs === []
                ? PreresultatEligibilite::ProbablementEligible
                : PreresultatEligibilite::ProbablementNonEligible,
            'motifs' => $motifs,
        ];
    }
}
