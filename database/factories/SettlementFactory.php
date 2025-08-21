<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Gera uma data de settlement entre 30 dias atrás e hoje
        $settlementDate = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'gig_id' => null, // Será definido quando usado
            'settlement_date' => Carbon::instance($settlementDate)->format('Y-m-d'),
            'artist_payment_proof' => $this->faker->optional(0.3)->url(),
            'booker_commission_proof' => $this->faker->optional(0.3)->url(),
            'notes' => $this->faker->optional(0.5)->paragraph(),
        ];
    }
}
