<?php

namespace Database\Factories;

use App\Models\Contract; // Importar Contract
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon; // Importar Carbon para manipulação de datas

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
        // Garante que existe um contrato para vincular
        $contract = Contract::inRandomOrder()->first() ?? Contract::factory()->create();

        $dueDate = Carbon::instance($this->faker->dateTimeBetween('-3 months', '+6 months')); // Vence no passado ou futuro (como Carbon)
        $isPaid = $this->faker->boolean(70); // 70% chance de estar pago

        // --- Lógica CORRIGIDA para $paidDate ---
        $paidDate = null;
        if ($isPaid) {
            // Data de pagamento deve ser ANTES de 'agora'.
            // Vamos gerar entre a data do contrato (ou max 6 meses atrás) e 'agora'.
            $possibleStartDate = Carbon::parse($contract->contract_date ?? '-6 months'); // Usa data do contrato ou 6 meses atrás
            $endDate = now();

            // Garante que a data de início não seja depois da data de fim
            if ($possibleStartDate->greaterThanOrEqualTo($endDate)) {
                $possibleStartDate = $endDate->copy()->subDay(); // Se data do contrato for hoje/futuro, começa ontem
            }
            $paidDate = $this->faker->dateTimeBetween($possibleStartDate, $endDate);
        }
        // --- Fim da Lógica CORRIGIDA ---

        $status = 'pendente';
        if ($isPaid) {
            $status = 'pago';
        } elseif ($dueDate->isPast() && !$isPaid) { // Usar Carbon para checar se está no passado
            $status = 'atrasado';
        }

        return [
            'contract_id' => $contract->id,
            'description' => $this->faker->randomElement(['Entrada 50%', 'Saldo Final', 'Parcela Única', 'Cachê']),
            'value' => $this->faker->randomFloat(2, 500, max(500, $contract->value * 0.6)), // Garante valor mínimo e < contrato
            'due_date' => $dueDate->format('Y-m-d'),
            'paid_at' => $paidDate?->format('Y-m-d H:i:s'), // Usar operador nullsafe
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}