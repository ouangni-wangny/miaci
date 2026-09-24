<?php

namespace Database\Factories;

use App\Enums\BeneficiaireType;
use App\Enums\StatutDemandeSinistre;
use App\Models\Adherent;
use App\Models\DemandeSinistre;
use App\Models\TypeSinistre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeSinistre>
 */
class DemandeSinistreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'adherent_id' => Adherent::factory(),
            'type_sinistre_id' => TypeSinistre::factory(),
            'beneficiaire_type' => BeneficiaireType::Adherent,
            'description' => fake()->paragraph(),
            'montant_demande' => fake()->numberBetween(20000, 200000),
            'date_evenement' => fake()->dateTimeBetween('-2 months', 'now'),
            'statut' => StatutDemandeSinistre::Soumise,
        ];
    }
}
