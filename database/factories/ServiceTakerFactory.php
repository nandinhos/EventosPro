<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceTaker>
 */
class ServiceTakerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization' => fake()->company(),
            'document' => fake()->numerify('##.###.###/####-##'),
            'street' => fake()->streetAddress(),
            'postal_code' => fake()->numerify('#####-###'),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'country' => 'Brasil',
            'company_phone' => fake()->phoneNumber(),
            'contact' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
