<?php

namespace App\Livewire\Gestion\Adherents;

use App\Enums\Sexe;
use App\Enums\StatutAdherent;
use App\Models\Adherent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Import extends Component
{
    use WithFileUploads;

    public $fichier;

    public int $importes = 0;

    /** @var array<int, string> */
    public array $erreurs = [];

    public function mount(): void
    {
        $this->authorize('importer', Adherent::class);
    }

    /**
     * Colonnes attendues : matricule,nom,prenom,sexe,date_naissance,telephone,email,etablissement,fonction,date_adhesion
     */
    public function importer(): void
    {
        $this->authorize('importer', Adherent::class);

        $this->validate(['fichier' => ['required', 'file', 'mimes:csv,txt']]);

        $this->erreurs = [];
        $this->importes = 0;

        $handle = fopen($this->fichier->getRealPath(), 'r');
        $entetes = fgetcsv($handle, escape: '\\');

        if ($entetes === false) {
            $this->addError('fichier', 'Le fichier est vide.');

            return;
        }

        $entetes = array_map(fn ($e) => strtolower(trim($e)), $entetes);
        $ligne = 1;

        DB::transaction(function () use ($handle, $entetes, &$ligne) {
            while (($donnees = fgetcsv($handle, escape: '\\')) !== false) {
                $ligne++;

                if (count($donnees) !== count($entetes)) {
                    $this->erreurs[] = "Ligne {$ligne} : nombre de colonnes incorrect.";

                    continue;
                }

                $row = array_combine($entetes, $donnees);

                try {
                    $valide = validator($row, [
                        'matricule' => ['required', 'string', 'max:50', Rule::unique('adherents', 'matricule')],
                        'nom' => ['required', 'string', 'max:255'],
                        'prenom' => ['required', 'string', 'max:255'],
                        'sexe' => ['required', Rule::enum(Sexe::class)],
                        'date_naissance' => ['required', 'date'],
                        'telephone' => ['nullable', 'string', 'max:30'],
                        'email' => ['nullable', 'email', 'max:255'],
                        'etablissement' => ['nullable', 'string', 'max:255'],
                        'fonction' => ['nullable', 'string', 'max:255'],
                        'date_adhesion' => ['required', 'date'],
                    ])->validate();
                } catch (ValidationException $e) {
                    $this->erreurs[] = "Ligne {$ligne} : ".implode(' ', $e->validator->errors()->all());

                    continue;
                }

                Adherent::create([
                    ...$valide,
                    'statut' => StatutAdherent::Actif,
                ]);

                $this->importes++;
            }
        });

        fclose($handle);
    }

    public function render(): View
    {
        return view('livewire.gestion.adherents.import');
    }
}
