<?php

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Enums\Sexe;
use App\Enums\StatutAdherent;
use App\Models\Adherent;
use App\Models\User;
use App\Services\MatriculeGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest-wide')]
class Inscription extends Component
{
    public string $nom = '';

    public string $prenom = '';

    public string $sexe = 'M';

    public string $date_naissance = '';

    public string $telephone = '';

    public string $email = '';

    public string $etablissement = '';

    public string $ville = '';

    public string $fonction = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'nom' => [
                'required', 'string', 'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $nom = mb_strtolower(trim($value));
                    $prenom = mb_strtolower(trim($this->prenom));

                    // Un adhérent existant sans date de naissance enregistrée
                    // (cas fréquent pour les dossiers importés depuis l'ancien
                    // suivi Excel de la mutuelle, qui ne comportait pas cette
                    // colonne) est comparé sur nom + prénom seuls : une date
                    // manquante ne doit jamais laisser passer un vrai doublon.
                    $existeDeja = Adherent::whereRaw('LOWER(nom) = ?', [$nom])
                        ->whereRaw('LOWER(prenom) = ?', [$prenom])
                        ->where(function ($query) {
                            $query->whereDate('date_naissance', $this->date_naissance)
                                ->orWhereNull('date_naissance');
                        })
                        ->exists();

                    if ($existeDeja) {
                        $fail("Un adhérent avec ce nom et ce prénom existe déjà. Si c'est vous, connectez-vous avec votre matricule ou contactez l'administrateur de la mutuelle.");
                    }
                },
            ],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', Rule::enum(Sexe::class)],
            'date_naissance' => ['required', 'date', 'before:-18 years'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:adherents,email'],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'password' => 'mot de passe',
        ];
    }

    public function inscrire(MatriculeGenerator $matriculeGenerator): void
    {
        $valides = $this->validate();

        $dateAdhesion = now();

        $user = DB::transaction(function () use ($valides, $dateAdhesion, $matriculeGenerator) {
            $user = User::create([
                'name' => "{$valides['prenom']} {$valides['nom']}",
                'email' => $valides['email'],
                'password' => Hash::make($valides['password']),
            ]);
            $user->assignRole(Role::Adherent->value);

            Adherent::create([
                'user_id' => $user->id,
                'matricule' => $matriculeGenerator->pourNouvelAdherent($dateAdhesion),
                'nom' => $valides['nom'],
                'prenom' => $valides['prenom'],
                'sexe' => $valides['sexe'],
                'date_naissance' => $valides['date_naissance'],
                'telephone' => $valides['telephone'],
                'email' => $valides['email'],
                'etablissement' => $valides['etablissement'],
                'ville' => $valides['ville'],
                'fonction' => $valides['fonction'],
                'date_adhesion' => $dateAdhesion,
                'statut' => StatutAdherent::EnAttente,
            ]);

            return $user;
        });

        Auth::login($user);

        session()->flash('status', 'Votre compte a été créé. Un gestionnaire doit valider votre adhésion avant que vous puissiez soumettre des demandes ou payer en ligne — vous pouvez dès à présent compléter votre profil.');

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.inscription', ['sexes' => Sexe::cases()]);
    }
}
