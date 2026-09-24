<?php

namespace Database\Factories;

use App\Enums\ModePaiement;
use App\Models\DroitAdhesion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DroitAdhesion>
 */
class DroitAdhesionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'montant' => 11000,
            'date_paiement' => now(),
            'mode_paiement' => ModePaiement::Especes,
        ];
    }
}
