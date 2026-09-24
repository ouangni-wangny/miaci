<?php

namespace App\Services;

use App\Enums\StatutCotisation;
use App\Enums\StatutRelevePeriode;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcule le solde de cotisation d'un adhérent à partir du paramétrage
 * actif (montant + fréquence). Conformément au règlement de la mutuelle,
 * chaque personne à charge validée cotise comme un membre à part entière :
 * le montant dû est donc la somme, pour l'adhérent lui-même et chacune de
 * ses personnes à charge actives et validées, du nombre de périodes
 * échues depuis leur propre date d'adhésion, multiplié par le montant
 * de la période.
 */
class CotisationService
{
    /**
     * Nombre maximum de périodes générées par relevesPeriodes(), en garde-fou.
     */
    private const MAX_PERIODES = 600;

    /**
     * @return array{du: int, paye: int, reste: int, parametre: ?ParametreCotisation}
     */
    public function calculerSolde(Adherent $adherent): array
    {
        $parametre = ParametreCotisation::actuel();
        $paye = (int) $this->cotisationsValides($adherent)->sum('montant');

        if (! $parametre) {
            return ['du' => 0, 'paye' => $paye, 'reste' => 0, 'parametre' => null];
        }

        $du = $this->cotisantsDepuis($adherent)
            ->sum(fn (Carbon $dateAdhesion) => $this->montantDuDepuis($dateAdhesion, $parametre));

        $reste = max(0, $du - $paye);

        return ['du' => $du, 'paye' => $paye, 'reste' => $reste, 'parametre' => $parametre];
    }

    /**
     * "À jour" signifie : aucun mois échu (hors mois en cours, qui n'est dû
     * qu'au 5 du mois suivant — Article 7) ne reste impayé. Contrairement à
     * calculerSolde() (solde cumulé, où une avance passée peut masquer une
     * vraie période impayée), cette méthode s'appuie sur moisArrieres(),
     * qui regarde chaque période individuellement.
     */
    public function estAJour(Adherent $adherent): bool
    {
        return $this->moisArrieres($adherent) === 0;
    }

    /**
     * Délai de carence minimum imposé par le règlement (Article 7), avant
     * application des pénalités de retard : 8 mois pour les adhérents nés
     * après 1956, 12 mois pour ceux nés en 1956 ou avant. S'applique aussi
     * bien à l'adhérent qu'à une personne à charge : chacun cotise (et est
     * donc soumis à la carence) selon sa propre date de naissance.
     */
    public function delaiCarenceBaseAge(Adherent|PersonneACharge $membre): int
    {
        if ($membre->date_naissance !== null) {
            return $membre->date_naissance->year > 1956 ? 8 : 12;
        }

        // Dossiers importés sans date de naissance exacte : la tranche
        // d'âge (avant/après 1956) est parfois connue malgré tout. À défaut,
        // on retient la valeur la plus conservatrice du règlement (12 mois).
        if ($membre->nee_apres_1956 !== null) {
            return $membre->nee_apres_1956 ? 8 : 12;
        }

        return 12;
    }

    /**
     * Nombre de périodes consécutives actuellement en arriéré (non
     * intégralement payées aujourd'hui), en remontant depuis la période la
     * plus récente déjà échue — le mois en cours est ignoré puisque son
     * paiement n'est dû qu'au 5 du mois suivant. Sert uniquement au
     * signalement de perte de qualité de membre (Article 8 : « au-delà de
     * 3 mois d'arriérés ») — une dette qui n'existe plus une fois réglée.
     * Pour la pénalité de carence, qui reste acquise même après règlement
     * d'un retard, voir moisPayesEnRetard().
     */
    public function moisArrieres(Adherent $adherent): int
    {
        $releves = $this->relevesPeriodes($adherent);

        if ($releves === []) {
            return 0;
        }

        // Le premier élément est la période en cours (la plus récente) :
        // son paiement n'est pas encore en retard, on ne le compte pas.
        $arrieres = 0;
        foreach (array_slice($releves, 1) as $i => $releve) {
            if ($releve['statut'] === StatutRelevePeriode::Paye) {
                break;
            }

            // Le mois immédiatement précédent bénéficie encore du délai de
            // paiement prévu par le règlement (Article 7) : jusqu'au 5 du
            // mois suivant sa fin, avant d'être considéré en retard. Les
            // mois plus anciens ont nécessairement déjà dépassé ce délai.
            if ($i === 0 && now()->lt($releve['fin']->copy()->addDays(5))) {
                continue;
            }

            $arrieres++;
        }

        return $arrieres;
    }

    /**
     * Nombre de périodes échues (hors mois en cours) dont le règlement
     * complet est intervenu — ou, si elles ne sont toujours pas payées,
     * dont le délai est déjà dépassé — après le 5 du mois suivant leur fin
     * (Article 7). Contrairement à moisArrieres(), qui ne reflète que la
     * situation actuelle (et oublie un retard une fois la période
     * finalement réglée), ce décompte reste acquis : « Tous les mutualistes
     * qui paieront leurs cotisations après le 5 du mois verront leurs
     * délais de carence augmentés » — c'est l'acte d'avoir payé en retard
     * qui déclenche la pénalité, pas le fait de devoir encore de l'argent
     * aujourd'hui.
     */
    public function moisPayesEnRetard(Adherent $adherent): int
    {
        $releves = $this->relevesPeriodes($adherent);

        if ($releves === []) {
            return 0;
        }

        // Le mois en cours (premier élément, le plus récent) n'est jamais
        // en retard : son paiement n'est même pas encore exigible.
        $periodesEchues = array_reverse(array_slice($releves, 1));

        $cotisations = $this->cotisationsValides($adherent)->sortBy('date_paiement')->values();

        $enRetard = 0;

        foreach ($periodesEchues as $periode) {
            $delaiLimite = $periode['fin']->copy()->addDays(5);

            $cumul = 0;
            $dateReglement = null;

            foreach ($cotisations->filter(fn ($c) => $c->periode_debut->between($periode['debut'], $periode['fin'])) as $cotisation) {
                $cumul += $cotisation->montant;

                if ($cumul >= $periode['du']) {
                    $dateReglement = $cotisation->date_paiement;
                    break;
                }
            }

            // Tant qu'une période n'est pas intégralement réglée, elle n'est
            // pas (encore) « payée en retard » au sens de l'article 7 — elle
            // relève de l'arriéré (voir moisArrieres()/doitEtreSignalePour
            // Arrieres()), pas de la pénalité de carence. La pénalité ne se
            // déclenche qu'au moment où le règlement intervient, s'il
            // intervient après le délai.
            if ($dateReglement && $dateReglement->gt($delaiLimite)) {
                $enRetard++;
            }
        }

        return $enRetard;
    }

    /**
     * Pénalité de carence (en mois) liée au retard de paiement (Article 7) :
     * +1 mois pour un mois payé après le délai du 5, +3 mois (plafonné) si
     * plus d'un mois est concerné.
     */
    public function penaliteRetardMois(Adherent $adherent): int
    {
        return match (true) {
            $this->moisPayesEnRetard($adherent) === 0 => 0,
            $this->moisPayesEnRetard($adherent) === 1 => 1,
            default => 3,
        };
    }

    /**
     * Délai de carence effectif (base + pénalité de retard éventuelle), EN
     * MOIS — sert d'affichage informatif ("X mois requis"). La pénalité de
     * retard reste toujours évaluée sur le foyer (l'adhérent tuteur, qui
     * porte les cotisations) ; $pourMembre permet de calculer le délai de
     * base sur l'âge réel du bénéficiaire (adhérent ou l'une de ses
     * personnes à charge) quand celui-ci diffère du tuteur.
     *
     * Si une date de fin de carence "de base" a été fixée manuellement par
     * un gestionnaire (voir dateFinCarence()), c'est elle qui remplace le
     * délai lié à l'âge — la pénalité de retard, elle, continue toujours
     * de s'ajouter par-dessus, y compris pour un retard survenu après coup :
     * la correction manuelle ne fige donc pas la carence pour l'avenir.
     */
    public function delaiCarenceMinimum(Adherent $adherent, Adherent|PersonneACharge|null $pourMembre = null): int
    {
        $membre = $pourMembre ?? $adherent;
        $penalite = $this->penaliteRetardMois($adherent);

        if ($membre->date_fin_carence_indicative !== null) {
            $dateAdhesion = Carbon::parse($membre->date_adhesion ?? $membre->created_at);
            $delaiBase = max(0, (int) $dateAdhesion->diffInMonths($membre->date_fin_carence_indicative));

            return $delaiBase + $penalite;
        }

        return $this->delaiCarenceBaseAge($membre) + $penalite;
    }

    /**
     * Date de fin de carence effective : celle qui fait foi pour toute
     * décision d'éligibilité (sinistre, don de fin d'année) et tout
     * affichage. Un gestionnaire peut fixer manuellement la date de
     * carence "de base" (champ sur la fiche adhérent ou personne à
     * charge) pour corriger un cas particulier — à saisir sans tenir
     * compte d'une éventuelle pénalité de retard déjà en cours, puisque
     * celle-ci (ainsi que toute pénalité future) est toujours rajoutée
     * automatiquement par-dessus, exactement comme pour le calcul par
     * défaut (âge + pénalité de retard).
     */
    public function dateFinCarence(Adherent $adherent, Adherent|PersonneACharge|null $pourMembre = null): Carbon
    {
        $membre = $pourMembre ?? $adherent;

        $dateBase = $membre->date_fin_carence_indicative !== null
            ? $membre->date_fin_carence_indicative->copy()
            : Carbon::parse($membre->date_adhesion ?? $membre->created_at)
                ->addMonths($this->delaiCarenceBaseAge($membre));

        return $dateBase->addMonths($this->penaliteRetardMois($adherent));
    }

    /**
     * Le règlement (Article 7) prévoit la perte de la qualité de membre
     * au-delà de 3 mois d'arriérés de cotisation. Cette méthode ne fait que
     * signaler la situation : la radiation reste toujours une action
     * manuelle du gestionnaire (voir Adherent::changerStatut).
     */
    public function doitEtreSignalePourArrieres(Adherent $adherent): bool
    {
        return $this->moisArrieres($adherent) > 3;
    }

    /**
     * Relevé période par période (un mois si la cotisation est mensuelle)
     * depuis la première adhésion (l'adhérent ou sa plus ancienne personne
     * à charge) jusqu'à la période en cours, avec le montant dû (qui prend
     * en compte le nombre de personnes à charge déjà entrées à cette date),
     * le montant payé et le statut de chacune. La période la plus récente
     * est en tête de liste.
     *
     * @return array<int, array{debut: Carbon, fin: Carbon, du: int, paye: int, statut: StatutRelevePeriode}>
     */
    public function relevesPeriodes(Adherent $adherent): array
    {
        $parametre = ParametreCotisation::actuel();

        if (! $parametre) {
            return [];
        }

        $moisParPeriode = $parametre->frequence->moisParPeriode();
        $datesCotisants = $this->cotisantsDepuis($adherent);
        // Le relevé ne remonte jamais avant le début du paramétrage de
        // cotisation actif : les mois antérieurs ne sont pas dus.
        $curseur = $datesCotisants->min()->max(Carbon::parse($parametre->date_debut))->copy();
        $limite = now()->startOfMonth();

        $cotisations = $this->cotisationsValides($adherent);

        $releves = [];

        for ($i = 0; $curseur <= $limite && $i < self::MAX_PERIODES; $i++) {
            $periodeFin = $curseur->copy()->addMonths($moisParPeriode)->subDay();

            $nombreCotisants = $datesCotisants->filter(fn (Carbon $d) => $d->lte($curseur))->count();
            $du = $nombreCotisants * $parametre->montant;

            $paye = (int) $cotisations
                ->filter(fn ($c) => $c->periode_debut->between($curseur, $periodeFin))
                ->sum('montant');

            $releves[] = [
                'debut' => $curseur->copy(),
                'fin' => $periodeFin->copy(),
                'du' => $du,
                'paye' => $paye,
                'statut' => match (true) {
                    $paye >= $du => StatutRelevePeriode::Paye,
                    $paye > 0 => StatutRelevePeriode::Partiel,
                    default => StatutRelevePeriode::Impaye,
                },
            ];

            $curseur = $curseur->copy()->addMonths($moisParPeriode);
        }

        return array_reverse($releves);
    }

    /**
     * Répartit un paiement (un montant total versé en une fois) sur les
     * périodes de cotisation les plus anciennes non intégralement payées
     * d'abord — jamais sur le mois en cours par défaut : un arriéré doit
     * toujours être comblé avant la période présente. Chaque période reçoit
     * au maximum ce qu'il lui manque ; un paiement partiel (qui ne couvre
     * pas entièrement une période) est enregistré tel quel, sans avancer
     * sur la période suivante. Un excédent au-delà de tout ce qui est dû
     * (adhérent déjà à jour) est crédité sur la période la plus récente
     * plutôt que d'inventer une période future.
     *
     * @return Collection<int, Cotisation> les cotisations créées (une par période touchée)
     */
    public function allouerPaiement(
        Adherent $adherent,
        int $montant,
        Carbon $datePaiement,
        string $modePaiement,
        ?string $reference,
        ?int $enregistrePar,
        ?int $transactionPaiementId = null,
    ): Collection {
        $periodes = array_reverse($this->relevesPeriodes($adherent)); // plus ancienne -> plus récente
        $creees = collect();
        $restant = $montant;

        $creer = function (array $periode, int $montantPeriode) use ($adherent, $datePaiement, $modePaiement, $reference, $enregistrePar, $transactionPaiementId): Cotisation {
            return $adherent->cotisations()->create([
                'montant' => $montantPeriode,
                'date_paiement' => $datePaiement,
                'periode_debut' => $periode['debut'],
                'periode_fin' => $periode['fin'],
                'mode_paiement' => $modePaiement,
                'reference' => $reference,
                'statut' => StatutCotisation::Valide,
                'enregistre_par' => $enregistrePar,
                'transaction_paiement_id' => $transactionPaiementId,
            ]);
        };

        foreach ($periodes as $periode) {
            if ($restant <= 0) {
                break;
            }

            $manquant = $periode['du'] - $periode['paye'];

            if ($manquant <= 0) {
                continue;
            }

            $alloue = min($restant, $manquant);
            $creees->push($creer($periode, $alloue));
            $restant -= $alloue;
        }

        if ($restant > 0) {
            $periodeCredit = end($periodes) ?: ['debut' => now()->startOfMonth(), 'fin' => now()->endOfMonth()];
            $creees->push($creer($periodeCredit, $restant));
        }

        return $creees;
    }

    /**
     * Mois de départ des cotisations (l'adhérent ou de ses personnes à
     * charge actives et validées : chacune "cotise" à partir de sa propre
     * date d'adhésion.
     *
     * @return Collection<int, Carbon>
     */
    private function cotisantsDepuis(Adherent $adherent): Collection
    {
        $dates = collect([$this->moisDebutCotisation(Carbon::parse($adherent->date_adhesion))]);

        $this->personnesACharge($adherent)->each(function (PersonneACharge $personne) use ($dates) {
            if ($personne->estActiveEtValidee()) {
                $dates->push($this->moisDebutCotisation(Carbon::parse($personne->date_adhesion ?? $personne->created_at)));
            }
        });

        return $dates;
    }

    /**
     * Personnes à charge de l'adhérent : lues sur la relation déjà chargée
     * si un appelant l'a explicitement fait (écrans en lecture seule qui
     * itèrent beaucoup d'adhérents, ex. tableau de bord — via un eager load
     * `with('personnesACharge...')` avant l'appel), sinon requêtées à
     * chaque fois (jamais mises en cache nous-mêmes) : la relation peut être
     * modifiée par ailleurs (autre requête, autre instance du même adhérent)
     * pendant la durée de vie de cet objet, une mise en cache silencieuse
     * ferait alors relire un état périmé.
     *
     * @return Collection<int, PersonneACharge>
     */
    private function personnesACharge(Adherent $adherent): Collection
    {
        return $adherent->relationLoaded('personnesACharge')
            ? $adherent->personnesACharge
            : $adherent->personnesACharge()->get();
    }

    /**
     * Cotisations valides de l'adhérent — même logique de lecture que
     * personnesACharge() ci-dessus (relation déjà chargée si l'appelant l'a
     * fait explicitement, sinon requête neuve à chaque fois).
     *
     * @return Collection<int, Cotisation>
     */
    private function cotisationsValides(Adherent $adherent): Collection
    {
        $cotisations = $adherent->relationLoaded('cotisations')
            ? $adherent->cotisations
            : $adherent->cotisations()->get();

        return $cotisations->where('statut', StatutCotisation::Valide)->values();
    }

    /**
     * Mois à partir duquel la cotisation est due (Article 7) : les membres
     * qui adhèrent avant le 15 du mois doivent la cotisation du mois en
     * cours ; ceux qui adhèrent le 15 ou après ne la doivent qu'à compter
     * du mois suivant.
     */
    private function moisDebutCotisation(Carbon $dateAdhesion): Carbon
    {
        $debutMois = $dateAdhesion->copy()->startOfMonth();

        return $dateAdhesion->day < 15 ? $debutMois : $debutMois->addMonth();
    }

    /**
     * Montant total théoriquement dû, tous cotisants confondus (adhérents
     * et personnes à charge, quel que soit leur statut aujourd'hui), pour
     * chaque mois écoulé de la période [$debut, $fin] où le paramétrage de
     * cotisation était en vigueur. Sert de "prévisionnel" pour le bilan
     * annuel : contrairement à calculerSolde() (qui ne regarde qu'un
     * adhérent encore actif aujourd'hui), cette méthode couvre aussi les
     * adhérents radiés depuis, pour refléter ce qui était réellement dû à
     * l'époque.
     *
     * Approximation assumée (comme dans le reste du service) : faute
     * d'historiser les changements de statut et de date de paiement du
     * droit d'adhésion, l'appartenance "cotisant à jour de droit
     * d'adhésion" est évaluée sur l'état actuel, pas sur l'état réel au
     * mois considéré.
     */
    public function montantAttenduPourPeriode(Carbon $debut, Carbon $fin): int
    {
        $parametre = ParametreCotisation::actuel();

        if (! $parametre) {
            return 0;
        }

        $finEffective = $fin->min(now()->startOfMonth());
        $debutEffectif = $debut->copy()->startOfMonth()->max(Carbon::parse($parametre->date_debut)->startOfMonth());

        if ($debutEffectif->gt($finEffective)) {
            return 0;
        }

        $debutsCotisation = Adherent::pluck('date_adhesion')
            ->map(fn ($d) => $this->moisDebutCotisation(Carbon::parse($d)))
            ->concat(
                PersonneACharge::with('droitsAdhesion')->get()
                    ->filter(fn (PersonneACharge $p) => $p->estActiveEtValidee())
                    ->map(fn (PersonneACharge $p) => $this->moisDebutCotisation(Carbon::parse($p->date_adhesion ?? $p->created_at)))
            );

        $moisParPeriode = $parametre->frequence->moisParPeriode();
        $total = 0;
        $curseur = $debutEffectif->copy();

        for ($i = 0; $curseur->lte($finEffective) && $i < self::MAX_PERIODES; $i++) {
            $nbCotisants = $debutsCotisation->filter(fn (Carbon $d) => $d->lte($curseur))->count();
            $total += $nbCotisants * $parametre->montant;
            $curseur = $curseur->copy()->addMonths($moisParPeriode);
        }

        return $total;
    }

    private function montantDuDepuis(Carbon $dateAdhesion, ParametreCotisation $parametre): int
    {
        $debut = $dateAdhesion->max(Carbon::parse($parametre->date_debut));
        $moisEcoules = max(0, (int) $debut->diffInMonths(now()));
        $nombrePeriodes = intdiv($moisEcoules, $parametre->frequence->moisParPeriode()) + 1;

        return $nombrePeriodes * $parametre->montant;
    }
}
