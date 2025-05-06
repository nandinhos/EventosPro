<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;      // Pagamentos PREVISTOS/RECEBIDOS
use App\Models\CostCenter;   // Centros de Custo
use App\Models\GigCost;      // Custos da Gig
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GigSeeder extends Seeder
{
    /**
     * Converte string de data DD/MM/YYYY para YYYY-MM-DD ou retorna null.
     */
    private function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }
        try {
            // Tenta criar a data no formato esperado
            return Carbon::createFromFormat('d/m/Y', trim($dateString))->format('Y-m-d');
        } catch (\Exception $e) {
            // Loga o erro se a data for inválida
            Log::warning("Formato de data inválido encontrado no CSV: " . $dateString);
            return null; // Retorna null se o formato for inválido
        }
    }

    /**
     * Limpa e converte valor monetário string para float.
     */
    private function parseValue(?string $valueString): ?float
    {
        if ($valueString === null || $valueString === '') {
            return null;
        }
        // Remove caracteres não numéricos exceto ponto e vírgula
        $cleaned = preg_replace('/[^\d,\.]/', '', $valueString);
        // Troca vírgula por ponto para decimal
        $cleaned = str_replace(',', '.', $cleaned);
        // Remove pontos extras (separadores de milhar), mantendo apenas o último como decimal
        if (substr_count($cleaned, '.') > 1) {
             $cleaned = str_replace('.', '', substr($cleaned, 0, strrpos($cleaned, '.'))) . substr($cleaned, strrpos($cleaned, '.'));
        }

        $value = filter_var($cleaned, FILTER_VALIDATE_FLOAT);
        // Retorna null se não for um float válido
        return $value === false ? null : $value;
    }

    /**
     * Executa o Seeder.
     */
    public function run(): void
    {
        $this->command->info("Iniciando importação de Gigs, GigCosts e Payments (Previstos) do CSV...");

        // Cache de IDs para performance
        $artistIds = Artist::pluck('id', 'name');
        $bookerIds = Booker::pluck('id', 'name');
        $costCenterIds = CostCenter::pluck('id', 'name');
        Log::debug('Cost Center IDs carregados:', $costCenterIds->toArray());

        $csvPath = base_path("database/data/tabela.csv");
        if (!file_exists($csvPath)) {
             $this->command->error("Arquivo CSV 'tabela.csv' não encontrado em database/data/"); return;
        }

        $csvFile = fopen($csvPath, "r");
        $firstline = true;
        $gigsCreatedCount = 0;
        $paymentsCreatedCount = 0;
        $costsCreatedCount = 0;

        if ($csvFile === false) {
            $this->command->error("Não foi possível abrir o arquivo CSV."); return;
        }

        DB::beginTransaction();
        $now = now(); // Timestamp para created_at/updated_at

        try {
            while (($row = fgetcsv($csvFile, 2000, ",")) !== false) {
                if ($firstline) { $firstline = false; continue; } // Pular cabeçalho

                // Índices das colunas conforme seu CSV
                $idxCol = [
                    'contract_date' => 1, 'contract_number' => 2, 'contract_status' => 3,
                    'booker_name' => 4, 'artist_name' => 5, 'local_event_details' => 6,
                    'gig_date' => 7, 'type' => 8, 'currency' => 9, 'cache_value' => 10,
                    'catering' => 12, 'logistics' => 13, 'hotel' => 14,
                    'venc1_date' => 15, 'venc1_value' => 16, 'venc2_date' => 17, 'venc2_value' => 18,
                    'venc3_date' => 19, 'venc3_value' => 20, 'artist_payment_value' => 21,
                    'nfs_number' => 22, 'agency_commission_value' => 23, 'booker_commission_value' => 24
                ];

                // --- Mapeamento e Limpeza ---
                $artistName = trim($row[$idxCol['artist_name']] ?? '');
                $gigDateStr = trim($row[$idxCol['gig_date']] ?? '');
                if (empty($artistName) || empty($gigDateStr)) { Log::warning("Linha CSV ignorada: Falta Artista ou Data da Gig.", ['row_data' => $row]); continue; }

                $artistId = $artistIds->get($artistName);
                if (!$artistId) { Log::warning("Artista não encontrado no BD: '{$artistName}'. Linha ignorada.", ['row_data' => $row]); continue; }

                $bookerName = trim($row[$idxCol['booker_name']] ?? '');
                $bookerId = (strtoupper($bookerName) === 'CORAL' || empty($bookerName)) ? null : $bookerIds->get($bookerName);
                if (!empty($bookerName) && strtoupper($bookerName) !== 'CORAL' && !$bookerId) { Log::warning("Booker '{$bookerName}' não encontrado no BD. Definindo como NULL."); }

                $gigDate = $this->parseDate($gigDateStr);
                if (!$gigDate) { Log::warning("Data Gig inválida '{$gigDateStr}'. Linha ignorada."); continue; }

                $cacheValue = $this->parseValue($row[$idxCol['cache_value']] ?? '0') ?? 0.00;
                $currency = trim($row[$idxCol['currency']] ?? 'BRL'); $currency = empty($currency) ? 'BRL' : strtoupper($currency);
                // Calcula BRL apenas para lógica de status (com taxa fixa para importação)
                $exchangeRateTemp = ($currency !== 'BRL') ? 5.0 : null; // TODO: Implementar busca de taxa real
                $cacheValueBrlTemp = ($exchangeRateTemp) ? $cacheValue * $exchangeRateTemp : $cacheValue;

                $agencyCommissionValue = $this->parseValue($row[$idxCol['agency_commission_value']] ?? null);
                $bookerCommissionValue = $this->parseValue($row[$idxCol['booker_commission_value']] ?? null);
                $liquidCommissionValue = ($agencyCommissionValue ?? 0) - ($bookerCommissionValue ?? 0);

                $contractStatusCsv = trim(strtolower($row[$idxCol['contract_status']] ?? ''));
                $contractStatus = match ($contractStatusCsv) { 'assinado' => 'assinado', 'enviado' => 'para_assinatura', 'expirado' => 'expirado', 'cancelado' => 'cancelado', default => 'n/a' };

                // Status de Pagamento do Artista/Booker baseado no CSV
                $artistPaymentStatus = ($this->parseValue($row[$idxCol['artist_payment_value']] ?? '') ?? 0) > 0 ? 'pago' : 'pendente';
                $bookerPaymentStatus = ($bookerId === null || ($bookerCommissionValue ?? 0) <= 0) ? 'pago' : 'pendente'; // Pago se Agência ou comissão zero

                $notes = trim($row[$idxCol['type']] ?? '');
                $nfsNumber = trim($row[$idxCol['nfs_number']] ?? null);
                if (!empty($nfsNumber)) { $notes .= ($notes ? "\n" : '') . "NFS Artista: " . $nfsNumber; }

                // --- Criar a Gig com Status Iniciais ---
                $gig = Gig::create([
                    'artist_id' => $artistId,
                    'booker_id' => $bookerId,
                    'contract_number' => trim($row[$idxCol['contract_number']] ?? null) ?: null,
                    'contract_date' => $this->parseDate(trim($row[$idxCol['contract_date']] ?? null)),
                    'gig_date' => $gigDate,
                    'location_event_details' => trim($row[$idxCol['local_event_details']] ?? 'Local não informado'),
                    'cache_value' => $cacheValue,
                    'currency' => $currency,
                    'agency_commission_value' => $agencyCommissionValue,
                    'booker_commission_value' => $bookerCommissionValue,
                    'liquid_commission_value' => $liquidCommissionValue,
                    // Tipos/Taxas de comissão ficam nulos ou com default da migration
                    'contract_status' => $contractStatus,
                    'payment_status' => 'a_vencer', // Default inicial, será atualizado abaixo
                    'artist_payment_status' => $artistPaymentStatus, // Baseado no CSV
                    'booker_payment_status' => $bookerPaymentStatus, // Baseado no CSV/Booker
                    'notes' => $notes ?: null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $gigsCreatedCount++;

                // --- Criar GigCosts (Iniciar como NÃO confirmado) ---
                $costsData = [];
                $costMapping = ['Catering' => $idxCol['catering'], 'Logistics' => $idxCol['logistics'], 'Hotel' => $idxCol['hotel']];
                foreach($costMapping as $costCenterName => $colIndex) {
                    $rawValue = $row[$colIndex] ?? '';
                    $costValue = $this->parseValue($rawValue);
                    if ($costValue !== null && $costValue > 0) {
                        $costCenterId = $costCenterIds->get($costCenterName);
                        if ($costCenterId) {
                            $costsData[] = [
                                'gig_id' => $gig->id,
                                'cost_center_id' => $costCenterId,
                                'value' => $costValue,
                                'currency' => 'BRL', // Assumindo BRL para despesas
                                'is_confirmed' => false, // <-- Inicia como NÃO CONFIRMADO
                                'confirmed_at' => null,
                                'confirmed_by' => null,
                                'expense_date' => $gigDate, // Usa data da gig como default
                                'description' => null,
                                'notes' => null,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        } else { Log::warning("ID Centro Custo '{$costCenterName}' não encontrado."); }
                    }
                }
                if (!empty($costsData)) {
                    GigCost::insert($costsData);
                    $costsCreatedCount += count($costsData);
                }

                // --- Criar Pagamentos (Previstos/Não Confirmados) ---
                $paymentsData = [];
                $totalDueValue = 0; // Soma dos valores devidos das parcelas
                $hasOverduePaymentDateInCSV = false;
                $hasFuturePaymentDateInCSV = false;

                foreach ([15, 17, 19] as $dateColIdx) {
                    $valueColIdx = $dateColIdx + 1;
                    $dueDateStr = trim($row[$dateColIdx] ?? '');
                    $valueStr = trim($row[$valueColIdx] ?? '');
                    $dueDate = $this->parseDate($dueDateStr);
                    $dueValue = $this->parseValue($valueStr);

                    if ($dueDate && $dueValue !== null && $dueValue > 0) {
                        $paymentNotes = 'Ref. Venc ' . (($dateColIdx - 13) / 2);
                        if ($dateColIdx == 19 && in_array(trim($row[0]), ['282', '289', '294', '297'])) { $paymentNotes .= ' (Valor somatório)'; }

                        $paymentsData[] = [
                            'gig_id' => $gig->id,
                            'description' => $paymentNotes,
                            'due_value' => $dueValue,
                            'due_date' => $dueDate,
                            'currency' => 'BRL', // Assumindo BRL para pagamentos previstos
                            'exchange_rate' => null,
                            'received_value_actual' => null, // Não recebido na importação
                            'received_date_actual' => null,  // Não recebido na importação
                            'confirmed_at' => null,
                            'confirmed_by' => null,
                            'notes' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $totalDueValue += $dueValue; // Soma o valor devido desta parcela

                        // Verifica datas de vencimento para status GERAL da Gig
                        if (Carbon::parse($dueDate)->isPast()) { $hasOverduePaymentDateInCSV = true; }
                        else { $hasFuturePaymentDateInCSV = true; }
                    }
                }
                 if (!empty($paymentsData)) {
                     Payment::insert($paymentsData);
                     $paymentsCreatedCount += count($paymentsData);
                 }

                 // --- Atualiza Status de Pagamento Geral da Gig ---
                 $finalPaymentStatus = 'a_vencer'; // Default
                 // Se cachê BRL for 0, considera pago
                 if ($cacheValueBrlTemp <= 0) {
                     $finalPaymentStatus = 'pago';
                 }
                 // Se tem parcela vencida (e assumimos que não foi confirmada como paga)
                 elseif ($hasOverduePaymentDateInCSV) {
                     $finalPaymentStatus = 'vencido';
                 }
                 // Se tem parcela futura OU se não tem parcelas mas o cachê é > 0
                 elseif ($hasFuturePaymentDateInCSV || (empty($paymentsData) && $cacheValueBrlTemp > 0)) {
                      $finalPaymentStatus = 'a_vencer';
                 }
                 // Caso especial: Se tem parcelas, NENHUMA está vencida e NENHUMA é futura (todas venceram HOJE?)
                 // ou se a soma das parcelas do CSV já bate com o cachê BRL (improvável sem confirmação)
                 // Mantemos 'a_vencer' como fallback seguro se as condições acima não baterem.


                 if ($gig->payment_status !== $finalPaymentStatus) {
                     $gig->payment_status = $finalPaymentStatus;
                     $gig->saveQuietly(); // Salva sem disparar eventos
                 }

            } // Fim do While

            DB::commit();
            $this->command->info("Importação FINALIZADA! Gigs: {$gigsCreatedCount}. Payments (Previstos): {$paymentsCreatedCount}. Costs: {$costsCreatedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro CRÍTICO durante a importação do CSV: " . $e->getMessage() . " na linha CSV aprox.: " . implode(',', $row ?? ['Erro ao ler linha']), ['exception' => $e]);
            $this->command->error("Erro durante a importação. Verifique os logs. Nenhuma alteração foi salva.");
        } finally {
             if (isset($csvFile) && is_resource($csvFile)) { fclose($csvFile); }
        }
    } // Fim run()
} // Fim classe