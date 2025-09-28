<?php

namespace Database\Factories;

use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GigCost>
 */
class GigCostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GigCost::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gig_id' => Gig::factory(),
            'cost_center_id' => CostCenter::factory(),
            'description' => $this->faker->sentence(4),
            'value' => $this->faker->randomFloat(2, 50, 2000),
            'currency' => $this->faker->randomElement(['BRL', 'USD', 'EUR']),
            'expense_date' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'is_confirmed' => $this->faker->boolean(70), // 70% chance de estar confirmado
            'is_invoice' => $this->faker->boolean(30), // 30% chance de ser fatura
            'confirmed_by' => null, // Será definido quando is_confirmed for true
            'confirmed_at' => null, // Será definido quando is_confirmed for true
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the gig cost is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_confirmed' => true,
                'confirmed_by' => User::factory(),
                'confirmed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ];
        });
    }

    /**
     * Indicate that the gig cost is not confirmed.
     */
    public function unconfirmed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_confirmed' => false,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the gig cost is an invoice.
     */
    public function invoice(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_invoice' => true,
            ];
        });
    }

    /**
     * Set the currency to BRL.
     */
    public function brl(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'currency' => 'BRL',
            ];
        });
    }

    /**
     * Set a specific value.
     */
    public function withValue(float $value): static
    {
        return $this->state(function (array $attributes) use ($value) {
            return [
                'value' => $value,
            ];
        });
    }
}
