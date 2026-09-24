<?php

namespace App\Livewire\MonEspace\Sinistres;

use App\Enums\BeneficiaireType;
use App\Enums\StatutAdherent;
use App\Enums\StatutDemandeSinistre;
use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\PieceJustificative;
use App\Models\TypeSinistre;
use App\Services\Sinistre\EligibiliteService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Soumettre extends Component
{
    use WithFileUploads;

    public Adherent $adherent;

    public string $type_sinistre_id = '';

    public string $beneficiaire_type = 'adherent';

    public string $personne_a_charge_id = '';

    public string $description = '';

    public string $montant_demande = '';

    public string $date_evenement = '';

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $fichiers = [];

    public function mount(): void
    {
        $this->authorize('create', DemandeSinistre::class);

        $adherent = auth()->user()->adherent;
        abort_unless($adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");
        abort_unless(
            $adherent->statut === StatutAdherent::Actif,
            403,
            "Votre adhésion est en attente de validation par un gestionnaire. Vous pourrez soumettre une demande dès qu'elle sera validée."
        );

        $this->adherent = $adherent;
    }

    public function updatedTypeSinistreId(): void
    {
        $this->fichiers = [];
    }

    public function typeSinistreSelectionne(): ?TypeSinistre
    {
        return $this->type_sinistre_id
            ? TypeSinistre::with('piecesRequises')->find($this->type_sinistre_id)
            : null;
    }

    protected function rules(): array
    {
        $regles = [
            'type_sinistre_id' => ['required', 'exists:types_sinistre,id'],
            'beneficiaire_type' => ['required', 'in:adherent,personne_a_charge'],
            'personne_a_charge_id' => ['required_if:beneficiaire_type,personne_a_charge', 'nullable', 'exists:personnes_a_charge,id'],
            'description' => ['required', 'string', 'max:2000'],
            'montant_demande' => ['required', 'integer', 'min:1'],
            'date_evenement' => ['required', 'date', 'before_or_equal:today'],
        ];

        foreach ($this->typeSinistreSelectionne()?->piecesRequises ?? [] as $piece) {
            $regles["fichiers.{$piece->id}"] = $piece->obligatoire
                ? ['required', 'file', 'max:10240']
                : ['nullable', 'file', 'max:10240'];
        }

        return $regles;
    }

    public function enregistrer(EligibiliteService $eligibiliteService): void
    {
        $type = $this->typeSinistreSelectionne();
        abort_unless($type, 404);

        $valides = $this->validate();

        $personne = null;

        if ($valides['beneficiaire_type'] === 'personne_a_charge') {
            $personne = $this->adherent->personnesACharge()->findOrFail($valides['personne_a_charge_id']);
            abort_unless($personne->valide_par_gestionnaire, 422, "Cette personne à charge n'a pas encore été validée par un gestionnaire.");
        }

        $demande = DB::transaction(function () use ($valides, $type, $eligibiliteService, $personne) {
            $demande = $this->adherent->demandesSinistre()->create([
                'type_sinistre_id' => $type->id,
                'beneficiaire_type' => BeneficiaireType::from($valides['beneficiaire_type']),
                'personne_a_charge_id' => $valides['beneficiaire_type'] === 'personne_a_charge' ? $valides['personne_a_charge_id'] : null,
                'description' => $valides['description'],
                'montant_demande' => $valides['montant_demande'],
                'date_evenement' => $valides['date_evenement'],
                'statut' => StatutDemandeSinistre::Soumise,
            ]);

            $piecesFourniesIds = [];
            foreach ($type->piecesRequises as $pieceRequise) {
                /** @var TemporaryUploadedFile|null $fichier */
                $fichier = $this->fichiers[$pieceRequise->id] ?? null;

                if (! $fichier) {
                    continue;
                }

                $chemin = $fichier->store('sinistres/'.$demande->id, 'local');

                PieceJustificative::create([
                    'justificable_type' => DemandeSinistre::class,
                    'justificable_id' => $demande->id,
                    'piece_requise_type_sinistre_id' => $pieceRequise->id,
                    'fichier_path' => $chemin,
                    'nom_original' => $fichier->getClientOriginalName(),
                    'type_mime' => $fichier->getMimeType(),
                    'taille' => $fichier->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);

                $piecesFourniesIds[] = $pieceRequise->id;
            }

            $evaluation = $eligibiliteService->evaluer(
                $this->adherent,
                $type,
                Carbon::parse($valides['date_evenement']),
                $piecesFourniesIds,
                $personne,
            );

            $demande->update([
                'preresultat_eligibilite' => $evaluation['preresultat'],
                'motif_preresultat' => implode(' ', $evaluation['motifs']) ?: null,
            ]);

            return $demande;
        });

        session()->flash('status', 'Votre demande a été soumise avec succès.');

        $this->redirect(route('mon-espace.sinistres.fiche', $demande), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.mon-espace.sinistres.soumettre', [
            'types' => TypeSinistre::where('actif', true)->orderBy('libelle')->get(),
            'personnesACharge' => $this->adherent->personnesACharge()->where('valide_par_gestionnaire', true)->get(),
            'typeSelectionne' => $this->typeSinistreSelectionne(),
        ]);
    }
}
