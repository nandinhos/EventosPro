<?php

namespace Database\Factories;

use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CostCenter>
 */
class CostCenterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CostCenter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true).' '.$this->faker->randomNumber(3),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'color' => $this->faker->optional(0.3)->hexColor(), // 30% chance of having a color
        ];
    }
}
