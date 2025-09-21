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
        $gigDate = $this->faker->dateTimeBetween('-1 year', '+1 year');
        $contractDate = $this->faker->dateTimeBetween('-2 years', $gigDate);
        
        // Determinar status de pagamento baseado na data do evento
        $isPastEvent = $gigDate < now();
        $artistPaymentStatus = $isPastEvent ? 'pago' : 'pendente';
        $bookerPaymentStatus = $isPastEvent ? 'pago' : 'pendente';

        return [
            'artist_id' => Artist::factory(),
            'booker_id' => Booker::factory(),
            'contract_number' => $this->faker->unique()->numerify('CONT-####'),
            'contract_date' => $contractDate,
            'gig_date' => $gigDate,
            'location_event_details' => $this->faker->sentence(10),
            'cache_value' => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => 'BRL',
            'agency_commission_type' => 'percent',
            'agency_commission_rate' => 20.00,
            'agency_commission_value' => $this->faker->randomFloat(2, 200, 10000),
            'booker_commission_type' => 'percent',
            'booker_commission_rate' => 5.00,
            'booker_commission_value' => $this->faker->randomFloat(2, 50, 2500),
            'liquid_commission_value' => $this->faker->randomFloat(2, 150, 7500),
            'contract_status' => 'n/a', // Campo obrigatório
            'payment_status' => $this->faker->randomElement(['a_vencer', 'vencido', 'pago']),
            'artist_payment_status' => $artistPaymentStatus,
            'booker_payment_status' => $bookerPaymentStatus,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}