<?php

namespace App\Console\Commands;

use App\Models\Gig;
use App\Services\ExchangeRateService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditCurrencyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-currency
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--auto-fix : Corrigir automaticamente sem confirmações}
                            {--batch-size=100 : Tamanho do lote para processamento}
                            {--date-from= : Data inicial para filtrar gigs (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar gigs (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria de consistência de moedas - valida moedas entre gig, payments e costs';

    protected array $issues = [];

    protected array $stats = [
        'total_gigs' => 0,
        'issues_found' => 0,
        'corrections_applied' => 0,
        'errors' => 0,
    ];

    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        parent::__construct();
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Moedas');
        $this->info('================================');

        // Configurações
        $scanOnly = $this->option('scan-only');
        $autoFix = $this->option('auto-fix');
        $batchSize = (int) $this->option('batch-size');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        // Validar parâmetros
        if ($autoFix && $scanOnly) {
            $this->error('❌ Não é possível usar --scan-only e --auto-fix simultaneamente');

            return 1;
        }

        // Mostrar configurações
        $this->displayConfiguration($scanOnly, $autoFix, $batchSize, $dateFrom, $dateTo);

        // Confirmar execução
        if (! $autoFix && ! $this->confirmExecution()) {
            $this->info('⏹️  Operação cancelada pelo usuário');

            return 0;
        }

        try {
            // Executar auditoria
            $this->performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix);

            // Mostrar relatório final
            $this->displayFinalReport();

            return 0;
        } catch (Exception $e) {
            $this->error("❌ Erro durante a execução: {$e->getMessage()}");
            Log::error('AuditCurrency Error', ['exception' => $e]);

            return 1;
        }
    }

    protected function displayConfiguration($scanOnly, $autoFix, $batchSize, $dateFrom, $dateTo)
    {
        $this->info('📋 Configurações:');
        $this->line('   Modo: '.($scanOnly ? 'Apenas Escaneamento' : ($autoFix ? 'Correção Automática' : 'Correção Interativa')));
        $this->line("   Tamanho do lote: {$batchSize}");

        if ($dateFrom) {
            $this->line("   Data inicial: {$dateFrom}");
        }
        if ($dateTo) {
            $this->line("   Data final: {$dateTo}");
        }

        $this->newLine();
    }

    protected function confirmExecution(): bool
    {
        if (! defined('STDIN') || ! is_resource(STDIN)) {
            return true;
        }

        $this->warn('⚠️  ATENÇÃO: Esta operação irá validar moedas e potencialmente sincronizar valores.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Construir query base
        $query = Gig::with(['payments', 'gigCosts.costCenter', 'artist', 'booker']);

        if ($dateFrom) {
            $query->where('gig_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('gig_date', '<=', $dateTo);
        }

        // Contar total de registros
        $this->stats['total_gigs'] = $query->count();
        $this->info("📊 Total de gigs para análise: {$this->stats['total_gigs']}");
        $this->newLine();

        if ($this->stats['total_gigs'] === 0) {
            $this->warn('⚠️  Nenhuma gig encontrada para os critérios especificados.');

            return;
        }

        // Processar em lotes
        $progressBar = $this->output->createProgressBar($this->stats['total_gigs']);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $query->chunk($batchSize, function ($gigs) use ($scanOnly, $autoFix, $progressBar) {
            foreach ($gigs as $gig) {
                $this->auditGigCurrency($gig, $scanOnly, $autoFix);
                $progressBar->advance();
            }

            unset($gigs);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function auditGigCurrency(Gig $gig, $scanOnly, $autoFix)
    {
        try {
            $gigIssues = [];

            // 1. Verificar se gig tem moeda definida
            $this->checkGigCurrency($gig, $gigIssues);

            // Se não tem moeda, pular outras validações
            if (! $gig->currency) {
                $this->recordIssues($gig, $gigIssues, $scanOnly, $autoFix);

                return;
            }

            // 2. Verificar moedas dos payments vs gig
            $this->checkPaymentsCurrency($gig, $gigIssues);

            // 3. Verificar moedas dos costs vs gig
            $this->checkCostsCurrency($gig, $gigIssues);

            // 4. Verificar múltiplas moedas nos payments
            $this->checkMultipleCurrenciesInPayments($gig, $gigIssues);

            // 5. Verificar múltiplas moedas nos costs
            $this->checkMultipleCurrenciesInCosts($gig, $gigIssues);

            // 6. Verificar moedas não suportadas
            $this->checkUnsupportedCurrencies($gig, $gigIssues);

            // Registrar issues encontradas
            $this->recordIssues($gig, $gigIssues, $scanOnly, $autoFix);

        } catch (Exception $e) {
            $this->stats['errors']++;
            Log::error("Erro ao auditar Currency da Gig ID {$gig->id}", ['exception' => $e]);
        }
    }

    protected function checkGigCurrency(Gig $gig, array &$issues)
    {
        if (! $gig->currency || trim($gig->currency) === '') {
            $issues[] = [
                'type' => 'missing_currency',
                'severity' => 'critical',
                'description' => 'Gig sem moeda definida',
                'field' => 'currency',
                'current_value' => null,
                'suggested_value' => 'BRL',
                'suggested_action' => 'Definir moeda padrão (BRL)',
                'can_auto_fix' => true,
            ];
        }
    }

    protected function checkPaymentsCurrency(Gig $gig, array &$issues)
    {
        $gigCurrency = $gig->currency;

        foreach ($gig->payments as $payment) {
            if ($payment->currency && $payment->currency !== $gigCurrency) {
                $issues[] = [
                    'type' => 'payment_currency_mismatch',
                    'severity' => 'critical',
                    'description' => "Payment #{$payment->id} com moeda diferente da gig",
                    'field' => 'payments.currency',
                    'current_value' => $payment->currency,
                    'suggested_value' => $gigCurrency,
                    'suggested_action' => 'Sincronizar moeda do payment com a gig',
                    'can_auto_fix' => true,
                    'payment_id' => $payment->id,
                ];
            }
        }
    }

    protected function checkCostsCurrency(Gig $gig, array &$issues)
    {
        $gigCurrency = $gig->currency;

        foreach ($gig->gigCosts as $cost) {
            if ($cost->currency && $cost->currency !== $gigCurrency) {
                $issues[] = [
                    'type' => 'cost_currency_mismatch',
                    'severity' => 'warning',
                    'description' => "GigCost #{$cost->id} com moeda diferente da gig",
                    'field' => 'costs.currency',
                    'current_value' => $cost->currency,
                    'suggested_value' => $gigCurrency,
                    'suggested_action' => 'Verificar se custo deve estar na moeda da gig ou manter original',
                    'can_auto_fix' => false,
                    'cost_id' => $cost->id,
                ];
            }
        }
    }

    protected function checkMultipleCurrenciesInPayments(Gig $gig, array &$issues)
    {
        $currencies = $gig->payments->pluck('currency')->unique()->filter()->values();

        if ($currencies->count() > 1) {
            $currencyList = $currencies->implode(', ');

            $issues[] = [
                'type' => 'multiple_currencies_payments',
                'severity' => 'warning',
                'description' => "Payments com múltiplas moedas: {$currencyList}",
                'field' => 'payments.currency',
                'current_value' => $currencyList,
                'suggested_action' => 'Padronizar moeda dos payments',
                'can_auto_fix' => false,
            ];
        }
    }

    protected function checkMultipleCurrenciesInCosts(Gig $gig, array &$issues)
    {
        $currencies = $gig->gigCosts()->pluck('currency')->unique()->filter()->values();

        if ($currencies->count() > 1) {
            $currencyList = $currencies->implode(', ');

            $issues[] = [
                'type' => 'multiple_currencies_costs',
                'severity' => 'warning',
                'description' => "Costs com múltiplas moedas: {$currencyList}",
                'field' => 'costs.currency',
                'current_value' => $currencyList,
                'suggested_action' => 'Revisar moedas dos custos',
                'can_auto_fix' => false,
            ];
        }
    }

    protected function checkUnsupportedCurrencies(Gig $gig, array &$issues)
    {
        $supportedCurrencies = ['BRL', 'USD', 'EUR', 'GBP'];

        // Verificar moeda da gig
        if ($gig->currency && ! in_array($gig->currency, $supportedCurrencies)) {
            $issues[] = [
                'type' => 'unsupported_currency',
                'severity' => 'warning',
                'description' => "Moeda da gig não suportada: {$gig->currency}",
                'field' => 'currency',
                'current_value' => $gig->currency,
                'suggested_action' => 'Verificar se moeda está correta',
                'can_auto_fix' => false,
            ];
        }

        // Verificar moedas dos payments
        foreach ($gig->payments as $payment) {
            if ($payment->currency && ! in_array($payment->currency, $supportedCurrencies)) {
                $issues[] = [
                    'type' => 'unsupported_currency',
                    'severity' => 'warning',
                    'description' => "Payment #{$payment->id} com moeda não suportada: {$payment->currency}",
                    'field' => 'payments.currency',
                    'current_value' => $payment->currency,
                    'suggested_action' => 'Verificar moeda do payment',
                    'can_auto_fix' => false,
                    'payment_id' => $payment->id,
                ];
            }
        }
    }

    protected function recordIssues(Gig $gig, array $gigIssues, $scanOnly, $autoFix)
    {
        if (! empty($gigIssues)) {
            $this->stats['issues_found'] += count($gigIssues);

            $this->issues[] = [
                'gig_id' => $gig->id,
                'gig_date' => $gig->gig_date->format('Y-m-d'),
                'artist' => $gig->artist->name ?? 'N/A',
                'contract_number' => $gig->contract_number ?? 'N/A',
                'gig_currency' => $gig->currency ?? 'NULL',
                'issues' => $gigIssues,
            ];

            if (! $scanOnly) {
                $this->processIssues($gig, $gigIssues, $autoFix);
            }
        }
    }

    protected function processIssues(Gig $gig, array $gigIssues, $autoFix)
    {
        foreach ($gigIssues as $issue) {
            if ($issue['severity'] === 'critical' && ($issue['can_auto_fix'] ?? false)) {
                if ($autoFix || $this->confirmFix($gig, $issue)) {
                    $this->applyFix($gig, $issue);
                }
            }
        }
    }

    protected function confirmFix(Gig $gig, array $issue): bool
    {
        if (! defined('STDIN') || ! is_resource(STDIN)) {
            return false;
        }

        $this->newLine();
        $this->warn("🔧 Correção disponível para Gig ID {$gig->id}:");
        $this->line("   Problema: {$issue['description']}");
        $this->line("   Campo: {$issue['field']}");
        $this->line('   Valor atual: '.($issue['current_value'] ?? 'N/A'));
        if (isset($issue['suggested_value'])) {
            $this->line("   Valor sugerido: {$issue['suggested_value']}");
        }

        return $this->confirm('Aplicar correção?', false);
    }

    protected function applyFix(Gig $gig, array $issue)
    {
        try {
            DB::beginTransaction();

            $message = '';

            switch ($issue['type']) {
                case 'missing_currency':
                    $gig->currency = $issue['suggested_value'];
                    $gig->save();
                    $message = "✅ Gig {$gig->id}: Moeda definida como {$issue['suggested_value']}";
                    break;

                case 'payment_currency_mismatch':
                    $payment = $gig->payments()->find($issue['payment_id']);
                    if ($payment) {
                        $payment->currency = $issue['suggested_value'];
                        $payment->save();
                        $message = "✅ Payment {$payment->id}: Moeda sincronizada para {$issue['suggested_value']}";
                    }
                    break;

                default:
                    DB::rollBack();

                    return;
            }

            DB::commit();
            $this->stats['corrections_applied']++;
            $this->info($message);

        } catch (Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;
            $this->error("❌ Erro ao aplicar correção: {$e->getMessage()}");
        }
    }

    protected function displayFinalReport()
    {
        $this->newLine();
        $this->info('📊 RELATÓRIO FINAL - AUDITORIA DE MOEDAS');
        $this->info('=========================================');
        $this->line("Total de gigs analisadas: {$this->stats['total_gigs']}");
        $this->line("Issues encontradas: {$this->stats['issues_found']}");
        $this->line("Correções aplicadas: {$this->stats['corrections_applied']}");
        $this->line("Erros durante execução: {$this->stats['errors']}");

        if (! empty($this->issues)) {
            $this->newLine();
            $this->warn('🚨 RESUMO DOS PROBLEMAS ENCONTRADOS:');

            $issuesByType = $this->groupIssuesByType();

            foreach ($issuesByType as $type => $data) {
                $emoji = $this->getSeverityEmoji($data['severity']);
                $this->line("{$emoji} {$type}: {$data['count']} ocorrências");
            }

            $this->saveDetailedReport();
        }

        $this->newLine();
        $this->info('✅ Auditoria de Moedas concluída!');
    }

    protected function groupIssuesByType(): array
    {
        $grouped = [];

        foreach ($this->issues as $gigIssue) {
            foreach ($gigIssue['issues'] as $issue) {
                $type = $issue['type'];
                $severity = $issue['severity'];

                if (! isset($grouped[$type])) {
                    $grouped[$type] = [
                        'count' => 0,
                        'severity' => $severity,
                    ];
                }

                $grouped[$type]['count']++;
            }
        }

        return $grouped;
    }

    protected function getSeverityEmoji($severity): string
    {
        return match ($severity) {
            'critical' => '🔴',
            'error' => '🟠',
            'warning' => '🟡',
            default => 'ℹ️'
        };
    }

    protected function saveDetailedReport()
    {
        $reportPath = storage_path('logs/audit_currency_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'command' => 'gig:audit-currency',
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
