<?php

namespace Database\Factories;

use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgencyFixedCost>
 */
class AgencyFixedCostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $referenceMonth = fake()->dateTimeBetween('-6 months', '+6 months')->format('Y-m-01');
        $dueDate = fake()->dateTimeBetween($referenceMonth, '+1 month')->format('Y-m-d');

        return [
            'description' => fake()->randomElement([
                'Aluguel do Escritório',
                'Energia Elétrica',
                'Internet e Telefonia',
                'Salários Administrativos',
                'Marketing Digital',
                'Software e Licenças',
                'Manutenção de Equipamentos',
            ]),
            'monthly_value' => fake()->randomFloat(2, 500, 5000),
            'reference_month' => $referenceMonth,
            'due_date' => $dueDate,
            'cost_type' => fake()->randomElement(['operacional', 'administrativo']),
            'cost_center_id' => CostCenter::factory(),
            'notes' => fake()->optional(0.3)->sentence(),
            'is_active' => fake()->boolean(90), // 90% ativos
        ];
    }

    /**
     * Indicate that the cost is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the cost is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the cost is operational (GIG).
     */
    public function operational(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'operacional',
        ]);
    }

    /**
     * Indicate that the cost is administrative (AGENCY).
     */
    public function administrative(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_type' => 'administrativo',
        ]);
    }
}
