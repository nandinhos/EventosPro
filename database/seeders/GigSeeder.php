<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Gig;
use App\Models\Payment;
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
        return $value === false ? null : $value;
    }

    /**
     * Executa o Seeder.
     */
    public function run(): void
    {
        $this->command->info("Iniciando importação de Gigs e Payments do CSV...");

        // Cache de IDs para performance
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

        // Iniciar transação para garantir consistência
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($csvFile, 2000, ",")) !== false) {
                if ($firstline) {
                    $firstline = false;
                    continue; // Pular cabeçalho
                }

                // --- Mapeamento e Limpeza de Dados da Linha CSV ---
                $artistName = trim($row[5] ?? '');
                $bookerName = trim($row[4] ?? '');
                $gigDateStr = trim($row[7] ?? '');
                $locationDetails = trim($row[6] ?? 'Local não informado');
                $cacheValueStr = trim($row[10] ?? '0'); // Coluna K - 'cache_value' no CSV (Bruto)
                $currency = trim($row[9] ?? 'BRL');
                $contractNumber = trim($row[2] ?? null);
                $contractDateStr = trim($row[1] ?? null);
                $contractStatusCsv = trim(strtolower($row[3] ?? ''));

                $cateringStr = trim($row[12] ?? '0');
                $logisticsStr = trim($row[13] ?? '0');
                $hotelStr = trim($row[14] ?? '0');

                $venc1DateStr = trim($row[15] ?? '');
                $venc1ValueStr = trim($row[16] ?? '');
                $venc2DateStr = trim($row[17] ?? '');
                $venc2ValueStr = trim($row[18] ?? '');
                $venc3DateStr = trim($row[19] ?? '');
                $venc3ValueStr = trim($row[20] ?? '');

                $artistPaymentValueStr = trim($row[21] ?? ''); // Coluna V - 'artist_payment'
                $nfsNumber = trim($row[22] ?? null); // Coluna W
                $agencyCommissionValueStr = trim($row[23] ?? ''); // Coluna X
                $bookerCommissionValueStr = trim($row[24] ?? ''); // Coluna Y

                // --- Validações Essenciais ---
                if (empty($artistName) || empty($gigDateStr)) {
                    Log::warning("Linha ignorada no CSV por falta de Artista ou Data da Gig: " . implode(',', $row));
                    continue;
                }

                // --- Busca IDs e Converte Dados ---
                $artistId = $artistIds->get($artistName);
                // Tratar caso CORAL -> null booker_id, outros nomes buscam ID
                $bookerId = (strtoupper($bookerName) === 'CORAL' || empty($bookerName)) ? null : $bookerIds->get($bookerName);

                if (!$artistId) {
                     Log::warning("Artista não encontrado no banco: '{$artistName}'. Linha ignorada.");
                     continue; // Pula linha se artista não existe
                }
                if (!empty($bookerName) && strtoupper($bookerName) !== 'CORAL' && !$bookerId) {
                     Log::warning("Booker não encontrado no banco: '{$bookerName}'. Definindo como NULL.");
                     // $bookerId já será null por causa do get()
                }

                $gigDate = $this->parseDate($gigDateStr);
                if (!$gigDate) {
                    Log::warning("Data da Gig inválida para '{$artistName}' em {$gigDateStr}. Linha ignorada.");
                    continue; // Pula linha se data da gig for inválida
                }
                $contractDate = $this->parseDate($contractDateStr);

                $cacheValue = $this->parseValue($cacheValueStr) ?? 0.00;
                $currency = empty($currency) ? 'BRL' : strtoupper($currency);
                // TODO: Implementar busca de taxa de câmbio real ou usar uma padrão
                $exchangeRate = ($currency !== 'BRL') ? 5.0 : null; // Taxa padrão 5.0 para teste
                $cacheValueBrl = ($exchangeRate) ? $cacheValue * $exchangeRate : $cacheValue;

                $expensesValueBrl = ($this->parseValue($cateringStr) ?? 0) +
                                    ($this->parseValue($logisticsStr) ?? 0) +
                                    ($this->parseValue($hotelStr) ?? 0);

                $agencyCommissionValue = $this->parseValue($agencyCommissionValueStr);
                $bookerCommissionValue = $this->parseValue($bookerCommissionValueStr);
                $liquidCommissionValue = ($agencyCommissionValue ?? 0) - ($bookerCommissionValue ?? 0);

                // --- Mapeamento de Status ---
                $contractStatus = match ($contractStatusCsv) {
                    'assinado' => 'assinado',
                    'enviado' => 'para_assinatura',
                    'expirado' => 'expirado',
                     // '' ou outros valores -> 'n/a'
                    default => 'n/a',
                };

                // Status de pagamento serão definidos após processar as parcelas
                $paymentStatus = 'a_vencer'; // Default inicial
                $artistPaymentStatus = ($this->parseValue($artistPaymentValueStr) ?? 0) > 0 ? 'pago' : 'pendente';
                $bookerPaymentStatus = ($bookerId === null || ($this->parseValue($bookerCommissionValueStr) ?? 0) <= 0) ? 'pago' : 'pendente'; // Pago se for CORAL/vazio ou comissão 0

                // --- Notas Adicionais ---
                $notes = trim($row[8] ?? ''); // Coluna 'type'
                if (!empty($nfsNumber)) {
                    $notes .= ($notes ? "\n" : '') . "NFS Artista: " . $nfsNumber;
                }
                // Adicionar nota sobre reembolso pendente se necessário (baseado nas cores que não temos)

                // --- Criar a Gig ---
                $gig = Gig::create([
                    'artist_id' => $artistId,
                    'booker_id' => $bookerId,
                    'contract_number' => $contractNumber,
                    'contract_date' => $contractDate,
                    'gig_date' => $gigDate,
                    'location_event_details' => $locationDetails,
                    'cache_value' => $cacheValue,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'cache_value_brl' => $cacheValueBrl,
                    'expenses_value_brl' => $expensesValueBrl,
                    'agency_commission_value' => $agencyCommissionValue,
                    'booker_commission_value' => $bookerCommissionValue,
                    'liquid_commission_value' => $liquidCommissionValue,
                    // Tipos/Taxas de comissão ficam nulos por enquanto
                    'contract_status' => $contractStatus,
                    'payment_status' => $paymentStatus, // Será atualizado abaixo
                    'artist_payment_status' => $artistPaymentStatus,
                    'booker_payment_status' => $bookerPaymentStatus,
                    'notes' => $notes ?: null, // Salva null se vazio
                ]);
                $gigsCreatedCount++;

                // --- Criar Pagamentos Recebidos ---
                $totalDue = $gig->cache_value_brl; // Valor total a ser recebido
                $totalReceived = 0;
                $paymentsData = [];
                $hasPendingPayment = false;
                $hasOverduePayment = false;

                // Processa Vencimento 1
                if (($dueDate = $this->parseDate($venc1DateStr)) && ($value = $this->parseValue($venc1ValueStr)) !== null) {
                     // **ASSUMINDO que o valor no CSV é o devido, e não o recebido**
                     $paymentsData[] = [
                        'gig_id' => $gig->id,
                        'due_date' => $dueDate,
                        'received_value' => $value, // Valor da parcela
                        'paid_at' => null, // Assumindo não pago inicialmente
                        'status' => 'pendente',
                        'notes' => 'Parcela 1/Venc 1',
                        'created_at' => now(),
                        'updated_at' => now(),
                     ];
                     // Lógica para status (a ser aplicada depois de salvar os payments)
                     if (Carbon::parse($dueDate)->isPast()) {
                         $hasOverduePayment = true;
                     } else {
                         $hasPendingPayment = true;
                     }
                     // NÃO somamos em $totalReceived aqui, pois estamos assumindo que são valores *devidos*
                }
                // Processa Vencimento 2
                if (($dueDate = $this->parseDate($venc2DateStr)) && ($value = $this->parseValue($venc2ValueStr)) !== null) {
                     $paymentsData[] = [
                        'gig_id' => $gig->id,
                        'due_date' => $dueDate,
                        'received_value' => $value,
                        'paid_at' => null,
                        'status' => 'pendente',
                        'notes' => 'Parcela 2/Venc 2',
                        'created_at' => now(),
                        'updated_at' => now(),
                     ];
                      if (Carbon::parse($dueDate)->isPast()) {
                         $hasOverduePayment = true;
                     } else {
                         $hasPendingPayment = true;
                     }
                }
                // Processa Vencimento 3
                if (($dueDate = $this->parseDate($venc3DateStr)) && ($value = $this->parseValue($venc3ValueStr)) !== null) {
                    $notesVenc3 = 'Parcela 3/Venc 3';
                     // Verifica se é um dos casos de somatório
                     if (in_array(trim($row[0]), ['282', '289', '294', '297'])) {
                         $notesVenc3 .= ' (Valor referente a múltiplas parcelas finais)';
                     }
                     $paymentsData[] = [
                        'gig_id' => $gig->id,
                        'due_date' => $dueDate,
                        'received_value' => $value,
                        'paid_at' => null,
                        'status' => 'pendente',
                        'notes' => $notesVenc3,
                        'created_at' => now(),
                        'updated_at' => now(),
                     ];
                      if (Carbon::parse($dueDate)->isPast()) {
                         $hasOverduePayment = true;
                     } else {
                         $hasPendingPayment = true;
                     }
                }

                // Insere os pagamentos (se houver)
                if (!empty($paymentsData)) {
                    Payment::insert($paymentsData);
                    $paymentsCreatedCount += count($paymentsData);
                }

                // --- Atualiza Status de Pagamento Geral da Gig ---
                // Lógica Simplificada para Importação:
                // Se tem VENC/VALOR e alguma data já passou -> VENCIDO
                // Se tem VENC/VALOR e nenhuma data passou -> A VENCER
                // Se NÃO tem VENC/VALOR (e cache > 0) -> A VENCER (precisa registrar manualmente)
                // Se cache = 0 -> PAGO (ou N/A?) - Vamos considerar PAGO se cache for 0
                if ($gig->cache_value_brl <= 0) {
                    $finalPaymentStatus = 'pago';
                } elseif ($hasOverduePayment) {
                    $finalPaymentStatus = 'vencido';
                } elseif ($hasPendingPayment) {
                    $finalPaymentStatus = 'a_vencer';
                } else {
                     $finalPaymentStatus = 'a_vencer'; // Default se não houver parcelas definidas
                }

                // Atualiza o status na Gig recém-criada
                 if ($gig->payment_status !== $finalPaymentStatus) {
                    $gig->payment_status = $finalPaymentStatus;
                    $gig->saveQuietly(); // Salva sem disparar eventos/observers durante o seed
                 }


            } // Fim do While

            DB::commit(); // Confirma tudo se chegou até aqui sem erro
            $this->command->info("Importação concluída! Gigs criadas: " . $gigsCreatedCount . ". Pagamentos criados: " . $paymentsCreatedCount);

        } catch (\Exception $e) {
            DB::rollBack(); // Desfaz tudo em caso de erro
            Log::error("Erro durante a importação do CSV: " . $e->getMessage() . " na linha: " . implode(',', $row ?? []));
            $this->command->error("Erro durante a importação. Verifique os logs. Nenhuma alteração foi salva.");
        } finally {
            if (isset($csvFile) && is_resource($csvFile)) {
                 fclose($csvFile);
            }
        }
    }
}