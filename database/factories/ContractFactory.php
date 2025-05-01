<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contractDate = $this->faker->dateTimeBetween('-1 year', 'now');

        return [
            // Não precisa mais de booker/artist/location aqui, eles estão no Evento
            'contract_number' => 'CT-' . $this->faker->unique()->randomNumber(6), // Prefixo CT
            'contract_date' => $contractDate->format('Y-m-d'),
            'value' => $this->faker->randomFloat(2, 1000, 50000), // Valor entre 1k e 50k
            'currency' => $this->faker->randomElement(['BRL']), // Começar só com BRL
            'status' => $this->faker->randomElement(['em vigor', 'pendente', 'concluido', 'cancelado']),
            'file_path' => null, // Arquivos depois
        ];
    }
}