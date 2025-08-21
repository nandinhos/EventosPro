<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gig>
 */
class GigFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'contract_number' => $this->faker->unique()->numerify('CONT-####'),
            'contract_date' => $this->faker->date(),
            'gig_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'location_event_details' => $this->faker->address,
            'cache_value' => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => $this->faker->randomElement(['BRL', 'USD', 'EUR']),
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => $this->faker->randomFloat(2, 10, 30),
            'agency_commission_value' => null, // Será calculado pelo observer
            'booker_commission_type' => 'percent',
            'booker_commission_rate' => $this->faker->randomFloat(2, 5, 15),
            'booker_commission_value' => null, // Será calculado pelo observer
            'liquid_commission_value' => null, // Será calculado pelo observer
            'payment_status' => $this->faker->randomElement(['a_vencer', 'vencido', 'pago']),
            'artist_payment_status' => $this->faker->randomElement(['pendente', 'pago']),
            'booker_payment_status' => $this->faker->randomElement(['pendente', 'pago']),
            'contract_status' => $this->faker->randomElement(['ativo', 'cancelado', 'concluido']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}