<?php

namespace Database\Factories;

use App\Models\Event; // Importar Event
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory; // Importar Carbon

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Garante que existe um evento REALIZADO para vincular
        $event = Event::where('status', 'realizado')->inRandomOrder()->first()
                 ?? Event::factory()->create(['status' => 'realizado']);

        // Pega o contrato associado, se houver, para basear valores
        $contractValue = $event->contract?->value ?? $this->faker->randomFloat(2, 5000, 50000);
        $bookerCommissionRate = 5.00; // Simplificando, pode buscar do booker se existir o campo

        $artistNetAmount = $contractValue * (1 - ($bookerCommissionRate / 100));
        $bookerCommission = $contractValue * ($bookerCommissionRate / 100);

        // --- Lógica CORRIGIDA para settlement_date ---
        $eventDateCarbon = Carbon::parse($event->event_date); // Converte data do evento para Carbon
        $endDate = now(); // Data final é agora

        // Garante que a data de início (data do evento) seja anterior à data de fim (agora)
        // Se a data do evento for hoje ou no futuro, define a data de início como ontem
        $startDate = $eventDateCarbon->copy(); // Copia a data do evento
        if ($startDate->greaterThanOrEqualTo($endDate)) {
            $startDate = $endDate->copy()->subDay(); // Se evento é hoje/futuro, começa ontem
        }

        // Gera a data de acerto entre a data do evento (ou ontem) e agora
        $settlementDate = Carbon::instance($this->faker->dateTimeBetween($startDate, $endDate));
        // --- Fim da Lógica CORRIGIDA ---

        return [
            'event_id' => $event->id,
            'settlement_date' => $settlementDate->format('Y-m-d'), // Usa a data corrigida
            'artist_net_amount' => $this->faker->optional(0.9)->randomFloat(2, $artistNetAmount * 0.8, $artistNetAmount * 1.2),
            'agency_commission' => null,
            'booker_commission' => $this->faker->optional(0.9)->randomFloat(2, $bookerCommission * 0.8, $bookerCommission * 1.2),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
