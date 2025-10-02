<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LargeDatasetSeeder extends Seeder
{
    /**
     * Seeder para criar um grande volume de dados para teste de performance da auditoria
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando dataset grande para teste de performance...');

        try {
            // Verificar conexão
            DB::connection()->getPdo();
            $this->command->info('✅ Conexão com banco estabelecida.');
        } catch (\Exception $e) {
            $this->command->error('❌ Erro de conexão com banco: '.$e->getMessage());

            return;
        }

        // Limpar dados anteriores de teste de performance
        $this->cleanupPreviousData();

        // Obter artistas e bookers existentes
        $artists = Artist::pluck('id')->toArray();
        $bookers = Booker::pluck('id')->toArray();

        if (empty($artists) || empty($bookers)) {
            $this->command->error('❌ Não há artistas ou bookers suficientes. Execute as seeders básicas primeiro.');

            return;
        }

        $venues = [
            'Allianz Parque', 'Arena Corinthians', 'Estádio do Maracanã', 'Arena da Baixada',
            'Mineirão', 'Arena Fonte Nova', 'Estádio Beira-Rio', 'Arena Grêmio',
            'Clube Hípico', 'Green Valley', 'Warung Beach Club', 'D-Edge',
            'Festa Ploc', 'Universo Paralello', 'Rock in Rio', 'Lollapalooza',
            'Villa Mix', 'Festa da Uva', 'Oktoberfest', 'Carnaval Salvador',
        ];

        $contractStatuses = ['ativo', 'cancelado', 'suspenso'];
        $paymentStatuses = ['pendente', 'pago', 'atrasado'];
        $currencies = ['BRL', 'USD', 'EUR'];

        $batchSize = 100;
        $totalRecords = 1000; // Criar 1000 registros para teste
        $batches = ceil($totalRecords / $batchSize);

        $this->command->info("📊 Criando {$totalRecords} registros em {$batches} lotes de {$batchSize}...");

        $progressBar = $this->command->getOutput()->createProgressBar($totalRecords);
        $progressBar->start();

        $createdCount = 0;

        for ($batch = 0; $batch < $batches; $batch++) {
            $gigsData = [];
            $currentBatchSize = min($batchSize, $totalRecords - ($batch * $batchSize));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $contractDate = Carbon::now()->subDays(rand(1, 365));
                $gigDate = $contractDate->copy()->addDays(rand(1, 180));
                $cacheValue = rand(5000, 100000);
                $currency = $currencies[array_rand($currencies)];

                // Criar alguns registros com problemas intencionais para teste
                $hasIssues = rand(1, 10) <= 3; // 30% chance de ter problemas

                if ($hasIssues) {
                    // Introduzir problemas aleatórios
                    $issueType = rand(1, 4);
                    switch ($issueType) {
                        case 1: // Cache value zerado
                            $cacheValue = 0;
                            break;
                        case 2: // Data futura com status pago
                            $gigDate = Carbon::now()->addDays(rand(1, 90));
                            $paymentStatus = 'pago';
                            break;
                        case 3: // Currency vazia
                            $currency = '';
                            break;
                        case 4: // Data de contrato posterior à gig
                            $contractDate = $gigDate->copy()->addDays(rand(1, 30));
                            break;
                    }
                }

                $gigsData[] = [
                    'contract_number' => 'PERF-'.str_pad($batch * $batchSize + $i + 1, 6, '0', STR_PAD_LEFT),
                    'artist_id' => $artists[array_rand($artists)],
                    'booker_id' => $bookers[array_rand($bookers)],
                    'contract_date' => $contractDate->format('Y-m-d'),
                    'gig_date' => $gigDate->format('Y-m-d'),
                    'location_event_details' => $venues[array_rand($venues)].' - São Paulo, SP',
                    'cache_value' => $cacheValue,
                    'currency' => $currency,
                    'agency_commission_type' => 'percent',
                    'agency_commission_rate' => rand(10, 30),
                    'agency_commission_value' => $cacheValue * (rand(10, 30) / 100),
                    'booker_commission_type' => 'percent',
                    'booker_commission_rate' => rand(5, 15),
                    'booker_commission_value' => $cacheValue * (rand(5, 15) / 100),
                    'liquid_commission_value' => $cacheValue * (rand(5, 20) / 100),
                    'contract_status' => $contractStatuses[array_rand($contractStatuses)],
                    'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                    'artist_payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                    'booker_payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Inserir lote
            Gig::insert($gigsData);
            $createdCount += count($gigsData);
            $progressBar->advance(count($gigsData));

            // Log do progresso
            if (($batch + 1) % 5 === 0) {
                Log::info('LargeDatasetSeeder: Lote '.($batch + 1)." de {$batches} criado", [
                    'records_in_batch' => count($gigsData),
                    'total_created' => $createdCount,
                    'memory_usage' => memory_get_usage(true),
                ]);
            }

            // Liberar memória
            unset($gigsData);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $progressBar->finish();
        $this->command->newLine();

        // Criar alguns pagamentos para os registros criados
        $this->createSamplePayments();

        $this->command->info('✅ Dataset de performance criado com sucesso!');
        $this->command->info("📊 Total de registros criados: {$createdCount}");
        $this->command->info('🔍 Execute a auditoria para testar:');
        $this->command->info('   ./vendor/bin/sail artisan gig:audit-data --scan-only --full-database');

        Log::info('LargeDatasetSeeder: Seeder concluída', [
            'total_records_created' => $createdCount,
            'memory_peak' => memory_get_peak_usage(true),
        ]);
    }

    private function cleanupPreviousData(): void
    {
        $deletedCount = Gig::where('contract_number', 'LIKE', 'PERF-%')->delete();

        if ($deletedCount > 0) {
            $this->command->info("🧹 Removidos {$deletedCount} registros de teste anteriores.");
        }
    }

    private function createSamplePayments(): void
    {
        $this->command->info('💰 Criando pagamentos de exemplo...');

        // Pegar algumas gigs criadas para adicionar pagamentos
        $gigs = Gig::where('contract_number', 'LIKE', 'PERF-%')
            ->limit(200)
            ->get();

        $paymentsData = [];

        foreach ($gigs as $gig) {
            // 70% chance de ter pelo menos um pagamento
            if (rand(1, 10) <= 7) {
                $numPayments = rand(1, 3);
                $totalValue = $gig->cache_value;

                for ($i = 0; $i < $numPayments; $i++) {
                    $value = $i === $numPayments - 1
                        ? $totalValue // Último pagamento pega o resto
                        : rand(1000, $totalValue / 2);

                    $totalValue -= $value;

                    if ($value <= 0) {
                        break;
                    }

                    $paymentsData[] = [
                        'gig_id' => $gig->id,
                        'description' => 'Pagamento '.($i + 1).' - '.$gig->contract_number,
                        'due_value' => $value,
                        'due_date' => Carbon::parse($gig->gig_date)->addDays(rand(-30, 30)),
                        'currency' => $gig->currency,
                        'received_value_actual' => rand(1, 10) <= 8 ? $value : $value * 0.9, // 80% chance de valor correto
                        'received_date_actual' => rand(1, 10) <= 7 ? Carbon::parse($gig->gig_date)->addDays(rand(-15, 45)) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (! empty($paymentsData)) {
            // Inserir em lotes para evitar problemas de memória
            $chunks = array_chunk($paymentsData, 100);
            foreach ($chunks as $chunk) {
                Payment::insert($chunk);
            }

            $this->command->info('💰 Criados '.count($paymentsData).' pagamentos de exemplo.');
        }
    }
}
