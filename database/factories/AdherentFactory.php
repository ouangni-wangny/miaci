<?php

namespace Database\Factories;

use App\Enums\Sexe;
use App\Enums\StatutAdherent;
use App\Models\Adherent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Adherent>
 */
class AdherentFactory extends Factory
{
    private const ETABLISSEMENTS = [
        'EPP Abobo 1' => 'Abidjan',
        'EPP Yopougon-Attié' => 'Abidjan',
        'EPP Cocody Danga' => 'Abidjan',
        'EPP Marcory Zone 4' => 'Abidjan',
        'EPP Treichville Arras' => 'Abidjan',
        'EPP Bouaké Air France 1' => 'Bouaké',
        'EPP Daloa Orly' => 'Daloa',
        'EPP San-Pédro Bardot' => 'San-Pédro',
        'EPP Korhogo Petit Paris' => 'Korhogo',
        'EPP Man Libreville' => 'Man',
        'EPP Gagnoa Dioulabougou' => 'Gagnoa',
        'EPP Divo Résidentiel' => 'Divo',
    ];

    private const FONCTIONS = [
        'Instituteur adjoint', 'Instituteur', 'Instituteur principal', "Directeur d'école",
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sexe = fake()->randomElement(Sexe::cases());
        $prenom = $sexe === Sexe::Masculin ? fake()->firstNameMale() : fake()->firstNameFemale();
        $etablissement = fake()->randomElement(array_keys(self::ETABLISSEMENTS));

        return [
            'matricule' => fake()->unique()->numerify('MIACI-#####'),
            'nom' => fake()->lastName(),
            'prenom' => $prenom,
            'sexe' => $sexe,
            'date_naissance' => fake()->dateTimeBetween('-58 years', '-23 years'),
            'telephone' => fake()->numerify('07 ## ## ## ##'),
            'email' => fake()->unique()->safeEmail(),
            'etablissement' => $etablissement,
            'ville' => self::ETABLISSEMENTS[$etablissement],
            'fonction' => fake()->randomElement(self::FONCTIONS),
            'date_adhesion' => fake()->dateTimeBetween('-10 years', '-1 month'),
            'statut' => StatutAdherent::Actif,
            'photo_path' => null,
        ];
    }

    public function suspendu(): static
    {
        return $this->state(fn () => ['statut' => StatutAdherent::Suspendu]);
    }

    public function radie(): static
    {
        return $this->state(fn () => ['statut' => StatutAdherent::Radie]);
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['statut' => StatutAdherent::EnAttente]);
    }
}
