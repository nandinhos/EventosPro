<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booker>
 */
class BookerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'default_commission_rate' => $this->faker->randomFloat(2, 5.0, 25.0),
            'contact_info' => $this->faker->email(),
        ];
    }
}
