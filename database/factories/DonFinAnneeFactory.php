<?php

namespace Database\Factories;

use App\Models\DonFinAnnee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonFinAnnee>
 */
class DonFinAnneeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'annee' => now()->year,
            'montant' => 10000,
            'date_versement' => now()->year.'-12-20',
        ];
    }
}
