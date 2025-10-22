<?php

namespace App\Services;

use App\Models\Gig;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AuditService
{
    protected GigFinancialCalculatorService $financialCalculator;

    public function __construct(GigFinancialCalculatorService $financialCalculator)
    {
        $this->financialCalculator = $financialCalculator;
    }

    /**
     * Calcula dados de auditoria para uma coleção de gigs.
     */
    public function calculateBulkAuditData(Collection $gigs): array
    {
        $auditData = [];

        foreach ($gigs as $gig) {
            $auditData[$gig->id] = $this->calculateGigAuditData($gig);
        }

        return $auditData;
    }

    /**
     * Calcula os dados de auditoria para uma gig específica.
     * Implementa a fórmula: Divergência = Valor do Contrato - (Total Pago + Total Pendente)
     */
    public function calculateGigAuditData(Gig $gig): array
    {
        try {
            // Garantir que os relacionamentos estão carregados
            if (! $gig->relationLoaded('payments')) {
                $gig->load('payments');
            }

            // Valor do contrato na moeda original
            $valorContrato = (float) ($gig->cache_value ?? 0);

            // Calcular totais usando o serviço financeiro
            $totalPago = $this->calculateTotalPaid($gig);
            $totalPendente = $this->calculateTotalPending($gig);

            // Cálculo da divergência principal
            $divergencia = $valorContrato - ($totalPago + $totalPendente);

            // Análises adicionais
            $analiseDetalhada = $this->performDetailedAnalysis($gig, $valorContrato, $totalPago, $totalPendente);

            // Observações automáticas
            $observacao = $this->generateAutomaticObservations($gig, $divergencia, $totalPago, $totalPendente, $valorContrato, $analiseDetalhada);

            // Status e classificação da divergência
            $statusDivergencia = $this->classifyDivergence($divergencia);

            // Log::debug("[AuditService] Gig ID {$gig->id}: Contrato={$valorContrato}, Pago={$totalPago}, Pendente={$totalPendente}, Divergência={$divergencia}");

            return [
                'valor_contrato' => $valorContrato,
                'total_pago' => $totalPago,
                'total_pendente' => $totalPendente,
                'divergencia' => $divergencia,
                'divergencia_percentual' => $valorContrato > 0 ? ($divergencia / $valorContrato) * 100 : 0,
                'observacao' => $observacao,
                'tem_divergencia' => abs($divergencia) > 0.01,
                'status_divergencia' => $statusDivergencia,
                'analise_detalhada' => $analiseDetalhada,
                'ultima_atualizacao' => now()->format('d/m/Y H:i:s'),
            ];

        } catch (Exception $e) {
            Log::error("[AuditService] Erro ao calcular auditoria para Gig ID {$gig->id}: ".$e->getMessage());

            return $this->getErrorAuditData($gig->id, $e->getMessage());
        }
    }

    /**
     * Calcula o total já pago (confirmado) na moeda original.
     */
    private function calculateTotalPaid(Gig $gig): float
    {
        return $this->financialCalculator->calculateTotalReceivedInOriginalCurrency($gig);
    }

    /**
     * Calcula o total ainda pendente na moeda original.
     */
    private function calculateTotalPending(Gig $gig): float
    {
        return $this->financialCalculator->calculateTotalReceivableInOriginalCurrency($gig);
    }

    /**
     * Realiza análise detalhada dos dados financeiros.
     */
    private function performDetailedAnalysis(Gig $gig, float $valorContrato, float $totalPago, float $totalPendente): array
    {
        $payments = $gig->payments;

        // Análise de pagamentos
        $pagamentosConfirmados = $payments->whereNotNull('confirmed_at')->count();
        $pagamentosPendentes = $payments->whereNull('confirmed_at')->count();
        $pagamentosVencidos = $payments->whereNull('confirmed_at')
            ->where('due_date', '<', now())
            ->count();

        // Análise temporal
        $proximoVencimento = $payments->whereNull('confirmed_at')
            ->where('due_date', '>=', now())
            ->min('due_date');

        $ultimoPagamento = $payments->whereNotNull('confirmed_at')
            ->max('confirmed_at');

        // Análise de moedas
        $moedasEnvolvidas = $payments->pluck('currency')->unique()->values()->toArray();
        $temMultiplasMoedas = count($moedasEnvolvidas) > 1;

        // Percentual de conclusão
        $percentualPago = $valorContrato > 0 ? ($totalPago / $valorContrato) * 100 : 0;

        return [
            'pagamentos_confirmados' => $pagamentosConfirmados,
            'pagamentos_pendentes' => $pagamentosPendentes,
            'pagamentos_vencidos' => $pagamentosVencidos,
            'proximo_vencimento' => $proximoVencimento ? Carbon::parse($proximoVencimento)->format('d/m/Y') : null,
            'ultimo_pagamento' => $ultimoPagamento ? Carbon::parse($ultimoPagamento)->format('d/m/Y') : null,
            'moedas_envolvidas' => $moedasEnvolvidas,
            'tem_multiplas_moedas' => $temMultiplasMoedas,
            'percentual_pago' => round($percentualPago, 2),
            'total_pagamentos' => $payments->count(),
        ];
    }

    /**
     * Gera observações automáticas baseadas na análise dos dados.
     */
    private function generateAutomaticObservations(Gig $gig, float $divergencia, float $totalPago, float $totalPendente, float $valorContrato, array $analiseDetalhada): string
    {
        $observacoes = [];

        // Análise principal da divergência
        if (abs($divergencia) <= 0.01) {
            $observacoes[] = '✓ Valores conferem';
        } elseif ($divergencia > 0) {
            $observacoes[] = '⚠ Falta receber '.$gig->currency.' '.number_format($divergencia, 2, ',', '.');
        } else {
            $observacoes[] = '⚠ Excesso de '.$gig->currency.' '.number_format(abs($divergencia), 2, ',', '.');
        }

        // Status de pagamento
        if ($totalPago == 0 && $totalPendente == 0) {
            $observacoes[] = '❌ Nenhum pagamento registrado';
        } elseif ($totalPago == $valorContrato) {
            $observacoes[] = '✓ Totalmente pago';
        } elseif ($totalPago > 0 && $totalPendente > 0) {
            $percentual = round(($totalPago / $valorContrato) * 100, 1);
            $observacoes[] = "🔄 Pagamento parcial ({$percentual}%)";
        }

        // Alertas de vencimento
        if ($analiseDetalhada['pagamentos_vencidos'] > 0) {
            $observacoes[] = "🔴 {$analiseDetalhada['pagamentos_vencidos']} pagamento(s) vencido(s)";
        }

        // Próximo vencimento
        if ($analiseDetalhada['proximo_vencimento']) {
            $proximoVencimento = Carbon::createFromFormat('d/m/Y', $analiseDetalhada['proximo_vencimento']);
            $diasParaVencimento = now()->diffInDays($proximoVencimento, false);

            if ($diasParaVencimento <= 7 && $diasParaVencimento >= 0) {
                $observacoes[] = "⏰ Vencimento em {$diasParaVencimento} dia(s)";
            }
        }

        // Múltiplas moedas
        if ($analiseDetalhada['tem_multiplas_moedas']) {
            $moedas = implode(', ', $analiseDetalhada['moedas_envolvidas']);
            $observacoes[] = "🌍 Múltiplas moedas: {$moedas}";
        }

        return implode(' | ', $observacoes);
    }

    /**
     * Classifica o tipo de divergência para estilização.
     */
    private function classifyDivergence(float $divergencia): string
    {
        if (abs($divergencia) <= 0.01) {
            return 'ok'; // Verde - valores conferem
        } elseif ($divergencia > 0) {
            return 'falta'; // Amarelo/Laranja - falta receber
        } else {
            return 'excesso'; // Vermelho - excesso pago
        }
    }

    /**
     * Retorna dados de auditoria para casos de erro.
     */
    private function getErrorAuditData(int $gigId, string $errorMessage): array
    {
        return [
            'valor_contrato' => 0,
            'total_pago' => 0,
            'total_pendente' => 0,
            'divergencia' => 0,
            'divergencia_percentual' => 0,
            'observacao' => '❌ Erro no cálculo: '.$errorMessage,
            'tem_divergencia' => false,
            'status_divergencia' => 'erro',
            'analise_detalhada' => [],
            'ultima_atualizacao' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Gera relatório de auditoria consolidado.
     */
    public function generateConsolidatedReport(Collection $gigs): array
    {
        $auditData = $this->calculateBulkAuditData($gigs);

        $totalGigs = $gigs->count();
        $gigsComDivergencia = collect($auditData)->where('tem_divergencia', true)->count();
        $totalDivergencia = collect($auditData)->sum('divergencia');
        $totalContrato = collect($auditData)->sum('valor_contrato');
        $totalPago = collect($auditData)->sum('total_pago');
        $totalPendente = collect($auditData)->sum('total_pendente');

        // Estatísticas por status
        $statusStats = collect($auditData)->groupBy('status_divergencia')->map->count();

        return [
            'resumo' => [
                'total_gigs' => $totalGigs,
                'gigs_com_divergencia' => $gigsComDivergencia,
                'percentual_divergencia' => $totalGigs > 0 ? round(($gigsComDivergencia / $totalGigs) * 100, 2) : 0,
                'total_divergencia' => $totalDivergencia,
                'total_contrato' => $totalContrato,
                'total_pago' => $totalPago,
                'total_pendente' => $totalPendente,
            ],
            'estatisticas_status' => $statusStats,
            'dados_detalhados' => $auditData,
            'gerado_em' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Valida a integridade dos dados de uma gig para auditoria.
     */
    public function validateGigIntegrity(Gig $gig): array
    {
        $issues = [];

        // Verificar se tem valor de contrato
        if (! $gig->cache_value || $gig->cache_value <= 0) {
            $issues[] = 'Valor do contrato não definido ou inválido';
        }

        // Verificar se tem moeda definida
        if (! $gig->currency) {
            $issues[] = 'Moeda não definida';
        }

        // Verificar se tem pagamentos registrados
        if ($gig->payments->isEmpty()) {
            $issues[] = 'Nenhum pagamento registrado';
        }

        // Verificar consistência de moedas
        $gigCurrency = $gig->currency;
        $paymentCurrencies = $gig->payments->pluck('currency')->unique();

        if ($paymentCurrencies->contains(function ($currency) use ($gigCurrency) {
            return $currency !== $gigCurrency;
        })) {
            $issues[] = 'Inconsistência de moedas entre contrato e pagamentos';
        }

        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
            'validated_at' => now()->format('d/m/Y H:i:s'),
        ];
    }
}
