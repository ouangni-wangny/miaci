<?php

namespace Database\Factories;

use App\Enums\ModePaiement;
use App\Enums\StatutCotisation;
use App\Models\Cotisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cotisation>
 */
class CotisationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $periodeDebut = fake()->dateTimeBetween('-6 months', 'now')->modify('first day of this month');

        return [
            'montant' => 1000,
            'date_paiement' => $periodeDebut,
            'periode_debut' => $periodeDebut,
            'periode_fin' => (clone $periodeDebut)->modify('last day of this month'),
            'mode_paiement' => fake()->randomElement(ModePaiement::cases()),
            'reference' => null,
            'statut' => StatutCotisation::Valide,
        ];
    }
}
