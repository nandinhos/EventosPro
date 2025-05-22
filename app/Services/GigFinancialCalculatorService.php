<?php

namespace App\Services;

use App\Models\Gig;
use App\Models\GigCost; // Usaremos para somar despesas
use Illuminate\Support\Facades\Log;

/**
 * Classe de serviço responsável por todos os cálculos financeiros
 * relacionados a uma Gig.
 * Assegura que as regras de negócio para cachês, despesas e comissões
 * sejam aplicadas de forma centralizada e consistente.
 */
class GigFinancialCalculatorService
{
    /**
     * Calcula o "Cachê Bruto" da Gig.
     * Fórmula: Valor do Contrato (cache_value da Gig) - Despesas Pagas pela Agência.
     * Despesas Pagas pela Agência são aquelas GigCosts confirmadas onde 'is_invoice' é false.
     *
     * @param Gig $gig O modelo da Gig.
     * @return float O valor do cachê bruto em BRL (após conversão da moeda original da Gig e dedução das despesas da agência).
     */
    public function calculateGrossCashBrl(Gig $gig): float
    {
        // Carregar custos necessários se não estiverem carregados para evitar N+1 em alguns cenários
        $gig->loadMissing('costs.costCenter');

        // 1. Obter o valor do contrato da Gig em BRL
        // O accessor 'cache_value_brl' no modelo Gig já faz a conversão da moeda original para BRL
        // usando a taxa de câmbio (se aplicável e disponível).
        $contractValueBrl = $gig->cache_value_brl; // (Lógica de conversão está no accessor)

        // 2. Somar as despesas confirmadas que foram pagas pela agência (is_invoice = false)
        // Assumimos que as despesas em gig_costs já estão em BRL ou precisam ser convertidas.
        // Por ora, o GigCost armazena 'value' e 'currency'. Se currency != BRL, precisaríamos de conversão aqui também.
        // Vamos simplificar por agora e assumir que GigCost.value já está em BRL para despesas.
        // Se não, precisaríamos de um service de conversão de moeda ou um accessor em GigCost.
        $agencyPaidExpensesBrl = $gig->costs
            ->where('is_confirmed', true)
            ->where('is_invoice', false) // Despesas pagas pela agência
            ->sum('value'); // Assumindo que GigCost->value está em BRL

        $grossCashBrl = $contractValueBrl - $agencyPaidExpensesBrl;

        Log::debug("[GigFinancialCalculatorService] Calculando Cachê Bruto para Gig ID {$gig->id}: Contrato BRL {$contractValueBrl} - Desp. Agência BRL {$agencyPaidExpensesBrl} = {$grossCashBrl}");

        return (float) max(0, $grossCashBrl); // Não pode ser negativo
    }

    /**
     * Calcula a "Comissão Bruta da Agência".
     * Pode ser um percentual sobre o "Cachê Bruto" ou um valor fixo.
     *
     * @param Gig $gig O modelo da Gig.
     * @return float O valor da comissão bruta da agência em BRL.
     */
    public function calculateAgencyGrossCommissionBrl(Gig $gig): float
    {
        $grossCashBrl = $this->calculateGrossCashBrl($gig); // Base de cálculo

        $commission = 0.0;

        if (strtoupper($gig->agency_commission_type ?? '') === 'PERCENT') {
            $rate = (float) ($gig->agency_commission_rate ?? 20.0); // Default 20%
            $commission = ($grossCashBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Agência (Percentual) para Gig ID {$gig->id}: {$rate}% de {$grossCashBrl} = {$commission}");
        } elseif (strtoupper($gig->agency_commission_type ?? '') === 'FIXED') {
            $commission = (float) ($gig->agency_commission_value ?? 0.0); // Valor fixo (já deve estar em BRL)
            Log::debug("[GigFinancialCalculatorService] Comissão Agência (Fixa) para Gig ID {$gig->id}: {$commission}");
        } else {
            // Fallback para percentual se o tipo não estiver definido ou for inválido, usando a taxa padrão.
            // Você mencionou 20% do "Valor do Contrato", mas agora é sobre o "Cachê Bruto".
            $rate = (float) ($gig->agency_commission_rate ?? 20.0);
            $commission = ($grossCashBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Agência (Fallback Percentual) para Gig ID {$gig->id}: {$rate}% de {$grossCashBrl} = {$commission}");
        }
        return (float) max(0, $commission);
    }

    /**
     * Calcula o "Cachê Líquido do Artista".
     * Fórmula: Cachê Bruto - Comissão Bruta da Agência.
     * Nota: Despesas reembolsáveis ao artista (is_invoice = true) são somadas a este valor no momento da NF/Acerto.
     *
     * @param Gig $gig O modelo da Gig.
     * @return float O valor do cachê líquido do artista em BRL.
     */
    public function calculateArtistNetPayoutBrl(Gig $gig): float
    {
        $grossCashBrl = $this->calculateGrossCashBrl($gig);
        $agencyGrossCommissionBrl = $this->calculateAgencyGrossCommissionBrl($gig);

        // Se a comissão do artista for explicitamente definida (ex: 80% da base, ou um valor fixo para o artista)
        // a lógica seria diferente. Seguindo "Cachê Bruto - Comissão Bruta da Agência":
        $artistNetPayoutBrl = $grossCashBrl - $agencyGrossCommissionBrl;

        Log::debug("[GigFinancialCalculatorService] Cachê Líquido Artista para Gig ID {$gig->id}: Cachê Bruto BRL {$grossCashBrl} - Com. Agência BRL {$agencyGrossCommissionBrl} = {$artistNetPayoutBrl}");

        return (float) max(0, $artistNetPayoutBrl);
    }

    /**
     * Calcula a "Comissão do Booker".
     * Pode ser um percentual sobre o "Valor do Contrato" original da Gig ou um valor fixo.
     *
     * @param Gig $gig O modelo da Gig.
     * @return float O valor da comissão do booker em BRL.
     */
    public function calculateBookerCommissionBrl(Gig $gig): float
    {
        if (!$gig->booker_id) {
            return 0.0;
        }

        // A base para a comissão do booker é o "Valor do Contrato" original da Gig, convertido para BRL.
        $contractValueBrl = $gig->cache_value_brl; // Usa o accessor que já converte para BRL

        $commission = 0.0;

        if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT') {
            $rate = (float) ($gig->booker_commission_rate ?? 5.0); // Default 5% sobre o VALOR DO CONTRATO ORIGINAL
            $commission = ($contractValueBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Booker (Percentual) para Gig ID {$gig->id}: {$rate}% de Contrato BRL {$contractValueBrl} = {$commission}");
        } elseif (strtoupper($gig->booker_commission_type ?? '') === 'FIXED') {
            $commission = (float) ($gig->booker_commission_value ?? 0.0); // Valor fixo (já deve estar em BRL)
            Log::debug("[GigFinancialCalculatorService] Comissão Booker (Fixa) para Gig ID {$gig->id}: {$commission}");
        } else {
            // Fallback para percentual se booker existir mas tipo não definido/inválido.
            // A regra é "5% do valor do contrato que ele mesmo indicou".
            // Se o tipo não é 'fixed', assumimos 'percent' com a taxa padrão.
            $rate = (float) ($gig->booker_commission_rate ?? 5.0);
            $commission = ($contractValueBrl * $rate) / 100;
            Log::debug("[GigFinancialCalculatorService] Comissão Booker (Fallback Percentual) para Gig ID {$gig->id}: {$rate}% de Contrato BRL {$contractValueBrl} = {$commission}");
        }
        return (float) max(0, $commission);
    }

    /**
     * Calcula a "Comissão Líquida da Agência".
     * Fórmula: Comissão Bruta da Agência - Comissão do Booker.
     *
     * @param Gig $gig O modelo da Gig.
     * @return float O valor da comissão líquida da agência em BRL.
     */
    public function calculateAgencyNetCommissionBrl(Gig $gig): float
    {
        $agencyGrossCommissionBrl = $this->calculateAgencyGrossCommissionBrl($gig);
        $bookerCommissionBrl = $this->calculateBookerCommissionBrl($gig);

        $agencyNetCommissionBrl = $agencyGrossCommissionBrl - $bookerCommissionBrl;

        Log::debug("[GigFinancialCalculatorService] Comissão Líquida Agência para Gig ID {$gig->id}: Com. Ag. Bruta BRL {$agencyGrossCommissionBrl} - Com. Booker BRL {$bookerCommissionBrl} = {$agencyNetCommissionBrl}");

        return (float) $agencyNetCommissionBrl; // Pode ser negativa se comissão do booker for maior
    }

    /**
     * Calcula o total de despesas confirmadas para a Gig.
     * Soma o valor de todas as GigCosts onde is_confirmed = true.
     * Assume que os valores em GigCost já estão em BRL ou foram convertidos adequadamente.
     *
     * @param Gig $gig
     * @return float
     */
    public function calculateTotalConfirmedExpensesBrl(Gig $gig): float
    {
        $gig->loadMissing('costs'); // Garante que os custos estejam carregados
        // Assumindo que GigCost->value está em BRL
        $total = $gig->costs->where('is_confirmed', true)->sum('value');
        Log::debug("[GigFinancialCalculatorService] Total Despesas Confirmadas BRL para Gig ID {$gig->id}: {$total}");
        return (float) $total;
    }

    /**
     * Calcula o total de despesas confirmadas E marcadas como reembolsáveis via NF do artista.
     *
     * @param Gig $gig
     * @return float
     */
    public function calculateTotalReimbursableExpensesBrl(Gig $gig): float
    {
        $gig->loadMissing('costs');
        // Assumindo que GigCost->value está em BRL
        $total = $gig->costs
            ->where('is_confirmed', true)
            ->where('is_invoice', true) // Despesas que o artista pagou e serão reembolsadas na NF
            ->sum('value');
        Log::debug("[GigFinancialCalculatorService] Total Despesas Reembolsáveis (NF Artista) BRL para Gig ID {$gig->id}: {$total}");
        return (float) $total;
    }

    /**
     * Calcula o valor final para a Nota Fiscal do Artista.
     * Fórmula: Cachê Líquido do Artista + Total de Despesas Reembolsáveis.
     *
     * @param Gig $gig
     * @return float
     */
    public function calculateArtistInvoiceValueBrl(Gig $gig): float
    {
        $artistNetPayout = $this->calculateArtistNetPayoutBrl($gig);
        $reimbursableExpenses = $this->calculateTotalReimbursableExpensesBrl($gig);

        $invoiceValue = $artistNetPayout + $reimbursableExpenses;
        Log::debug("[GigFinancialCalculatorService] Valor NF Artista para Gig ID {$gig->id}: Payout Artista BRL {$artistNetPayout} + Desp. Reembolsáveis BRL {$reimbursableExpenses} = {$invoiceValue}");
        return (float) $invoiceValue;
    }
}