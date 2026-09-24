<?php

namespace Database\Factories;

use App\Enums\FrequenceCotisation;
use App\Models\ParametreCotisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParametreCotisation>
 */
class ParametreCotisationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'montant' => 1000,
            'frequence' => FrequenceCotisation::Mensuelle,
            'date_debut' => now()->subYears(2)->startOfYear(),
            'actif' => true,
        ];
    }
}
