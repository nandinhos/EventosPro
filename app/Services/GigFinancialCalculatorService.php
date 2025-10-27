<?php

namespace App\Services;

use App\Models\Gig;
// GigCost não é mais diretamente usado aqui, pois pegamos os custos da relação da Gig
use Illuminate\Support\Facades\Log;

class GigFinancialCalculatorService
{
    /**
     * Calcula o "Cachê Bruto" da Gig em BRL.
     * Fórmula: Valor do Contrato (em BRL) - TOTAL de Despesas Confirmadas (em BRL).
     * Todas as despesas confirmadas (is_confirmed = true) deduzem desta base,
     * independentemente de quem as pagou (is_invoice).
     *
     * @param  Gig  $gig  O modelo da Gig.
     * @return float O valor do cachê bruto em BRL.
     */
    public function calculateGrossCashBrl(Gig $gig): float
    {
        $gig->loadMissing('gigCosts');

        // Pega os detalhes do valor do contrato em BRL
        $contractBrlDetails = $gig->cacheValueBrlDetails; // Usa o novo accessor

        if ($contractBrlDetails['value'] === null) {
            // Se não foi possível converter, não podemos calcular com segurança. Retorna 0.
            Log::warning("[GigFinancialCalculatorService] Não foi possível calcular o Cachê Bruto para Gig ID {$gig->id} por falta de taxa de câmbio.");

            return 0.0;
        }

        $contractValueBrl = $contractBrlDetails['value'];

        $totalConfirmedExpensesBrl = $gig->gigCosts->where('is_confirmed', true)->sum('value_brl');

        $grossCashBrl = $contractValueBrl - $totalConfirmedExpensesBrl;

        Log::debug("[GigFinancialCalculatorService] Calculando Cachê Bruto para Gig ID {$gig->id}: Contrato BRL {$contractValueBrl} - Total Desp. BRL {$totalConfirmedExpensesBrl} = {$grossCashBrl}");

        return (float) max(0, $grossCashBrl);
    }

    /**
     * Calcula a "Comissão Bruta da Agência".
     * Usa o "Cachê Bruto" (já com TODAS as despesas confirmadas deduzidas) como base.
     *
     * @param  Gig  $gig  O modelo da Gig.
     * @return float O valor da comissão bruta da agência em BRL.
     */
    public function calculateAgencyGrossCommissionBrl(Gig $gig): float
    {
        $grossCashBrl = $this->calculateGrossCashBrl($gig); // Base de cálculo AGORA CORRETA
        $commission = 0.0;

        if (strtoupper($gig->agency_commission_type ?? '') === 'PERCENT') {
            $rate = (float) ($gig->agency_commission_rate ?? 20.0);
            $commission = ($grossCashBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Agência (Percentual) para Gig ID {$gig->id}: {$rate}% de Cachê Bruto BRL {$grossCashBrl} = {$commission}");
        } elseif (strtoupper($gig->agency_commission_type ?? '') === 'FIXED') {
            $commission = (float) ($gig->agency_commission_value ?? 0.0);
            Log::debug("[GigFinancialCalculatorService] Comissão Agência (Fixa) para Gig ID {$gig->id}: {$commission}");
        } else {
            $rate = (float) ($gig->agency_commission_rate ?? 20.0);
            $commission = ($grossCashBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Agência (Fallback Percentual) para Gig ID {$gig->id}: {$rate}% de Cachê Bruto BRL {$grossCashBrl} = {$commission}");
        }

        return (float) max(0, $commission);
    }

    /**
     * Calcula o "Cachê Líquido do Artista (antes do reembolso de despesas)".
     * Fórmula: Cachê Bruto - Comissão Bruta da Agência.
     *
     * @param  Gig  $gig  O modelo da Gig.
     * @return float O valor do cachê líquido do artista em BRL, antes de somar reembolsos.
     */
    public function calculateArtistNetPayoutBrl(Gig $gig): float
    {
        $grossCashBrl = $this->calculateGrossCashBrl($gig);
        $agencyGrossCommissionBrl = $this->calculateAgencyGrossCommissionBrl($gig);
        $artistNetPayoutBrl = $grossCashBrl - $agencyGrossCommissionBrl;

        Log::debug("[GigFinancialCalculatorService] Cachê Líquido Artista (antes reembolso) para Gig ID {$gig->id}: Cachê Bruto BRL {$grossCashBrl} - Com. Agência BRL {$agencyGrossCommissionBrl} = {$artistNetPayoutBrl}");

        return (float) max(0, $artistNetPayoutBrl);
    }

    /**
     * Calcula a "Comissão do Booker".
     * Usa o "Cachê Bruto" (já com TODAS as despesas confirmadas deduzidas) como base.
     *
     * @param  Gig  $gig  O modelo da Gig.
     * @return float O valor da comissão do booker em BRL.
     */
    public function calculateBookerCommissionBrl(Gig $gig): float
    {
        if (! $gig->booker_id) {
            return 0.0;
        }
        $grossCashBrl = $this->calculateGrossCashBrl($gig); // Base de cálculo AGORA CORRETA
        $commission = 0.0;

        if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT') {
            $rate = (float) ($gig->booker_commission_rate ?? 5.0);
            $commission = ($grossCashBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Booker (Percentual) para Gig ID {$gig->id}: {$rate}% de Cachê Bruto BRL {$grossCashBrl} = {$commission}");
        } elseif (strtoupper($gig->booker_commission_type ?? '') === 'FIXED') {
            $commission = (float) ($gig->booker_commission_value ?? 0.0);
            Log::debug("[GigFinancialCalculatorService] Comissão Booker (Fixa) para Gig ID {$gig->id}: {$commission}");
        } else {
            if (isset($gig->booker_commission_rate) && is_numeric($gig->booker_commission_rate)) {
                $rate = (float) $gig->booker_commission_rate;
                $commission = ($grossCashBrl * $rate) / 100;
                Log::debug("[GigFinancialCalculatorService] Comissão Booker (Fallback Percentual) para Gig ID {$gig->id}: {$rate}% de Cachê Bruto BRL {$grossCashBrl} = {$commission}");
            } else {
                Log::warning("[GigFinancialCalculatorService] Tipo de comissão do Booker inválido para Gig ID {$gig->id}, resultando em comissão zero.");
            }
        }

        return (float) max(0, $commission);
    }

    /**
     * Calcula a "Comissão Líquida da Agência".
     * Fórmula: Comissão Bruta da Agência - Comissão do Booker.
     *
     * @param  Gig  $gig  O modelo da Gig.
     * @return float O valor da comissão líquida da agência em BRL.
     */
    public function calculateAgencyNetCommissionBrl(Gig $gig): float
    {
        $agencyGrossCommissionBrl = $this->calculateAgencyGrossCommissionBrl($gig);
        $bookerCommissionBrl = $this->calculateBookerCommissionBrl($gig);
        $agencyNetCommissionBrl = $agencyGrossCommissionBrl - $bookerCommissionBrl;

        Log::debug("[GigFinancialCalculatorService] Comissão Líquida Agência para Gig ID {$gig->id}: Com. Ag. Bruta BRL {$agencyGrossCommissionBrl} - Com. Booker BRL {$bookerCommissionBrl} = {$agencyNetCommissionBrl}");

        return (float) $agencyNetCommissionBrl;
    }

    /**
     * Calcula o total de todas as despesas confirmadas para a Gig.
     */
    public function calculateTotalConfirmedExpensesBrl(Gig $gig): float
    {
        $gig->loadMissing('gigCosts');
        $total = $gig->gigCosts->where('is_confirmed', true)->sum('value_brl');
        Log::debug("[GigFinancialCalculatorService] Total TODAS Despesas Confirmadas BRL para Gig ID {$gig->id}: {$total}");

        return (float) $total;
    }

    /**
     * Calcula o total de despesas confirmadas E marcadas como reembolsáveis via NF do artista (is_invoice = true).
     */
    public function calculateTotalReimbursableExpensesBrl(Gig $gig): float
    {
        $gig->loadMissing('gigCosts');
        $total = $gig->gigCosts
            ->where('is_confirmed', true)
            ->where('is_invoice', true)
            ->sum('value_brl');
        Log::debug("[GigFinancialCalculatorService] Total Despesas Reembolsáveis (NF Artista) BRL para Gig ID {$gig->id}: {$total}");

        return (float) $total;
    }

    /**
     * Calcula o valor final para a Nota Fiscal do Artista.
     * Fórmula: Cachê Líquido do Artista (antes do reembolso) + Total de Despesas Reembolsáveis.
     */
    public function calculateArtistInvoiceValueBrl(Gig $gig): float // Este método já estava correto na sua lógica interna
    {
        $artistNetPayoutBeforeReimbursement = $this->calculateArtistNetPayoutBrl($gig);
        $reimbursableExpenses = $this->calculateTotalReimbursableExpensesBrl($gig);

        $invoiceValue = $artistNetPayoutBeforeReimbursement + $reimbursableExpenses;
        Log::debug("[GigFinancialCalculatorService] Valor NF Artista para Gig ID {$gig->id}: Payout Artista (antes reembolso) BRL {$artistNetPayoutBeforeReimbursement} + Desp. Reembolsáveis BRL {$reimbursableExpenses} = {$invoiceValue}");

        return (float) $invoiceValue;
    }

    /**
     * Calcula o valor total já recebido para uma Gig, na moeda original da Gig.
     * Soma o valor de todos os pagamentos confirmados que estão na mesma moeda do contrato.
     */
    public function calculateTotalReceivedInOriginalCurrency(Gig $gig): float
    {
        // Garante que o relacionamento 'payments' esteja carregado
        $gig->loadMissing('payments');

        $totalReceived = $gig->payments
            ->whereNotNull('confirmed_at')
            ->where('currency', $gig->currency) // Considera apenas pagamentos na moeda principal do contrato
            ->sum('received_value_actual'); // Soma o valor real recebido

        Log::debug("[GigFinancialCalculatorService] Calculando Total Recebido para Gig ID {$gig->id} (Moeda: {$gig->currency}): {$totalReceived}");

        return (float) $totalReceived;
    }

    /**
     * Calcula o valor total AINDA A RECEBER, somando as parcelas pendentes.
     * Soma o valor devido (due_value) de todos os pagamentos não confirmados.
     */
    public function calculateTotalReceivableInOriginalCurrency(Gig $gig): float
    {
        $gig->loadMissing('payments');

        $totalReceivable = $gig->payments
            ->whereNull('confirmed_at')
            ->sum('due_value');

        Log::debug("[GigFinancialCalculatorService] Calculando Total A RECEBER (parcelas pendentes) para Gig ID {$gig->id}: {$totalReceivable}");

        return (float) $totalReceivable;
    }

    /**
     * Calcula o saldo pendente a ser recebido para uma Gig, na moeda original da Gig.
     * Fórmula: Valor do Contrato (Original) - Total Recebido (na Moeda Original).
     */
    public function calculatePendingBalanceInOriginalCurrency(Gig $gig): float
    {
        $contractValue = (float) ($gig->cache_value ?? 0);
        $totalReceived = $this->calculateTotalReceivedInOriginalCurrency($gig);

        $pendingBalance = $contractValue - $totalReceived;

        Log::debug("[GigFinancialCalculatorService] Calculando Saldo Pendente para Gig ID {$gig->id} (Moeda: {$gig->currency}): Contrato {$contractValue} - Recebido {$totalReceived} = {$pendingBalance}");

        return (float) max(0, $pendingBalance); // O saldo pendente não deve ser negativo
    }
}
