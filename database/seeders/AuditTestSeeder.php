<?php

namespace Database\Seeders;

use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditTestSeeder extends Seeder
{
    public function run()
    {
        echo "🔍 Criando dados de teste para auditoria...\n";

        try {
            // Verificar conexão com banco
            DB::connection()->getPdo();
            echo "✅ Conexão com banco estabelecida.\n";
        } catch (\Exception $e) {
            echo '❌ Erro de conexão com banco: '.$e->getMessage()."\n";
            echo "💡 Certifique-se de que o banco está rodando (ex: ./vendor/bin/sail up)\n";

            return;
        }

        // Limpar dados de teste anteriores (opcional)
        $this->cleanupPreviousTestData();

        // Obter IDs dos artistas e bookers existentes
        $artistIds = Artist::pluck('id')->toArray();
        $bookerIds = Booker::pluck('id')->toArray();

        if (empty($artistIds) || empty($bookerIds)) {
            echo "❌ Erro: É necessário ter artistas e bookers cadastrados antes de criar gigs de teste.\n";

            return;
        }

        $testCases = [
            // === CASOS DE DISCREPÂNCIAS FINANCEIRAS ===
            $this->createDiscrepancyCase1($artistIds, $bookerIds), // Falta lançamento
            $this->createDiscrepancyCase2($artistIds, $bookerIds), // Divergência - falta dinheiro
            $this->createDiscrepancyCase3($artistIds, $bookerIds), // Divergência - excesso
            $this->createDiscrepancyCase4($artistIds, $bookerIds), // Parcelas não batem com contrato

            // === CASOS DE STATUS INCONSISTENTES ===
            $this->createStatusInconsistencyCase1($artistIds, $bookerIds), // Pago com parcelas abertas
            $this->createStatusInconsistencyCase2($artistIds, $bookerIds), // Evento passado não pago
            $this->createStatusInconsistencyCase3($artistIds, $bookerIds), // Status artista/booker inconsistente

            // === CASOS DE CAMPOS OBRIGATÓRIOS ===
            $this->createMissingFieldsCase1($artistIds, $bookerIds), // Cache value zero
            $this->createMissingFieldsCase2($artistIds, $bookerIds), // Currency vazia
            $this->createMissingFieldsCase3($artistIds, $bookerIds), // Gig date nula

            // === CASOS DE DATAS INVÁLIDAS ===
            $this->createInvalidDateCase1($artistIds, $bookerIds), // Contract date > gig date
            $this->createInvalidDateCase2($artistIds, $bookerIds), // Evento vencido com status incorreto

            // === CASOS CORRETOS (PARA VALIDAÇÃO) ===
            $this->createCorrectCase1($artistIds, $bookerIds), // Totalmente correto
            $this->createCorrectCase2($artistIds, $bookerIds), // Pago corretamente
            $this->createCorrectCase3($artistIds, $bookerIds), // Futuro com parciais
        ];

        $createdCount = 0;
        foreach ($testCases as $index => $testCase) {
            try {
                DB::beginTransaction();

                $gig = Gig::create($testCase['gig']);

                // Criar pagamentos se especificados
                if (isset($testCase['payments'])) {
                    foreach ($testCase['payments'] as $paymentData) {
                        $paymentData['gig_id'] = $gig->id;
                        Payment::create($paymentData);
                    }
                }

                DB::commit();
                $createdCount++;
                echo '✅ Caso de teste '.($index + 1)." criado: {$testCase['description']}\n";

            } catch (\Exception $e) {
                DB::rollBack();
                echo '❌ Erro ao criar caso '.($index + 1).': '.$e->getMessage()."\n";
            }
        }

        echo "\n🎯 Total de casos de teste criados: $createdCount\n";
        echo "📋 Execute a auditoria para verificar os casos:\n";
        echo "   php artisan gig:audit-data --scan-only\n";
        echo "   ou acesse /audit/data-audit na interface web\n\n";
    }

    private function cleanupPreviousTestData()
    {
        try {
            // Remove gigs de teste anteriores (identificados por contract_number começando com TEST-)
            $testGigs = Gig::where('contract_number', 'LIKE', 'TEST-%')->get();
            $deletedCount = 0;

            foreach ($testGigs as $gig) {
                $gig->payments()->delete();
                $gig->delete();
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                echo "🧹 Removidos $deletedCount gigs de teste anteriores.\n";
            }
        } catch (\Exception $e) {
            echo '⚠️  Aviso: Erro ao limpar dados anteriores: '.$e->getMessage()."\n";
        }
    }

    // === CASOS DE DISCREPÂNCIAS FINANCEIRAS ===

    private function createDiscrepancyCase1($artistIds, $bookerIds)
    {
        return [
            'description' => 'Falta lançamento - Gig sem pagamentos',
            'gig' => [
                'artist_id' => $artistIds[0],
                'booker_id' => $bookerIds[0],
                'contract_number' => 'TEST-DISC-001',
                'contract_date' => Carbon::now()->subDays(30),
                'gig_date' => Carbon::now()->addDays(15),
                'location_event_details' => 'Festival Teste - São Paulo/SP',
                'cache_value' => 50000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [], // Sem pagamentos - vai gerar "falta_lancamento"
        ];
    }

    private function createDiscrepancyCase2($artistIds, $bookerIds)
    {
        return [
            'description' => 'Divergência - Falta R$ 10.000',
            'gig' => [
                'artist_id' => $artistIds[1] ?? $artistIds[0],
                'booker_id' => $bookerIds[1] ?? $bookerIds[0],
                'contract_number' => 'TEST-DISC-002',
                'contract_date' => Carbon::now()->subDays(45),
                'gig_date' => Carbon::now()->addDays(30),
                'location_event_details' => 'Club Teste - Rio de Janeiro/RJ',
                'cache_value' => 80000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'parcial',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Primeira parcela',
                    'due_value' => 40000.00,
                    'due_date' => Carbon::now()->addDays(10),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
                [
                    'description' => 'Segunda parcela',
                    'due_value' => 30000.00, // Total: 70k, falta 10k
                    'due_date' => Carbon::now()->addDays(25),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
            ],
        ];
    }

    private function createDiscrepancyCase3($artistIds, $bookerIds)
    {
        return [
            'description' => 'Divergência - Excesso de R$ 5.000',
            'gig' => [
                'artist_id' => $artistIds[2] ?? $artistIds[0],
                'booker_id' => $bookerIds[2] ?? $bookerIds[0],
                'contract_number' => 'TEST-DISC-003',
                'contract_date' => Carbon::now()->subDays(20),
                'gig_date' => Carbon::now()->addDays(45),
                'location_event_details' => 'Arena Teste - Belo Horizonte/MG',
                'cache_value' => 60000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Pagamento único',
                    'due_value' => 65000.00, // Excesso de 5k
                    'due_date' => Carbon::now()->addDays(20),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
            ],
        ];
    }

    private function createDiscrepancyCase4($artistIds, $bookerIds)
    {
        return [
            'description' => 'Parcelas em USD não batem com contrato BRL',
            'gig' => [
                'artist_id' => $artistIds[3] ?? $artistIds[0],
                'booker_id' => $bookerIds[3] ?? $bookerIds[0],
                'contract_number' => 'TEST-DISC-004',
                'contract_date' => Carbon::now()->subDays(60),
                'gig_date' => Carbon::now()->addDays(20),
                'location_event_details' => 'Festival Internacional - São Paulo/SP',
                'cache_value' => 100000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Pagamento em USD',
                    'due_value' => 15000.00, // USD - com taxa errada vai dar divergência
                    'due_date' => Carbon::now()->addDays(15),
                    'currency' => 'USD',
                    'exchange_rate' => 5.0, // Taxa incorreta
                ],
            ],
        ];
    }

    // === CASOS DE STATUS INCONSISTENTES ===

    private function createStatusInconsistencyCase1($artistIds, $bookerIds)
    {
        return [
            'description' => 'Status "pago" mas tem parcelas não confirmadas',
            'gig' => [
                'artist_id' => $artistIds[0],
                'booker_id' => $bookerIds[0],
                'contract_number' => 'TEST-STATUS-001',
                'contract_date' => Carbon::now()->subDays(90),
                'gig_date' => Carbon::now()->subDays(10), // Evento já passou
                'location_event_details' => 'Show Teste - Curitiba/PR',
                'cache_value' => 40000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'concluido',
                'payment_status' => 'pago', // Status incorreto
                'artist_payment_status' => 'pago',
                'booker_payment_status' => 'pago',
            ],
            'payments' => [
                [
                    'description' => 'Primeira parcela - confirmada',
                    'due_value' => 20000.00,
                    'due_date' => Carbon::now()->subDays(30),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                    'received_value_actual' => 20000.00,
                    'received_date_actual' => Carbon::now()->subDays(25),
                    'confirmed_at' => Carbon::now()->subDays(25),
                ],
                [
                    'description' => 'Segunda parcela - NÃO confirmada',
                    'due_value' => 20000.00,
                    'due_date' => Carbon::now()->subDays(5),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                    // Sem confirmed_at - vai gerar inconsistência
                ],
            ],
        ];
    }

    private function createStatusInconsistencyCase2($artistIds, $bookerIds)
    {
        return [
            'description' => 'Evento passado mas não marcado como pago',
            'gig' => [
                'artist_id' => $artistIds[1] ?? $artistIds[0],
                'booker_id' => $bookerIds[1] ?? $bookerIds[0],
                'contract_number' => 'TEST-STATUS-002',
                'contract_date' => Carbon::now()->subDays(120),
                'gig_date' => Carbon::now()->subDays(30), // Evento já passou
                'location_event_details' => 'Festival Passado - Salvador/BA',
                'cache_value' => 70000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'concluido',
                'payment_status' => 'a_vencer', // Status incorreto para evento passado
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Pagamento total confirmado',
                    'due_value' => 70000.00,
                    'due_date' => Carbon::now()->subDays(35),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                    'received_value_actual' => 70000.00,
                    'received_date_actual' => Carbon::now()->subDays(32),
                    'confirmed_at' => Carbon::now()->subDays(32),
                ],
            ],
        ];
    }

    private function createStatusInconsistencyCase3($artistIds, $bookerIds)
    {
        return [
            'description' => 'Status artista/booker inconsistente com pagamentos',
            'gig' => [
                'artist_id' => $artistIds[2] ?? $artistIds[0],
                'booker_id' => $bookerIds[2] ?? $bookerIds[0],
                'contract_number' => 'TEST-STATUS-003',
                'contract_date' => Carbon::now()->subDays(75),
                'gig_date' => Carbon::now()->subDays(15),
                'location_event_details' => 'Casa de Shows - Fortaleza/CE',
                'cache_value' => 35000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'concluido',
                'payment_status' => 'pago',
                'artist_payment_status' => 'pago', // Inconsistente
                'booker_payment_status' => 'pendente', // Inconsistente
            ],
            'payments' => [
                [
                    'description' => 'Pagamento completo',
                    'due_value' => 35000.00,
                    'due_date' => Carbon::now()->subDays(20),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                    'received_value_actual' => 35000.00,
                    'received_date_actual' => Carbon::now()->subDays(18),
                    'confirmed_at' => Carbon::now()->subDays(18),
                ],
            ],
        ];
    }

    // === CASOS DE CAMPOS OBRIGATÓRIOS ===

    private function createMissingFieldsCase1($artistIds, $bookerIds)
    {
        return [
            'description' => 'Cache value zerado',
            'gig' => [
                'artist_id' => $artistIds[0],
                'booker_id' => $bookerIds[0],
                'contract_number' => 'TEST-FIELDS-001',
                'contract_date' => Carbon::now()->subDays(15),
                'gig_date' => Carbon::now()->addDays(60),
                'location_event_details' => 'Evento Gratuito - Brasília/DF',
                'cache_value' => 0.00, // Valor inválido
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [],
        ];
    }

    private function createMissingFieldsCase2($artistIds, $bookerIds)
    {
        return [
            'description' => 'Currency vazia',
            'gig' => [
                'artist_id' => $artistIds[1] ?? $artistIds[0],
                'booker_id' => $bookerIds[1] ?? $bookerIds[0],
                'contract_number' => 'TEST-FIELDS-002',
                'contract_date' => Carbon::now()->subDays(25),
                'gig_date' => Carbon::now()->addDays(40),
                'location_event_details' => 'Show Internacional - São Paulo/SP',
                'cache_value' => 25000.00,
                'currency' => '', // Currency vazia
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [],
        ];
    }

    private function createMissingFieldsCase3($artistIds, $bookerIds)
    {
        return [
            'description' => 'Gig date muito no passado (data suspeita)',
            'gig' => [
                'artist_id' => $artistIds[2] ?? $artistIds[0],
                'booker_id' => $bookerIds[2] ?? $bookerIds[0],
                'contract_number' => 'TEST-FIELDS-003',
                'contract_date' => Carbon::now()->subDays(10),
                'gig_date' => Carbon::parse('1900-01-01'), // Data suspeita/inválida
                'location_event_details' => 'Evento a Definir - Local/UF',
                'cache_value' => 45000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'para_assinatura',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [],
        ];
    }

    // === CASOS DE DATAS INVÁLIDAS ===

    private function createInvalidDateCase1($artistIds, $bookerIds)
    {
        return [
            'description' => 'Contract date posterior à gig date',
            'gig' => [
                'artist_id' => $artistIds[0],
                'booker_id' => $bookerIds[0],
                'contract_number' => 'TEST-DATE-001',
                'contract_date' => Carbon::now()->addDays(50), // Posterior à gig_date
                'gig_date' => Carbon::now()->addDays(30),
                'location_event_details' => 'Show com Data Errada - Porto Alegre/RS',
                'cache_value' => 55000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Pagamento único',
                    'due_value' => 55000.00,
                    'due_date' => Carbon::now()->addDays(25),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
            ],
        ];
    }

    private function createInvalidDateCase2($artistIds, $bookerIds)
    {
        return [
            'description' => 'Evento vencido com status incorreto',
            'gig' => [
                'artist_id' => $artistIds[1] ?? $artistIds[0],
                'booker_id' => $bookerIds[1] ?? $bookerIds[0],
                'contract_number' => 'TEST-DATE-002',
                'contract_date' => Carbon::now()->subDays(100),
                'gig_date' => Carbon::now()->subDays(45), // Evento já passou
                'location_event_details' => 'Festival Vencido - Recife/PE',
                'cache_value' => 85000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado', // Deveria ser 'concluido'
                'payment_status' => 'vencido', // Status inconsistente
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Primeira parcela vencida',
                    'due_value' => 42500.00,
                    'due_date' => Carbon::now()->subDays(60),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
                [
                    'description' => 'Segunda parcela vencida',
                    'due_value' => 42500.00,
                    'due_date' => Carbon::now()->subDays(30),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
            ],
        ];
    }

    // === CASOS CORRETOS ===

    private function createCorrectCase1($artistIds, $bookerIds)
    {
        return [
            'description' => 'Caso totalmente correto - futuro',
            'gig' => [
                'artist_id' => $artistIds[0],
                'booker_id' => $bookerIds[0],
                'contract_number' => 'TEST-CORRECT-001',
                'contract_date' => Carbon::now()->subDays(20),
                'gig_date' => Carbon::now()->addDays(90),
                'location_event_details' => 'Festival Correto - Florianópolis/SC',
                'cache_value' => 120000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'a_vencer',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Primeira parcela',
                    'due_value' => 60000.00,
                    'due_date' => Carbon::now()->addDays(30),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
                [
                    'description' => 'Segunda parcela',
                    'due_value' => 60000.00,
                    'due_date' => Carbon::now()->addDays(85),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
            ],
        ];
    }

    private function createCorrectCase2($artistIds, $bookerIds)
    {
        return [
            'description' => 'Caso correto - totalmente pago',
            'gig' => [
                'artist_id' => $artistIds[1] ?? $artistIds[0],
                'booker_id' => $bookerIds[1] ?? $bookerIds[0],
                'contract_number' => 'TEST-CORRECT-002',
                'contract_date' => Carbon::now()->subDays(150),
                'gig_date' => Carbon::now()->subDays(60),
                'location_event_details' => 'Show Pago Corretamente - Manaus/AM',
                'cache_value' => 90000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'concluido',
                'payment_status' => 'pago',
                'artist_payment_status' => 'pago',
                'booker_payment_status' => 'pago',
            ],
            'payments' => [
                [
                    'description' => 'Pagamento total',
                    'due_value' => 90000.00,
                    'due_date' => Carbon::now()->subDays(70),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                    'received_value_actual' => 90000.00,
                    'received_date_actual' => Carbon::now()->subDays(65),
                    'confirmed_at' => Carbon::now()->subDays(65),
                ],
            ],
        ];
    }

    private function createCorrectCase3($artistIds, $bookerIds)
    {
        return [
            'description' => 'Caso correto - pagamento parcial futuro',
            'gig' => [
                'artist_id' => $artistIds[2] ?? $artistIds[0],
                'booker_id' => $bookerIds[2] ?? $bookerIds[0],
                'contract_number' => 'TEST-CORRECT-003',
                'contract_date' => Carbon::now()->subDays(40),
                'gig_date' => Carbon::now()->addDays(120),
                'location_event_details' => 'Festival Futuro - Goiânia/GO',
                'cache_value' => 150000.00,
                'currency' => 'BRL',
                'agency_commission_type' => 'percent',
                'agency_commission_rate' => 20.00,
                'booker_commission_type' => 'percent',
                'booker_commission_rate' => 5.00,
                'contract_status' => 'assinado',
                'payment_status' => 'parcial',
                'artist_payment_status' => 'pendente',
                'booker_payment_status' => 'pendente',
            ],
            'payments' => [
                [
                    'description' => 'Adiantamento - confirmado',
                    'due_value' => 50000.00,
                    'due_date' => Carbon::now()->subDays(10),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                    'received_value_actual' => 50000.00,
                    'received_date_actual' => Carbon::now()->subDays(8),
                    'confirmed_at' => Carbon::now()->subDays(8),
                ],
                [
                    'description' => 'Saldo final',
                    'due_value' => 100000.00,
                    'due_date' => Carbon::now()->addDays(115),
                    'currency' => 'BRL',
                    'exchange_rate' => 1.0,
                ],
            ],
        ];
    }
}
