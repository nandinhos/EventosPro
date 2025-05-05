<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment; // Importar Payment
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
        if (empty($dateString)) return null;
        try {
            return Carbon::createFromFormat('d/m/Y', trim($dateString))->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Formato de data inválido encontrado no CSV: " . $dateString);
            return null;
        }
    }

    /**
     * Limpa e converte valor monetário string para float.
     */
    private function parseValue(?string $valueString): ?float
    {
        if ($valueString === null || $valueString === '') return null;
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
        $this->command->info("Iniciando importação de Gigs e Payments (Previstos) do CSV...");

        $artistIds = Artist::pluck('id', 'name');
        $bookerIds = Booker::pluck('id', 'name');

        $csvFile = fopen(base_path("database/data/tabela.csv"), "r");
        $firstline = true;
        $gigsCreatedCount = 0;
        $paymentsCreatedCount = 0;

        if ($csvFile === false) {
            $this->command->error("Arquivo CSV 'tabela.csv' não encontrado em database/data/");
            return;
        }

        DB::beginTransaction();
        $now = now(); // Define $now antes do loop try

        try {
            while (($row = fgetcsv($csvFile, 2000, ",")) !== false) {
                if ($firstline) {
                    $firstline = false;
                    continue;
                }

                // --- Mapeamento e Limpeza de Dados ---
                $artistName = trim($row[5] ?? '');
                $bookerName = trim($row[4] ?? '');
                $gigDateStr = trim($row[7] ?? '');
                if (empty($artistName) || empty($gigDateStr)) {
                    Log::warning("Linha ignorada por falta de Artista ou Data da Gig: " . implode(',', $row));
                    continue;
                }
                $artistId = $artistIds->get($artistName);
                $bookerId = (strtoupper($bookerName) === 'CORAL' || empty($bookerName)) ? null : $bookerIds->get($bookerName);
                if (!$artistId) { Log::warning("Artista não encontrado: '{$artistName}'. Linha ignorada."); continue; }
                if (!empty($bookerName) && strtoupper($bookerName) !== 'CORAL' && !$bookerId) { Log::warning("Booker não encontrado: '{$bookerName}'. Definido como NULL."); }

                $gigDate = $this->parseDate($gigDateStr);
                if (!$gigDate) { Log::warning("Data da Gig inválida para '{$artistName}' em {$gigDateStr}. Linha ignorada."); continue; }

                $cacheValue = $this->parseValue(trim($row[10] ?? '0')) ?? 0.00; // Col K - Cache Bruto
                $currency = trim($row[9] ?? 'BRL');
                $currency = empty($currency) ? 'BRL' : strtoupper($currency);
                $exchangeRate = ($currency !== 'BRL') ? 5.0 : null; // Taxa padrão para importação
                $cacheValueBrl = ($exchangeRate) ? $cacheValue * $exchangeRate : $cacheValue; // Referência para comissão
                $expensesValueBrl = ($this->parseValue(trim($row[12] ?? '0')) ?? 0) + ($this->parseValue(trim($row[13] ?? '0')) ?? 0) + ($this->parseValue(trim($row[14] ?? '0')) ?? 0);
                $agencyCommissionValue = $this->parseValue(trim($row[23] ?? ''));
                $bookerCommissionValue = $this->parseValue(trim($row[24] ?? ''));
                $liquidCommissionValue = ($agencyCommissionValue ?? 0) - ($bookerCommissionValue ?? 0);
                $contractStatus = match (trim(strtolower($row[3] ?? ''))) { 'assinado' => 'assinado', 'enviado' => 'para_assinatura', 'expirado' => 'expirado', 'cancelado' => 'cancelado', default => 'n/a' };
                $artistPaymentStatus = ($this->parseValue(trim($row[21] ?? '')) ?? 0) > 0 ? 'pago' : 'pendente';
                $bookerPaymentStatus = ($bookerId === null || ($bookerCommissionValue ?? 0) <= 0) ? 'pago' : 'pendente';
                $notes = trim($row[8] ?? ''); // Coluna 'type'
                $nfsNumber = trim($row[22] ?? null);
                if (!empty($nfsNumber)) $notes .= ($notes ? "\n" : '') . "NFS Artista: " . $nfsNumber;


                // --- Criar a Gig ---
                $gig = Gig::create([
                    'artist_id' => $artistId,
                    'booker_id' => $bookerId,
                    'contract_number' => trim($row[2] ?? null) ?: null,
                    'contract_date' => $this->parseDate(trim($row[1] ?? null)),
                    'gig_date' => $gigDate,
                    'location_event_details' => trim($row[6] ?? 'Local não informado'),
                    'cache_value' => $cacheValue,
                    'currency' => $currency,
                    'expenses_value_brl' => $expensesValueBrl,
                    'agency_commission_value' => $agencyCommissionValue,
                    'booker_commission_value' => $bookerCommissionValue,
                    'liquid_commission_value' => $liquidCommissionValue,
                    'contract_status' => $contractStatus,
                    'payment_status' => 'a_vencer', // Default, será recalculado pelo Listener
                    'artist_payment_status' => $artistPaymentStatus,
                    'booker_payment_status' => $bookerPaymentStatus,
                    'notes' => $notes ?: null,
                    // exchange_rate, cache_value_brl, comission_type/rate removidos daqui
                 ]);
                $gigsCreatedCount++;

                // --- Criar Pagamentos PREVISTOS ---
                $paymentsData = [];
                $vencDates = [$this->parseDate(trim($row[15] ?? '')), $this->parseDate(trim($row[17] ?? '')), $this->parseDate(trim($row[19] ?? ''))];
                $vencValues = [$this->parseValue(trim($row[16] ?? '')), $this->parseValue(trim($row[18] ?? '')), $this->parseValue(trim($row[20] ?? ''))];
                $vencNotes = ['Parcela 1/Venc 1', 'Parcela 2/Venc 2', 'Parcela 3/Venc 3'];
                 if (in_array(trim($row[0]), ['282', '289', '294', '297'])) $vencNotes[2] .= ' (Valor referente a múltiplas parcelas finais)';

                for ($i = 0; $i < 3; $i++) {
                    if ($vencDates[$i] && $vencValues[$i] !== null) {
                        // Assume BRL para pagamentos do CSV por enquanto
                        $paymentCurrency = 'BRL';
                        $paymentExchangeRate = null;

                        // === CORREÇÃO AQUI ===
                        // Preenche apenas as colunas que existem na tabela 'payments' AGORA
                        $paymentsData[] = [
                            'gig_id' => $gig->id,
                            'description' => $vencNotes[$i], // <-- PASSANDO description
                            'due_value' => $vencValues[$i],
                            'due_date' => $vencDates[$i],
                            'currency' => $paymentCurrency, // <-- PASSANDO currency
                            'exchange_rate' => $paymentExchangeRate, // <-- PASSANDO exchange_rate
                            'received_value_actual' => null,
                            'received_date_actual' => null,
                            'confirmed_at' => null,
                            'confirmed_by' => null,
                            'notes' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        // === FIM DA CORREÇÃO ===
                    }
                }

                // Insere os pagamentos previstos
                if (!empty($paymentsData)) {
                    Payment::insert($paymentsData);
                    $paymentsCreatedCount += count($paymentsData);
                }

                // Status da Gig NÃO é mais atualizado aqui

            } // Fim do While

            DB::commit();
            $this->command->info("Importação concluída! Gigs criadas: " . $gigsCreatedCount . ". Pagamentos Previstos criados: " . $paymentsCreatedCount);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro durante a importação do CSV: " . $e->getMessage() . " na linha CSV: " . implode(',', $row ?? ['Erro ao ler linha']), ['exception' => $e]);
            $this->command->error("Erro durante a importação. Verifique os logs. Nenhuma alteração foi salva.");
        } finally {
            if (isset($csvFile) && is_resource($csvFile)) {
                 fclose($csvFile);
            }
        }
    }
}