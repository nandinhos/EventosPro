<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;      // Pagamentos PREVISTOS
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
        if (empty($dateString)) { return null; }
        try {
            return Carbon::createFromFormat('d/m/Y', trim($dateString))->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Formato de data inválido no CSV: " . $dateString);
            return null;
        }
    }

    /**
     * Limpa e converte valor monetário string para float.
     */
    private function parseValue(?string $valueString): ?float
    {
        if ($valueString === null || $valueString === '') { return null; }
        $cleaned = preg_replace('/[^\d,\.]/', '', $valueString);
        $cleaned = str_replace(',', '.', $cleaned);
        if (substr_count($cleaned, '.') > 1) {
             $cleaned = str_replace('.', '', substr($cleaned, 0, strrpos($cleaned, '.'))) . substr($cleaned, strrpos($cleaned, '.'));
        }
        $value = filter_var($cleaned, FILTER_VALIDATE_FLOAT);
        return $value === false ? null : $value;
    }

    /**
     * Executa o Seeder.
     */
    public function run(): void
    {
        $this->command->info("Iniciando importação de Gigs, GigCosts e Payments (Previstos) do CSV...");

        $artistIds = Artist::pluck('id', 'name');
        $bookerIds = Booker::pluck('id', 'name');
        $costCenterIds = CostCenter::pluck('id', 'name');

        $csvFile = fopen(base_path("database/data/tabela.csv"), "r");
        $firstline = true;
        $gigsCreatedCount = 0;
        $paymentsCreatedCount = 0;
        $costsCreatedCount = 0;

        if ($csvFile === false) {
            $this->command->error("Arquivo CSV 'tabela.csv' não encontrado em database/data/"); return;
        }

        DB::beginTransaction();
        $now = now();

        try {
            while (($row = fgetcsv($csvFile, 2000, ",")) !== false) {
                if ($firstline) { $firstline = false; continue; }

                // Índices das colunas
                $idxCol = [ /* ... (mesmos índices de antes) ... */
                    'contract_date' => 1, 'contract_number' => 2, 'contract_status' => 3,
                    'booker_name' => 4, 'artist_name' => 5, 'local_event_details' => 6,
                    'gig_date' => 7, 'type' => 8, 'currency' => 9, 'cache_value' => 10,
                    'catering' => 12, 'logistics' => 13, 'hotel' => 14,
                    'venc1_date' => 15, 'venc1_value' => 16, 'venc2_date' => 17, 'venc2_value' => 18,
                    'venc3_date' => 19, 'venc3_value' => 20, 'artist_payment_value' => 21,
                    'nfs_number' => 22, 'agency_commission_value' => 23, 'booker_commission_value' => 24
                ];

                // --- Mapeamento e Limpeza (sem alterações significativas) ---
                $artistName = trim($row[$idxCol['artist_name']] ?? '');
                $gigDateStr = trim($row[$idxCol['gig_date']] ?? '');
                if (empty($artistName) || empty($gigDateStr)) { Log::warning("Linha CSV ignorada: Falta Artista ou Data da Gig."); continue; }
                $artistId = $artistIds->get($artistName);
                if (!$artistId) { Log::warning("Artista não encontrado no BD: '{$artistName}'. Linha ignorada."); continue; }
                $bookerName = trim($row[$idxCol['booker_name']] ?? '');
                $bookerId = (strtoupper($bookerName) === 'CORAL' || empty($bookerName)) ? null : $bookerIds->get($bookerName);
                if (!empty($bookerName) && strtoupper($bookerName) !== 'CORAL' && !$bookerId) { Log::warning("Booker '{$bookerName}' não encontrado. Definido como NULL."); }
                $gigDate = $this->parseDate($gigDateStr);
                if (!$gigDate) { Log::warning("Data Gig inválida '{$gigDateStr}'. Linha ignorada."); continue; }
                $cacheValue = $this->parseValue($row[$idxCol['cache_value']] ?? '0') ?? 0.00;
                $currency = trim($row[$idxCol['currency']] ?? 'BRL'); $currency = empty($currency) ? 'BRL' : strtoupper($currency);
                $agencyCommissionValue = $this->parseValue($row[$idxCol['agency_commission_value']] ?? null);
                $bookerCommissionValue = $this->parseValue($row[$idxCol['booker_commission_value']] ?? null);
                $liquidCommissionValue = ($agencyCommissionValue ?? 0) - ($bookerCommissionValue ?? 0);
                $contractStatusCsv = trim(strtolower($row[$idxCol['contract_status']] ?? ''));
                $contractStatus = match ($contractStatusCsv) { 'assinado' => 'assinado', 'enviado' => 'para_assinatura', 'expirado' => 'expirado', 'cancelado' => 'cancelado', default => 'n/a' };
                $artistPaymentStatus = ($this->parseValue($row[$idxCol['artist_payment_value']] ?? '') ?? 0) > 0 ? 'pago' : 'pendente';
                $bookerPaymentStatus = ($bookerId === null || ($bookerCommissionValue ?? 0) <= 0) ? 'pago' : 'pendente';
                $notes = trim($row[$idxCol['type']] ?? '');
                $nfsNumber = trim($row[$idxCol['nfs_number']] ?? null);
                if (!empty($nfsNumber)) { $notes .= ($notes ? "\n" : '') . "NFS Artista: " . $nfsNumber; }

                // --- Criar a Gig (Sem exchange_rate, cache_value_brl e expenses_value_brl) ---
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
                    'contract_status' => $contractStatus,
                    'payment_status' => 'a_vencer', // Será atualizado abaixo
                    'artist_payment_status' => $artistPaymentStatus,
                    'booker_payment_status' => $bookerPaymentStatus,
                    'notes' => $notes ?: null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $gigsCreatedCount++;

                // --- Criar GigCosts (sem alterações) ---
                 $costsData = []; $costMapping = ['Catering' => $idxCol['catering'], 'Logistics' => $idxCol['logistics'], 'Hotel' => $idxCol['hotel']];
                 foreach($costMapping as $costCenterName => $colIndex) { /* ... (lógica igual para criar $costsData) ... */ }
                 if (!empty($costsData)) { GigCost::insert($costsData); $costsCreatedCount += count($costsData); }


                // --- CORRIGIDO: Criar Pagamentos (PREVISTOS) ---
                $paymentsData = [];
                $hasOverduePayment = false;
                $hasFuturePayment = false;

                foreach ([15, 17, 19] as $dateColIdx) {
                    $valueColIdx = $dateColIdx + 1;
                    $dueDateStr = trim($row[$dateColIdx] ?? '');
                    $valueStr = trim($row[$valueColIdx] ?? '');
                    $dueDate = $this->parseDate($dueDateStr);
                    $dueValue = $this->parseValue($valueStr);

                    if ($dueDate && $dueValue !== null && $dueValue > 0) {
                        $paymentNotes = 'Ref. Venc ' . (($dateColIdx - 13) / 2);
                        if ($dateColIdx == 19 && in_array(trim($row[0]), ['282', '289', '294', '297'])) {
                            $paymentNotes .= ' (Valor somatório)';
                        }

                        // Preenche colunas da tabela 'payments' conforme o modelo
                        $paymentsData[] = [
                            'gig_id' => $gig->id,
                            'description' => $paymentNotes,
                            'due_value' => $dueValue,       // Valor Devido
                            'due_date' => $dueDate,        // Data Vencimento
                            'currency' => 'BRL',           // Moeda do valor devido (assumindo BRL)
                            'exchange_rate' => null,       // Câmbio do valor devido
                            'received_value_actual' => null, // Valor recebido (null na importação)
                            'received_date_actual' => null, // Data recebida (null na importação)
                            'confirmed_at' => null,        // Não confirmado
                            'confirmed_by' => null,
                            'notes' => null,               // Notas específicas do pagamento
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        // Verifica datas para status GERAL da Gig
                        if (Carbon::parse($dueDate)->isPast()) {
                            $hasOverduePayment = true;
                        } else {
                            $hasFuturePayment = true;
                        }
                    }
                }

                 if (!empty($paymentsData)) {
                     Payment::insert($paymentsData);
                     $paymentsCreatedCount += count($paymentsData);
                 }

                 // --- Atualiza Status de Pagamento Geral da Gig (baseado nas datas de VENCIMENTO) ---
                $finalPaymentStatus = 'a_vencer'; // Default
                if ($gig->cache_value <= 0 && $gig->currency == 'BRL') { // Considera pago se cachê BRL for zero
                    $finalPaymentStatus = 'pago';
                } elseif ($hasOverduePayment) {
                    $finalPaymentStatus = 'vencido'; // Se alguma data VENC passou
                } elseif ($hasFuturePayment) {
                     $finalPaymentStatus = 'a_vencer'; // Se tem datas VENC futuras
                } else {
                     // Se não tem VENC definidos mas cache > 0, fica 'a_vencer'
                     $finalPaymentStatus = 'a_vencer';
                }

                 if ($gig->payment_status !== $finalPaymentStatus) {
                     $gig->payment_status = $finalPaymentStatus;
                     $gig->saveQuietly(); // Salva sem disparar eventos
                 }

            } // Fim do While

            DB::commit();
            $this->command->info("Importação concluída! Gigs: {$gigsCreatedCount}. Payments (Previstos): {$paymentsCreatedCount}. Costs: {$costsCreatedCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro CRÍTICO durante a importação do CSV: " . $e->getMessage() . " na linha CSV aprox.: " . implode(',', $row ?? ['Erro ao ler linha']), ['exception' => $e]);
            $this->command->error("Erro durante a importação. Verifique os logs. Nenhuma alteração foi salva.");
        } finally {
             if (isset($csvFile) && is_resource($csvFile)) { fclose($csvFile); }
        }
    }
}