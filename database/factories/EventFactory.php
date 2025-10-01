<?php

namespace Database\Factories;

use App\Models\Artist; // Importar modelos
use App\Models\Booker;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pega IDs válidos de bookers e artistas criados pelos seeders
        $bookerId = Booker::inRandomOrder()->first()->id;
        $mainArtistId = Artist::inRandomOrder()->first()->id;

        // Opcional: Cria ou pega um contrato para associar (ex: 50% de chance)
        $contractId = $this->faker->optional(0.5)->randomElement(
            Contract::pluck('id')->toArray() ?? [Contract::factory()->create()->id] // Pega ID existente ou cria um novo
        );

        $eventDate = $this->faker->dateTimeBetween('now', '+1 year');

        return [
            'booker_id' => $bookerId,
            'main_artist_id' => $mainArtistId,
            'contract_id' => $contractId,
            'name' => $this->faker->optional()->sentence(3), // Nome opcional
            'location_text' => $this->faker->city().' - '.$this->faker->stateAbbr(), // Local como texto
            'event_date' => $eventDate->format('Y-m-d'),
            'event_time' => $this->faker->optional()->randomElement(['18:00:00', '20:00:00', '22:00:00']),
            'type' => $this->faker->randomElement(['Show', 'Festival', 'Festa Corporativa', 'Club Night']),
            'status' => $this->faker->randomElement(['planejado', 'confirmado', 'realizado', 'cancelado']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
