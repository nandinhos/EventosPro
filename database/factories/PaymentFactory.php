<?php

namespace Database\Factories;

use App\Models\Gig;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = Carbon::instance($this->faker->dateTimeBetween('-3 months', '+6 months'));
        $isPaid = $this->faker->boolean(70);

        $receivedDate = null;
        if ($isPaid) {
            // Garante que a data de recebimento seja posterior à data de vencimento
            $startDate = $dueDate->copy()->addDay();
            $endDate = now();

            // Se a data de vencimento for no futuro, usa 'now' como data de recebimento
            if ($startDate->greaterThan($endDate)) {
                $receivedDate = now();
            } else {
                $receivedDate = $this->faker->dateTimeBetween($startDate, $endDate);
            }
        }

        return [
            'gig_id' => Gig::factory(),
            'description' => $this->faker->randomElement(['Entrada 50%', 'Saldo Final', 'Parcela Única', 'Cachê']),
            'due_value' => $this->faker->randomFloat(2, 500, 50000),
            'due_date' => $dueDate->format('Y-m-d'),
            'currency' => $this->faker->randomElement(['BRL', 'USD', 'EUR']),
            'exchange_rate' => $this->faker->optional()->randomFloat(6, 0.1, 10),
            'received_value_actual' => $isPaid ? $this->faker->randomFloat(2, 500, 50000) : null,
            'received_date_actual' => $receivedDate?->format('Y-m-d'),
            'confirmed_at' => $isPaid ? $receivedDate?->format('Y-m-d H:i:s') : null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
