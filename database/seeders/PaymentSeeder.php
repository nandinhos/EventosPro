<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Gig;
use App\Models\GigCost;
use App\Models\CostCenter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Criando pagamentos e custos variados para eventos de música eletrônica...');
        
        $faker = Faker::create('pt_BR');
        
        // Buscar gigs existentes
        $gigs = Gig::all();
        $costCenterIds = CostCenter::pluck('id')->toArray();
        
        if ($gigs->isEmpty()) {
            $this->command->warn('Nenhuma gig encontrada. Execute o GigSeeder primeiro.');
            return;
        }
        
        if (empty($costCenterIds)) {
            $this->command->warn('Nenhum centro de custo encontrado. Execute o CostCenterSeeder primeiro.');
            return;
        }
        
        $paymentsCreated = 0;
        $costsCreated = 0;
        
        $paymentMethods = [
            'Transferência Bancária',
            'PIX',
            'Boleto Bancário',
            'Cartão de Crédito',
            'Dinheiro',
            'Cheque',
            'Remessa Internacional',
            'PayPal',
            'Wise (ex-TransferWise)',
            'Criptomoeda'
        ];
        
        $paymentDescriptions = [
            'Adiantamento (50%)',
            'Saldo Final (50%)',
            'Pagamento à Vista',
            'Primeira Parcela (30%)',
            'Segunda Parcela (40%)',
            'Terceira Parcela (30%)',
            'Pagamento Pós-Evento',
            'Cachê Principal',
            'Bonus por Performance',
            'Taxa de Cancelamento'
        ];
        
        foreach ($gigs as $gig) {
            // Determinar estrutura de pagamento baseada no valor do cachê
             $cacheValueBrl = $gig->cache_value;
            
            if ($cacheValueBrl <= 10000) {
                // Pagamento à vista ou 2 parcelas
                $numPayments = $faker->randomElement([1, 2]);
            } elseif ($cacheValueBrl <= 50000) {
                // 2 a 3 parcelas
                $numPayments = $faker->randomElement([2, 3]);
            } else {
                // 3 a 4 parcelas para valores altos
                $numPayments = $faker->randomElement([3, 4]);
            }
            
            // Criar pagamentos
            $totalPaid = 0;
            for ($i = 1; $i <= $numPayments; $i++) {
                if ($i == $numPayments) {
                    // Última parcela: valor restante
                    $paymentValue = $cacheValueBrl - $totalPaid;
                } else {
                    // Parcelas anteriores: percentual do total
                    $percentage = $faker->randomElement([0.3, 0.4, 0.5]);
                    $paymentValue = round($cacheValueBrl * $percentage, 2);
                }
                
                $totalPaid += $paymentValue;
                
                // Data de vencimento baseada na data do evento
                $gigDate = Carbon::parse($gig->gig_date);
                if ($i == 1) {
                    // Primeira parcela: 30-60 dias antes do evento
                    $dueDate = $gigDate->copy()->subDays($faker->numberBetween(30, 60));
                } elseif ($i == $numPayments) {
                    // Última parcela: 0-7 dias após o evento
                    $dueDate = $gigDate->copy()->addDays($faker->numberBetween(0, 7));
                } else {
                    // Parcelas intermediárias: distribuídas entre primeira e última
                    $daysBeforeEvent = $faker->numberBetween(7, 29);
                    $dueDate = $gigDate->copy()->subDays($daysBeforeEvent);
                }
                
                // Status do pagamento baseado na data
                $isOverdue = $dueDate->isPast();
                $isPaid = $isOverdue ? $faker->boolean(70) : $faker->boolean(30);
                
                $payment = Payment::create([
                     'gig_id' => $gig->id,
                     'description' => $faker->randomElement($paymentDescriptions),
                     'due_value' => $paymentValue,
                     'due_date' => $dueDate,
                     'currency' => $gig->currency,
                     'exchange_rate' => $gig->currency !== 'BRL' ? $faker->randomFloat(4, 4.5, 6.5) : 1.0,
                     'received_value_actual' => $isPaid ? $paymentValue : null,
                     'received_date_actual' => $isPaid ? $dueDate->copy()->addDays($faker->numberBetween(-2, 5)) : null,
                     'confirmed_at' => $isPaid ? $faker->dateTimeBetween($dueDate->isPast() ? $dueDate : 'now', 'now') : null,
                     'confirmed_by' => $isPaid ? 1 : null, // Assumindo user ID 1
                     'notes' => $isPaid ? 'Pago via ' . $faker->randomElement($paymentMethods) : null,
                 ]);
                
                $paymentsCreated++;
            }
            
            // Criar custos para cada gig
            $numCosts = $faker->numberBetween(2, 6);
            
            for ($i = 0; $i < $numCosts; $i++) {
                $costValue = $faker->randomFloat(2, 200, 8000);
                $expenseDate = $faker->dateTimeBetween(
                    Carbon::parse($gig->gig_date)->subDays(30),
                    Carbon::parse($gig->gig_date)->addDays(7)
                );
                
                $isConfirmed = $faker->boolean(60);
                
                $cost = GigCost::create([
                    'gig_id' => $gig->id,
                    'cost_center_id' => $faker->randomElement($costCenterIds),
                    'description' => $faker->optional(0.7)->sentence(),
                    'value' => $costValue,
                    'currency' => 'BRL',
                    'expense_date' => $expenseDate,
                    'is_confirmed' => $isConfirmed,
                    'confirmed_by' => $isConfirmed ? 1 : null,
                    'confirmed_at' => $isConfirmed ? $faker->dateTimeBetween(Carbon::parse($expenseDate)->isPast() ? $expenseDate : 'now', 'now') : null,
                    'notes' => $faker->optional(0.4)->sentence(),
                ]);
                
                $costsCreated++;
            }
        }
        
        $this->command->info("$paymentsCreated pagamentos criados com estruturas variadas!");
        $this->command->info("$costsCreated custos de gig criados!");
        $this->command->info('Total de pagamentos no sistema: ' . Payment::count());
        $this->command->info('Total de custos no sistema: ' . GigCost::count());
    }
}