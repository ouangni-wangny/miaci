<?php

namespace App\Livewire\Gestion\Adherents;

use App\Enums\ModePaiement;
use App\Enums\Mois;
use App\Enums\StatutAdherent;
use App\Enums\StatutCotisation;
use App\Enums\StatutDroitAdhesion;
use App\Livewire\Concerns\ReinitialiseMotDePasse;
use App\Models\Adherent;
use App\Models\Cotisation;
use App\Models\JournalAudit;
use App\Models\ParametreCotisation;
use App\Models\PersonneACharge;
use App\Services\AuditLogger;
use App\Services\CotisationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Fiche extends Component
{
    use ReinitialiseMotDePasse;

    public Adherent $adherent;

    public bool $formulaireCotisationOuvert = false;

    /**
     * Une entrée par mois en attente (clé = période au format Y-m-d) :
     * ['selectionne' => bool, 'type' => 'complet'|'partiel', 'montant_partiel' => string].
     * Le montant du paiement complet n'est jamais saisi à la main : il est
     * toujours celui, déjà connu, affiché pour ce mois — seul le montant
     * d'un paiement partiel se tape, pour limiter les erreurs de saisie.
     */
    public array $periodesAPayer = [];

    public string $date_paiement = '';

    public string $mode_paiement = 'especes';

    public string $reference = '';

    public string $anneeFiltre = '';

    public string $moisFiltre = '';

    public bool $formulaireDroitAdhesionOuvert = false;

    public string $droitAdhesionPayableType = '';

    public ?int $droitAdhesionPayableId = null;

    public string $droit_adhesion_montant = '';

    public string $droit_adhesion_date_paiement = '';

    public string $droit_adhesion_mode_paiement = 'especes';

    public string $droit_adhesion_reference = '';

    public bool $formulairePersonneOuvert = false;

    public ?int $personneEnEditionId = null;

    public string $personne_nom = '';

    public string $personne_prenom = '';

    public string $personne_date_naissance = '';

    public string $personne_lien_parente = '';

    public string $personne_date_adhesion = '';

    public string $personne_date_fin_carence_indicative = '';

    public bool $formulaireAyantDroitOuvert = false;

    public string $ayant_droit_nom = '';

    public string $ayant_droit_telephone = '';

    public function mount(Adherent $adherent): void
    {
        $this->authorize('view', $adherent);

        $this->adherent = $adherent;
    }

    public function changerStatut(string $statut, AuditLogger $audit): void
    {
        $this->authorize('changerStatut', $this->adherent);

        $statutAvant = $this->adherent->statut->value;
        $this->adherent->update(['statut' => StatutAdherent::from($statut)]);

        $audit->log(
            'adherent.statut_modifie',
            $this->adherent,
            ['statut' => $statutAvant],
            ['statut' => $statut],
        );

        session()->flash('status', 'Statut de l\'adhérent mis à jour.');
    }

    public function supprimer(AuditLogger $audit)
    {
        $this->authorize('delete', $this->adherent);

        if ($this->adherent->possedeHistorique()) {
            session()->flash('erreur', 'Impossible de supprimer : cet adhérent a un historique (cotisations, sinistres, personnes à charge...). Utilisez plutôt le statut « Radié ».');

            return;
        }

        $audit->log('adherent.supprime', $this->adherent, $this->adherent->toArray());

        DB::transaction(function () {
            $user = $this->adherent->user;
            $this->adherent->delete();
            $user?->delete();
        });

        session()->flash('status', 'Adhérent supprimé.');

        return $this->redirect(route('gestion.adherents.index'), navigate: true);
    }

    public function ouvrirFormulaireAyantDroit(): void
    {
        $this->authorize('update', $this->adherent);

        $this->ayant_droit_nom = (string) $this->adherent->ayant_droit_nom;
        $this->ayant_droit_telephone = (string) $this->adherent->ayant_droit_telephone;
        $this->formulaireAyantDroitOuvert = true;
    }

    public function fermerFormulaireAyantDroit(): void
    {
        $this->formulaireAyantDroitOuvert = false;
        $this->resetValidation(['ayant_droit_nom', 'ayant_droit_telephone']);
    }

    public function enregistrerAyantDroit(): void
    {
        $this->authorize('update', $this->adherent);

        $valides = $this->validate([
            'ayant_droit_nom' => ['required', 'string', 'max:255'],
            'ayant_droit_telephone' => ['required', 'string', 'max:30'],
        ], [], [
            'ayant_droit_nom' => "nom complet de l'ayant droit",
            'ayant_droit_telephone' => "téléphone de l'ayant droit",
        ]);

        $this->adherent->update($valides);

        $this->formulaireAyantDroitOuvert = false;

        session()->flash('status', 'Ayant droit enregistré.');
    }

    public function supprimerAyantDroit(): void
    {
        $this->authorize('update', $this->adherent);

        $this->adherent->update(['ayant_droit_nom' => null, 'ayant_droit_telephone' => null]);

        $this->ayant_droit_nom = '';
        $this->ayant_droit_telephone = '';

        session()->flash('status', 'Ayant droit retiré.');
    }

    public function ouvrirFormulairePersonne(?int $personneId = null): void
    {
        $this->resetValidation();
        $this->reset(['personne_nom', 'personne_prenom', 'personne_date_naissance', 'personne_lien_parente', 'personne_date_adhesion', 'personne_date_fin_carence_indicative']);

        if ($personneId) {
            $personne = $this->adherent->personnesACharge()->findOrFail($personneId);
            $this->authorize('update', $personne);

            $this->personneEnEditionId = $personne->id;
            $this->personne_nom = $personne->nom;
            $this->personne_prenom = $personne->prenom;
            $this->personne_date_naissance = $personne->date_naissance?->format('Y-m-d') ?? '';
            $this->personne_lien_parente = (string) $personne->lien_parente;
            $this->personne_date_adhesion = $personne->date_adhesion?->format('Y-m-d') ?? '';
            $this->personne_date_fin_carence_indicative = $personne->date_fin_carence_indicative?->format('Y-m-d') ?? '';
        } else {
            $this->authorize('create', [PersonneACharge::class, $this->adherent]);
            $this->personneEnEditionId = null;
        }

        $this->formulairePersonneOuvert = true;
    }

    public function fermerFormulairePersonne(): void
    {
        $this->formulairePersonneOuvert = false;
    }

    public function enregistrerPersonne(): void
    {
        $valides = $this->validate([
            'personne_nom' => [
                'nullable', 'string', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    // Vérifié uniquement à la création : une fois enregistrée,
                    // une personne à charge peut être modifiée librement sans
                    // revalider contre tout l'historique (des homonymies —
                    // noms très courants — existent déjà entre adhérents
                    // différents dans les données importées, sans être de
                    // vrais doublons).
                    if ($this->personneEnEditionId) {
                        return;
                    }

                    $nom = trim((string) $value);
                    $prenom = trim((string) $this->personne_prenom);

                    // Rien à comparer sans nom, prénom ET date de naissance :
                    // ces trois champs restent facultatifs sur une fiche
                    // personne à charge, mais nom+prénom seuls ne suffisent
                    // pas à conclure à un doublon — des homonymies (noms très
                    // courants) existent déjà entre adhérents différents dans
                    // les données importées, sans être de vraies doublons.
                    if ($nom === '' || $prenom === '' || ! $this->personne_date_naissance) {
                        return;
                    }

                    $doublon = PersonneACharge::with('adherent')
                        ->whereRaw('LOWER(nom) = ?', [mb_strtolower($nom)])
                        ->whereRaw('LOWER(prenom) = ?', [mb_strtolower($prenom)])
                        ->whereDate('date_naissance', $this->personne_date_naissance)
                        ->where('adherent_id', '!=', $this->adherent->id)
                        ->first();

                    if ($doublon) {
                        $fail("Une personne à charge portant ce nom et ce prénom est déjà enregistrée chez {$doublon->adherent->nomComplet()} ({$doublon->adherent->matricule}). Vérifiez qu'il ne s'agit pas de la même personne avant de continuer.");
                    }
                },
            ],
            'personne_prenom' => ['nullable', 'string', 'max:255'],
            'personne_date_naissance' => ['nullable', 'date', 'before:today'],
            'personne_lien_parente' => ['nullable', 'string', 'max:100'],
            'personne_date_adhesion' => ['nullable', 'date'],
            'personne_date_fin_carence_indicative' => ['nullable', 'date'],
        ]);
        // Aucun champ n'est obligatoire : un champ laissé vide (chaîne vide)
        // est enregistré comme null plutôt que comme une chaîne vide.
        $valides = [
            'nom' => $valides['personne_nom'] ?: null,
            'prenom' => $valides['personne_prenom'] ?: null,
            'date_naissance' => $valides['personne_date_naissance'] ?: null,
            'lien_parente' => $valides['personne_lien_parente'] ?: null,
            'date_adhesion' => $valides['personne_date_adhesion'] ?: null,
            'date_fin_carence_indicative' => $valides['personne_date_fin_carence_indicative'] ?: null,
        ];

        if ($this->personneEnEditionId) {
            $personne = $this->adherent->personnesACharge()->findOrFail($this->personneEnEditionId);
            $this->authorize('update', $personne);

            $personne->update($valides);

            session()->flash('status', 'Personne à charge mise à jour.');
        } else {
            $this->authorize('create', [PersonneACharge::class, $this->adherent]);

            // À la création, une date d'adhésion non renseignée vaut
            // aujourd'hui (comportement historique) ; en édition, le
            // gestionnaire peut au contraire l'effacer volontairement.
            $valides['date_adhesion'] ??= now();

            $this->adherent->personnesACharge()->create($valides);

            session()->flash('status', "Personne à charge ajoutée. Elle sera comptée comme cotisante dès que son droit d'adhésion sera payé.");
        }

        $this->formulairePersonneOuvert = false;
    }

    public function supprimerPersonne(int $personneId): void
    {
        $personne = $this->adherent->personnesACharge()->findOrFail($personneId);

        $this->authorize('delete', $personne);

        $personne->delete();

        session()->flash('status', 'Personne à charge retirée.');
    }

    public function ouvrirFormulaireCotisation(CotisationService $cotisationService): void
    {
        $this->authorize('create', Cotisation::class);

        $this->date_paiement = now()->format('Y-m-d');
        $this->reference = '';

        $this->periodesAPayer = collect($cotisationService->relevesPeriodes($this->adherent))
            ->filter(fn (array $p) => $p['paye'] < $p['du'])
            ->mapWithKeys(fn (array $p) => [
                $p['debut']->format('Y-m-d') => [
                    'selectionne' => false,
                    'type' => 'complet',
                    'montant_partiel' => '',
                ],
            ])
            ->all();

        $this->formulaireCotisationOuvert = true;
    }

    public function enregistrerCotisation(AuditLogger $audit, CotisationService $cotisationService): void
    {
        $this->authorize('create', Cotisation::class);

        $penaliteAvant = $cotisationService->penaliteRetardMois($this->adherent);

        $valides = $this->validate([
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['required', 'in:especes,cheque,virement,mobile_money,carte,autre'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        // Les montants dus sont recalculés à l'instant présent (pas ceux
        // affichés à l'ouverture du formulaire) pour rester exacts même si
        // la situation a changé entre-temps.
        $releves = collect($cotisationService->relevesPeriodes($this->adherent))
            ->keyBy(fn (array $p) => $p['debut']->format('Y-m-d'));

        $aEnregistrer = [];

        foreach ($this->periodesAPayer as $cle => $saisie) {
            if (! ($saisie['selectionne'] ?? false)) {
                continue;
            }

            $periode = $releves->get($cle);

            if (! $periode) {
                continue;
            }

            $restant = $periode['du'] - $periode['paye'];

            if (($saisie['type'] ?? 'complet') === 'partiel') {
                $montant = (int) ($saisie['montant_partiel'] ?? 0);

                if ($montant <= 0 || $montant >= $restant) {
                    $this->addError(
                        "periodesAPayer.{$cle}.montant_partiel",
                        'Montant partiel invalide : doit être positif et inférieur au reste dû ('.number_format($restant, 0, ',', ' ').' FCFA) — sinon choisissez « Complet ».'
                    );

                    continue;
                }
            } else {
                $montant = $restant;
            }

            $aEnregistrer[] = [
                'montant' => $montant,
                'periode_debut' => $periode['debut'],
                'periode_fin' => $periode['fin'],
            ];
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        if ($aEnregistrer === []) {
            $this->addError('periodesAPayer', 'Sélectionnez au moins un mois à régler.');

            return;
        }

        $cotisations = DB::transaction(fn () => collect($aEnregistrer)->map(
            fn (array $ligne) => $this->adherent->cotisations()->create([
                'montant' => $ligne['montant'],
                'date_paiement' => $valides['date_paiement'],
                'periode_debut' => $ligne['periode_debut'],
                'periode_fin' => $ligne['periode_fin'],
                'mode_paiement' => $valides['mode_paiement'],
                'reference' => $valides['reference'],
                'statut' => StatutCotisation::Valide,
                'enregistre_par' => auth()->id(),
            ])
        ));

        $audit->log('cotisation.enregistree', $cotisations->first(), null, [
            ...$valides,
            'periodes_couvertes' => $cotisations->map(fn (Cotisation $c) => $c->periode_debut->format('Y-m'))->all(),
        ]);

        $this->formulaireCotisationOuvert = false;
        $this->periodesAPayer = [];
        $this->reset(['reference']);

        $message = 'Cotisation enregistrée ('.$cotisations->count().' mois mis à jour).';

        if ($motifCarence = $this->motifChangementCarence($penaliteAvant, $cotisationService)) {
            $message .= ' '.$motifCarence;
        }

        session()->flash('status', $message);
    }

    /**
     * Compare la pénalité de retard (Article 7) avant/après une action sur
     * les cotisations et, si elle a changé, renvoie un message expliquant le
     * motif — la pénalité s'applique identiquement au délai de carence de
     * l'adhérent tuteur et de chacune de ses personnes à charge (voir
     * CotisationService::delaiCarenceMinimum()).
     */
    private function motifChangementCarence(int $penaliteAvant, CotisationService $cotisationService): ?string
    {
        $penaliteApres = $cotisationService->penaliteRetardMois($this->adherent);

        if ($penaliteApres === $penaliteAvant) {
            return null;
        }

        if ($penaliteApres > $penaliteAvant) {
            return "Le délai de carence de l'adhérent tuteur et de toutes ses personnes à charge est allongé : pénalité de retard passée de {$penaliteAvant} à {$penaliteApres} mois (Article 7 — cotisation réglée après le 5 du mois suivant sa fin).";
        }

        return "Le délai de carence de l'adhérent tuteur et de toutes ses personnes à charge est raccourci : pénalité de retard passée de {$penaliteAvant} à {$penaliteApres} mois suite à cette annulation.";
    }

    public function annulerCotisation(int $cotisationId, AuditLogger $audit, CotisationService $cotisationService): void
    {
        $cotisation = $this->adherent->cotisations()->findOrFail($cotisationId);

        $this->authorize('annuler', $cotisation);

        if ($cotisation->statut === StatutCotisation::Annule) {
            return;
        }

        $penaliteAvant = $cotisationService->penaliteRetardMois($this->adherent);

        $avant = $cotisation->only(['statut']);
        $cotisation->update(['statut' => StatutCotisation::Annule]);

        $audit->log('cotisation.annulee', $cotisation, $avant, ['statut' => StatutCotisation::Annule->value]);

        $message = 'Paiement annulé — il n\'est plus compté dans le solde de l\'adhérent.';

        if ($motifCarence = $this->motifChangementCarence($penaliteAvant, $cotisationService)) {
            $message .= ' '.$motifCarence;
        }

        session()->flash('status', $message);
    }

    /**
     * @param  'adherent'|'personne'  $type
     */
    public function ouvrirFormulaireDroitAdhesion(string $type, ?int $personneId = null): void
    {
        $this->authorize('create', Cotisation::class);

        $this->droitAdhesionPayableType = $type;
        $this->droitAdhesionPayableId = $personneId;
        $this->droit_adhesion_montant = (string) (ParametreCotisation::actuel()?->droit_adhesion ?? '');
        $this->droit_adhesion_date_paiement = now()->format('Y-m-d');
        $this->droit_adhesion_mode_paiement = 'especes';
        $this->droit_adhesion_reference = '';
        $this->formulaireDroitAdhesionOuvert = true;
    }

    public function fermerFormulaireDroitAdhesion(): void
    {
        $this->formulaireDroitAdhesionOuvert = false;
    }

    public function enregistrerDroitAdhesion(AuditLogger $audit): void
    {
        $this->authorize('create', Cotisation::class);

        $valides = $this->validate([
            'droit_adhesion_montant' => ['required', 'integer', 'min:1'],
            'droit_adhesion_date_paiement' => ['required', 'date'],
            'droit_adhesion_mode_paiement' => ['required', 'in:especes,cheque,virement,mobile_money,carte,autre'],
            'droit_adhesion_reference' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var Model $payable */
        $payable = $this->droitAdhesionPayableType === 'adherent'
            ? $this->adherent
            : $this->adherent->personnesACharge()->findOrFail($this->droitAdhesionPayableId);

        $droit = $payable->droitsAdhesion()->create([
            'montant' => $valides['droit_adhesion_montant'],
            'date_paiement' => $valides['droit_adhesion_date_paiement'],
            'mode_paiement' => $valides['droit_adhesion_mode_paiement'],
            'reference' => $valides['droit_adhesion_reference'],
            'enregistre_par' => auth()->id(),
        ]);

        $audit->log('droit_adhesion.enregistre', $droit, null, $valides);

        $this->formulaireDroitAdhesionOuvert = false;
        $this->reset(['droit_adhesion_montant', 'droit_adhesion_reference']);

        session()->flash('status', "Droit d'adhésion enregistré.");
    }

    /**
     * @param  'adherent'|'personne'  $type
     */
    public function annulerDroitAdhesion(string $type, ?int $personneId, AuditLogger $audit): void
    {
        /** @var Model $payable */
        $payable = $type === 'adherent'
            ? $this->adherent
            : $this->adherent->personnesACharge()->findOrFail($personneId);

        $droit = $payable->droitsAdhesion()
            ->where('statut', StatutDroitAdhesion::Valide)
            ->latest('date_paiement')
            ->firstOrFail();

        $this->authorize('annuler', $droit);

        $avant = $droit->only(['statut']);
        $droit->update(['statut' => StatutDroitAdhesion::Annule]);

        $audit->log('droit_adhesion.annule', $droit, $avant, ['statut' => StatutDroitAdhesion::Annule->value]);

        session()->flash('status', "Droit d'adhésion annulé — il n'est plus compté comme payé.");
    }

    public function render(CotisationService $cotisationService): View
    {
        $cotisations = $this->adherent->cotisations()
            ->when($this->anneeFiltre, fn ($q) => $q->whereYear('date_paiement', $this->anneeFiltre))
            ->when($this->moisFiltre, fn ($q) => $q->whereMonth('date_paiement', $this->moisFiltre))
            ->orderByDesc('date_paiement')
            ->get();

        $anneesDisponibles = $this->adherent->cotisations()
            ->pluck('date_paiement')
            ->map(fn ($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->values();

        $personnesACharge = $this->adherent->personnesACharge()->orderBy('nom')->get();

        return view('livewire.gestion.adherents.fiche', [
            'personnesACharge' => $personnesACharge,
            // Délai de carence propre à chaque personne à charge (base selon
            // son âge + pénalité de retard du foyer, sauf date de fin fixée
            // manuellement), indexé par id pour un accès direct depuis la vue.
            'carencePersonnes' => $personnesACharge->mapWithKeys(fn (PersonneACharge $personne) => [
                $personne->id => [
                    'delai' => $cotisationService->delaiCarenceMinimum($this->adherent, $personne),
                    'dateFin' => $cotisationService->dateFinCarence($this->adherent, $personne),
                    'droitAdhesion' => $personne->droitsAdhesion()
                        ->where('statut', StatutDroitAdhesion::Valide)
                        ->latest('date_paiement')
                        ->first(),
                ],
            ]),
            'droitAdhesionAdherent' => $this->adherent->droitsAdhesion()
                ->where('statut', StatutDroitAdhesion::Valide)
                ->latest('date_paiement')
                ->first(),
            // Dernières opérations de sécurité sur cet adhérent (onglet « Sécurité »).
            'journalSecurite' => JournalAudit::query()
                ->with('user')
                ->where('entite_type', Adherent::class)
                ->where('entite_id', $this->adherent->id)
                ->whereIn('action', ['adherent.mot_de_passe_reinitialise', 'adherent.statut_modifie'])
                ->latest()
                ->limit(5)
                ->get(),
            'statuts' => StatutAdherent::cases(),
            'modesPaiement' => ModePaiement::cases(),
            'solde' => $cotisationService->calculerSolde($this->adherent),
            // À jour = aucun mois échu impayé (hors mois en cours), voir
            // CotisationService::estAJour() — distinct du solde cumulé, qui
            // peut rester positif (mois en cours pas encore payé) même sans
            // arriéré réel.
            'estAJour' => $cotisationService->estAJour($this->adherent),
            'releves' => $cotisationService->relevesPeriodes($this->adherent),
            'periodesImpayees' => collect($cotisationService->relevesPeriodes($this->adherent))
                ->filter(fn (array $p) => $p['paye'] < $p['du'])
                ->reverse()
                ->values(),
            'cotisations' => $cotisations,
            'anneesDisponibles' => $anneesDisponibles,
            'moisListe' => Mois::cases(),
            'delaiCarenceMinimum' => $cotisationService->delaiCarenceMinimum($this->adherent),
            // Date calendaire exacte de fin de carence : celle fixée
            // manuellement par un gestionnaire si elle existe, sinon base
            // (8 ou 12 mois selon l'âge) + pénalité de retard éventuelle
            // (Article 7) — recalculée à chaque affichage, elle bouge
            // automatiquement dès qu'un paiement en retard allonge le délai.
            'dateFinCarence' => $cotisationService->dateFinCarence($this->adherent),
            'penaliteRetardMois' => $cotisationService->penaliteRetardMois($this->adherent),
            'signalePourArrieres' => $cotisationService->doitEtreSignalePourArrieres($this->adherent),
        ]);
    }
}
