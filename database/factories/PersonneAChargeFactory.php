<?php

namespace Database\Factories;

use App\Enums\StatutPersonneACharge;
use App\Models\PersonneACharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonneACharge>
 */
class PersonneAChargeFactory extends Factory
{
    private const LIENS = ['Enfant', 'Conjoint', 'Père', 'Mère'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lien = fake()->randomElement(self::LIENS);
        $estAdulte = in_array($lien, ['Conjoint', 'Père', 'Mère'], true);

        return [
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'date_naissance' => $estAdulte
                ? fake()->dateTimeBetween('-70 years', '-25 years')
                : fake()->dateTimeBetween('-17 years', '-1 month'),
            'lien_parente' => $lien,
            'date_adhesion' => now(),
            'statut' => StatutPersonneACharge::Active,
            'valide_par_gestionnaire' => false,
        ];
    }

    /**
     * Une personne à charge comptée comme cotisante (droit d'adhésion
     * réputé payé) — le nom de l'état est conservé pour ne pas casser les
     * tests existants, même si la validation manuelle par un gestionnaire
     * n'est plus le critère utilisé par estActiveEtValidee().
     */
    public function validee(): static
    {
        return $this->state(fn () => [
            'droit_adhesion_exonere' => true,
        ]);
    }
}
