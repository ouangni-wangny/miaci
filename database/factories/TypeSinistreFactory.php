<?php

namespace Database\Factories;

use App\Models\TypeSinistre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TypeSinistre>
 */
class TypeSinistreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('TYPE-????'),
            'libelle' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'plafond_montant' => fake()->numberBetween(50000, 500000),
            'delai_carence_mois' => fake()->randomElement([0, 3, 6, 12]),
            'cotisation_a_jour_requise' => true,
            'actif' => true,
        ];
    }
}
