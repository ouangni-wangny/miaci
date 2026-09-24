<?php

namespace App\Livewire\MonEspace;

use App\Enums\Sexe;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Profil extends Component
{
    use WithFileUploads;

    public string $nom = '';

    public string $prenom = '';

    public string $sexe = 'M';

    public string $date_naissance = '';

    public string $telephone = '';

    public string $email = '';

    public string $etablissement = '';

    public string $ville = '';

    public string $fonction = '';

    public $photo = null;

    public string $mot_de_passe_actuel = '';

    public string $nouveau_mot_de_passe = '';

    public string $nouveau_mot_de_passe_confirmation = '';

    public bool $formulaireAyantDroitOuvert = false;

    public string $ayant_droit_nom = '';

    public string $ayant_droit_telephone = '';

    public function mount(): void
    {
        $adherent = auth()->user()->adherent;

        abort_unless($adherent, 403, "Aucune fiche adhérent n'est associée à votre compte.");

        $this->authorize('updateProfil', $adherent);

        $this->nom = $adherent->nom;
        $this->prenom = $adherent->prenom;
        $this->sexe = $adherent->sexe?->value ?? 'M';
        $this->date_naissance = $adherent->date_naissance?->format('Y-m-d') ?? '';
        $this->telephone = (string) $adherent->telephone;
        $this->email = (string) $adherent->email;
        $this->etablissement = (string) $adherent->etablissement;
        $this->ville = (string) $adherent->ville;
        $this->fonction = (string) $adherent->fonction;
        $this->ayant_droit_nom = (string) $adherent->ayant_droit_nom;
        $this->ayant_droit_telephone = (string) $adherent->ayant_droit_telephone;
    }

    protected function rules(): array
    {
        $adherent = auth()->user()->adherent;

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', Rule::enum(Sexe::class)],
            'date_naissance' => ['required', 'date', 'before:-18 years'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('adherents', 'email')->ignore($adherent->id),
                Rule::unique('users', 'email')->ignore($adherent->user_id),
            ],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048', Rule::dimensions()->minWidth(300)->minHeight(370)],
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
            'photo' => 'photo',
        ];
    }

    public function enregistrer(): void
    {
        $adherent = auth()->user()->adherent;

        $this->authorize('updateProfil', $adherent);

        $valides = $this->validate();
        $photo = $valides['photo'] ?? null;
        unset($valides['photo']);

        if ($photo) {
            if ($adherent->photo_path) {
                Storage::disk('public')->delete($adherent->photo_path);
            }

            $valides['photo_path'] = $photo->store('adherents/photos', 'public');
        }

        // Un champ vidé arrive comme une chaîne vide, pas comme null : on
        // l'enregistre comme null (aucun email) plutôt que comme « ».
        $valides['email'] = filled($valides['email'] ?? null) ? $valides['email'] : null;

        $adherent->update($valides);

        // L'email de contact sert aussi d'identifiant de connexion dès qu'il
        // est renseigné, en complément du matricule (voir LoginForm). Il ne
        // doit jamais vider l'email du compte : la colonne users.email est
        // obligatoire, et un compte sans email ne peut plus se connecter par email.
        if ($valides['email'] !== null && $valides['email'] !== $adherent->user->email) {
            $adherent->user->update(['email' => $valides['email']]);
        }

        $this->reset('photo');

        session()->flash('status', 'Votre profil a été mis à jour.');
    }

    public function changerMotDePasse(): void
    {
        $valides = $this->validate([
            'mot_de_passe_actuel' => ['required', 'string', 'current_password'],
            'nouveau_mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'mot_de_passe_actuel' => 'mot de passe actuel',
            'nouveau_mot_de_passe' => 'nouveau mot de passe',
        ]);

        auth()->user()->update(['password' => Hash::make($valides['nouveau_mot_de_passe'])]);

        $this->reset(['mot_de_passe_actuel', 'nouveau_mot_de_passe', 'nouveau_mot_de_passe_confirmation']);

        session()->flash('status', 'Votre mot de passe a été modifié.');
    }

    public function ouvrirFormulaireAyantDroit(): void
    {
        $this->formulaireAyantDroitOuvert = true;
    }

    public function fermerFormulaireAyantDroit(): void
    {
        $this->formulaireAyantDroitOuvert = false;
        $this->resetValidation(['ayant_droit_nom', 'ayant_droit_telephone']);
    }

    public function enregistrerAyantDroit(): void
    {
        $adherent = auth()->user()->adherent;

        $this->authorize('updateProfil', $adherent);

        $valides = $this->validate([
            'ayant_droit_nom' => ['required', 'string', 'max:255'],
            'ayant_droit_telephone' => ['required', 'string', 'max:30'],
        ], [], [
            'ayant_droit_nom' => "nom complet de l'ayant droit",
            'ayant_droit_telephone' => "téléphone de l'ayant droit",
        ]);

        $adherent->update($valides);

        $this->formulaireAyantDroitOuvert = false;

        session()->flash('status', 'Ayant droit enregistré.');
    }

    public function supprimerAyantDroit(): void
    {
        $adherent = auth()->user()->adherent;

        $this->authorize('updateProfil', $adherent);

        $adherent->update(['ayant_droit_nom' => null, 'ayant_droit_telephone' => null]);

        $this->ayant_droit_nom = '';
        $this->ayant_droit_telephone = '';

        session()->flash('status', 'Ayant droit retiré.');
    }

    public function render(): View
    {
        return view('livewire.mon-espace.profil', [
            'adherent' => auth()->user()->adherent,
            'sexes' => Sexe::cases(),
        ]);
    }
}
