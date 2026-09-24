<?php

namespace App\Livewire\Gestion\Adherents;

use App\Enums\Role;
use App\Enums\Sexe;
use App\Enums\StatutAdherent;
use App\Livewire\Concerns\ReinitialiseMotDePasse;
use App\Models\Adherent;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MatriculeGenerator;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Création (un seul formulaire) et modification d'un adhérent tuteur.
 *
 * En modification, l'écran est découpé en sections qui s'enregistrent chacune
 * de leur côté (enregistrerProfil, enregistrerAdhesion) : modifier un numéro
 * de téléphone ne peut ainsi pas changer un statut par effet de bord. La
 * sécurité (mot de passe) est un onglet de la fiche (voir Fiche et le trait
 * ReinitialiseMotDePasse). enregistrer() reste le point d'entrée de la
 * création et applique tout d'un coup à un adhérent existant.
 */
#[Layout('layouts.app')]
class Formulaire extends Component
{
    use ReinitialiseMotDePasse;
    use WithFileUploads;

    public const SECTIONS = ['profil', 'adhesion'];

    /** Section affichée en modification : profil | adhesion (la sécurité est un onglet de la fiche). */
    #[Url(as: 'section', history: true)]
    public string $section = 'profil';

    public ?Adherent $adherent = null;

    public string $matricule = '';

    public string $nom = '';

    public string $prenom = '';

    public string $sexe = 'M';

    public string $date_naissance = '';

    public string $telephone = '';

    public string $email = '';

    public string $etablissement = '';

    public string $ville = '';

    public string $fonction = '';

    public string $date_adhesion = '';

    public string $date_fin_carence_indicative = '';

    public string $statut = 'actif';

    public bool $creerCompteAcces = true;

    public $photo = null;

    public function mount(?Adherent $adherent = null): void
    {
        if ($adherent?->exists) {
            $this->authorize('update', $adherent);

            $this->adherent = $adherent;
            $this->matricule = $adherent->matricule;
            $this->nom = $adherent->nom;
            $this->prenom = $adherent->prenom;
            $this->sexe = $adherent->sexe?->value ?? 'M';
            $this->date_naissance = $adherent->date_naissance?->format('Y-m-d') ?? '';
            $this->telephone = (string) $adherent->telephone;
            $this->email = (string) $adherent->email;
            $this->etablissement = (string) $adherent->etablissement;
            $this->ville = (string) $adherent->ville;
            $this->fonction = (string) $adherent->fonction;
            $this->date_adhesion = $adherent->date_adhesion->format('Y-m-d');
            $this->date_fin_carence_indicative = $adherent->date_fin_carence_indicative?->format('Y-m-d') ?? '';
            $this->statut = $adherent->statut->value;

            // Ancien lien vers la section « Sécurité » : elle est maintenant
            // un onglet de la fiche.
            if ($this->section === 'securite') {
                $this->redirect(route('gestion.adherents.fiche', ['adherent' => $adherent, 'onglet' => 'securite']), navigate: true);

                return;
            }

            if (! in_array($this->section, self::SECTIONS, true)) {
                $this->section = 'profil';
            }
        } else {
            $this->authorize('create', Adherent::class);

            $this->date_adhesion = now()->format('Y-m-d');
        }
    }

    protected function rules(): array
    {
        $adherentId = $this->adherent?->id;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', Rule::enum(Sexe::class)],
            'date_naissance' => ['required', 'date', 'before:-18 years'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('adherents', 'email')->ignore($adherentId)],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'date_adhesion' => ['required', 'date'],
            'date_fin_carence_indicative' => ['nullable', 'date', 'after_or_equal:date_adhesion'],
            'statut' => ['required', Rule::enum(StatutAdherent::class)],
            'photo' => ['nullable', 'image', 'max:2048', Rule::dimensions()->minWidth(300)->minHeight(370)],
            'nouveau_mot_de_passe' => ['nullable', 'string', 'min:6'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'nom' => 'nom',
            'prenom' => 'prénom',
            'sexe' => 'sexe',
            'date_naissance' => 'date de naissance',
            'telephone' => 'téléphone',
            'email' => 'email',
            'etablissement' => 'établissement',
            'ville' => 'ville',
            'fonction' => 'fonction',
            'date_adhesion' => "date d'adhésion",
            'date_fin_carence_indicative' => 'date de fin de carence',
            'statut' => 'statut',
            'nouveau_mot_de_passe' => 'mot de passe',
        ];
    }

    /**
     * Champs des règles de validation propres à chaque section.
     *
     * @var array<string, list<string>>
     */
    private const CHAMPS = [
        'profil' => ['nom', 'prenom', 'sexe', 'date_naissance', 'telephone', 'email', 'etablissement', 'ville', 'fonction', 'photo'],
        'adhesion' => ['statut', 'date_adhesion', 'date_fin_carence_indicative'],
    ];

    /**
     * Valide uniquement les champs d'une section.
     *
     * @return array<string, mixed>
     */
    private function validerSection(string $section): array
    {
        return $this->validate(Arr::only($this->rules(), self::CHAMPS[$section]));
    }

    // ---------------------------------------------------------------------
    // Sections de la modification
    // ---------------------------------------------------------------------

    public function enregistrerProfil(): void
    {
        abort_unless($this->adherent, 404);
        $this->authorize('update', $this->adherent);

        $this->appliquerProfil($this->validerSection('profil'));

        session()->flash('status', 'Profil mis à jour.');
    }

    public function enregistrerAdhesion(AuditLogger $audit): void
    {
        abort_unless($this->adherent, 404);
        $this->authorize('update', $this->adherent);

        $this->appliquerAdhesion($this->validerSection('adhesion'), $audit);

        session()->flash('status', 'Adhésion et statut mis à jour.');
    }

    // ---------------------------------------------------------------------
    // Application des données validées (partagée entre sections et création)
    // ---------------------------------------------------------------------

    /** @param  array<string, mixed>  $valides */
    private function appliquerProfil(array $valides): void
    {
        $donnees = Arr::only($valides, ['nom', 'prenom', 'sexe', 'date_naissance', 'telephone', 'email', 'etablissement', 'ville', 'fonction']);

        /** @var TemporaryUploadedFile|null $photo */
        $photo = $valides['photo'] ?? null;

        if ($photo) {
            $donnees['photo_path'] = $photo->store('adherents/photos', 'public');

            if ($this->adherent->photo_path) {
                Storage::disk('public')->delete($this->adherent->photo_path);
            }
        }

        $this->adherent->update($donnees);
        $this->photo = null;
    }

    /** @param  array<string, mixed>  $valides */
    private function appliquerAdhesion(array $valides, AuditLogger $audit): void
    {
        $donnees = Arr::only($valides, ['statut', 'date_adhesion', 'date_fin_carence_indicative']);

        // Colonne de type date : une chaîne vide (champ laissé vide dans le
        // formulaire) doit être enregistrée comme null, pas comme "".
        $donnees['date_fin_carence_indicative'] = $donnees['date_fin_carence_indicative'] ?: null;

        $statutAvant = $this->adherent->statut->value;

        if ($donnees['statut'] !== $statutAvant) {
            $this->authorize('changerStatut', $this->adherent);
        }

        $this->adherent->update($donnees);

        if ($donnees['statut'] !== $statutAvant) {
            $audit->log('adherent.statut_modifie', $this->adherent, ['statut' => $statutAvant], ['statut' => $donnees['statut']]);
        }
    }

    // ---------------------------------------------------------------------
    // Création (et enregistrement global, conservé pour compatibilité)
    // ---------------------------------------------------------------------

    public function enregistrer(AuditLogger $audit): void
    {
        $valides = $this->validate();

        if ($this->adherent) {
            $this->authorize('update', $this->adherent);

            $this->appliquerProfil($valides);
            $this->appliquerAdhesion($valides, $audit);
            $this->appliquerSecurite($valides['nouveau_mot_de_passe'] ?? null, $audit);

            session()->flash('status', 'Fiche adhérent mise à jour.');
        } else {
            $photo = $valides['photo'] ?? null;
            unset($valides['photo'], $valides['nouveau_mot_de_passe']);

            $valides['date_fin_carence_indicative'] = $valides['date_fin_carence_indicative'] ?: null;

            if ($photo) {
                $valides['photo_path'] = $photo->store('adherents/photos', 'public');
            }

            $valides['matricule'] = app(MatriculeGenerator::class)->pourNouvelAdherent(Carbon::parse($valides['date_adhesion']));

            $adherent = Adherent::create($valides);

            if ($this->creerCompteAcces) {
                $user = User::create([
                    'name' => $adherent->nomComplet(),
                    'email' => $adherent->email ?: strtolower($adherent->matricule).'@miaci.local',
                    'password' => Hash::make(User::MOT_DE_PASSE_PAR_DEFAUT),
                ]);
                $user->assignRole(Role::Adherent->value);
                $adherent->update(['user_id' => $user->id]);
            }

            session()->flash('status', 'Adhérent créé.');
        }

        $this->redirect(route('gestion.adherents.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.gestion.adherents.formulaire', [
            'sexes' => Sexe::cases(),
            'statuts' => StatutAdherent::cases(),
        ]);
    }
}
