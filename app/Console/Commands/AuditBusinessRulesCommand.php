<?php

namespace App\Console\Commands;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditBusinessRulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-business-rules
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--auto-fix : Corrigir automaticamente sem confirmações}
                            {--batch-size=100 : Tamanho do lote para processamento}
                            {--date-from= : Data inicial para filtrar gigs (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar gigs (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria de regras de negócio complexas - valida comissões, settlements e lógica financeira';

    protected array $issues = [];

    protected array $stats = [
        'total_gigs' => 0,
        'issues_found' => 0,
        'corrections_applied' => 0,
        'errors' => 0,
    ];

    protected GigFinancialCalculatorService $financialCalculator;

    public function __construct(GigFinancialCalculatorService $financialCalculator)
    {
        parent::__construct();
        $this->financialCalculator = $financialCalculator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Regras de Negócio');
        $this->info('===========================================');

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
            Log::error('AuditBusinessRules Error', ['exception' => $e]);

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

        $this->warn('⚠️  ATENÇÃO: Esta operação irá validar regras de negócio e potencialmente recalcular valores.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Construir query base
        $query = Gig::with(['artist', 'booker', 'payments', 'settlement', 'gigCosts']);

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
                $this->auditGigBusinessRules($gig, $scanOnly, $autoFix);
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

    protected function auditGigBusinessRules(Gig $gig, $scanOnly, $autoFix)
    {
        try {
            $gigIssues = [];
            $now = Carbon::now();

            // 1. Validar comissão da agência vs valor do cachê
            $this->checkAgencyCommissionVsCacheValue($gig, $gigIssues);

            // 2. Validar comissão do booker vs valor do cachê
            $this->checkBookerCommissionVsCacheValue($gig, $gigIssues);

            // 3. Validar booker_commission sem booker
            $this->checkBookerCommissionWithoutBooker($gig, $gigIssues);

            // 4. Validar liquid_commission_value
            $this->checkLiquidCommissionValue($gig, $gigIssues);

            // 5. Validar eventos concluídos sem settlement
            $this->checkCompletedGigsWithoutSettlement($gig, $now, $gigIssues);

            // 6. Validar gigs canceladas com payments confirmados
            $this->checkCancelledGigsWithConfirmedPayments($gig, $gigIssues);

            // 7. Validar consistência de commission types e rates
            $this->checkCommissionTypesConsistency($gig, $gigIssues);

            // Registrar issues encontradas
            $this->recordIssues($gig, $gigIssues, $scanOnly, $autoFix);

        } catch (Exception $e) {
            $this->stats['errors']++;
            Log::error("Erro ao auditar Business Rules da Gig ID {$gig->id}", ['exception' => $e]);
        }
    }

    protected function checkAgencyCommissionVsCacheValue(Gig $gig, array &$issues)
    {
        $cacheValue = (float) $gig->cache_value;
        $agencyCommission = (float) $gig->agency_commission_value;

        if ($agencyCommission > $cacheValue) {
            $issues[] = [
                'type' => 'commission_exceeds_cache',
                'severity' => 'critical',
                'description' => 'Comissão da agência maior que o valor do cachê',
                'field' => 'agency_commission_value',
                'current_value' => number_format($agencyCommission, 2, '.', ''),
                'suggested_action' => 'Recalcular comissão da agência',
                'can_auto_fix' => true,
                'details' => "Cachê: R$ {$cacheValue}, Comissão: R$ {$agencyCommission}",
            ];
        }
    }

    protected function checkBookerCommissionVsCacheValue(Gig $gig, array &$issues)
    {
        if (! $gig->booker_id) {
            return;
        }

        $cacheValue = (float) $gig->cache_value;
        $bookerCommission = (float) $gig->booker_commission_value;

        if ($bookerCommission > $cacheValue) {
            $issues[] = [
                'type' => 'commission_exceeds_cache',
                'severity' => 'critical',
                'description' => 'Comissão do booker maior que o valor do cachê',
                'field' => 'booker_commission_value',
                'current_value' => number_format($bookerCommission, 2, '.', ''),
                'suggested_action' => 'Recalcular comissão do booker',
                'can_auto_fix' => true,
                'details' => "Cachê: R$ {$cacheValue}, Comissão: R$ {$bookerCommission}",
            ];
        }
    }

    protected function checkBookerCommissionWithoutBooker(Gig $gig, array &$issues)
    {
        if (! $gig->booker_id && ($gig->booker_commission_value > 0 || $gig->booker_commission_rate > 0)) {
            $issues[] = [
                'type' => 'booker_commission_without_booker',
                'severity' => 'warning',
                'description' => 'Comissão de booker definida mas sem booker atribuído',
                'field' => 'booker_commission_value',
                'current_value' => number_format($gig->booker_commission_value ?? 0, 2, '.', ''),
                'suggested_value' => '0.00',
                'suggested_action' => 'Zerar comissão de booker ou atribuir booker',
                'can_auto_fix' => true,
            ];
        }
    }

    protected function checkLiquidCommissionValue(Gig $gig, array &$issues)
    {
        $agencyCommission = (float) $gig->agency_commission_value;
        $bookerCommission = (float) $gig->booker_commission_value;
        $liquidCommission = (float) $gig->liquid_commission_value;

        $expectedLiquid = $agencyCommission - $bookerCommission;
        $divergence = abs($liquidCommission - $expectedLiquid);

        // Tolerância de R$ 0.01
        if ($divergence > 0.01) {
            $issues[] = [
                'type' => 'liquid_commission_incorrect',
                'severity' => 'critical',
                'description' => 'Comissão líquida incorreta',
                'field' => 'liquid_commission_value',
                'current_value' => number_format($liquidCommission, 2, '.', ''),
                'suggested_value' => number_format($expectedLiquid, 2, '.', ''),
                'suggested_action' => 'Recalcular comissão líquida (Agência - Booker)',
                'can_auto_fix' => true,
                'details' => "Esperado: R$ {$expectedLiquid} (R$ {$agencyCommission} - R$ {$bookerCommission})",
            ];
        }
    }

    protected function checkCompletedGigsWithoutSettlement(Gig $gig, Carbon $now, array &$issues)
    {
        $gigDate = Carbon::parse($gig->gig_date);

        // Se o evento foi há mais de 30 dias e não tem settlement
        if ($gigDate->diffInDays($now) > 30 && $gigDate->isBefore($now) && ! $gig->settlement) {
            $issues[] = [
                'type' => 'completed_without_settlement',
                'severity' => 'warning',
                'description' => 'Evento concluído há mais de 30 dias sem settlement registrado',
                'field' => 'settlement',
                'suggested_action' => 'Criar settlement para este evento',
                'can_auto_fix' => false,
                'details' => "Evento realizado em: {$gigDate->format('d/m/Y')}",
            ];
        }
    }

    protected function checkCancelledGigsWithConfirmedPayments(Gig $gig, array &$issues)
    {
        if ($gig->contract_status === 'cancelado') {
            $confirmedPayments = $gig->payments->whereNotNull('confirmed_at')->count();

            if ($confirmedPayments > 0) {
                $issues[] = [
                    'type' => 'cancelled_with_payments',
                    'severity' => 'warning',
                    'description' => "Gig cancelada mas possui {$confirmedPayments} pagamento(s) confirmado(s)",
                    'field' => 'contract_status',
                    'suggested_action' => 'Verificar status do contrato ou estornar pagamentos',
                    'can_auto_fix' => false,
                ];
            }
        }
    }

    protected function checkCommissionTypesConsistency(Gig $gig, array &$issues)
    {
        // Verificar tipo de comissão da agência
        if ($gig->agency_commission_type === 'percent') {
            if ($gig->agency_commission_rate <= 0 || $gig->agency_commission_rate > 100) {
                $issues[] = [
                    'type' => 'invalid_commission_rate',
                    'severity' => 'critical',
                    'description' => 'Taxa percentual de comissão da agência inválida',
                    'field' => 'agency_commission_rate',
                    'current_value' => $gig->agency_commission_rate,
                    'suggested_value' => '20', // Valor padrão comum para agência
                    'suggested_action' => 'Ajustar para taxa padrão de 20%',
                    'can_auto_fix' => true,
                ];
            }
        }

        // Verificar tipo de comissão do booker
        if ($gig->booker_id && $gig->booker_commission_type === 'percent') {
            if ($gig->booker_commission_rate <= 0 || $gig->booker_commission_rate > 100) {
                $issues[] = [
                    'type' => 'invalid_commission_rate',
                    'severity' => 'critical',
                    'description' => 'Taxa percentual de comissão do booker inválida',
                    'field' => 'booker_commission_rate',
                    'current_value' => $gig->booker_commission_rate,
                    'suggested_value' => '5', // Valor padrão comum para booker
                    'suggested_action' => 'Ajustar para taxa padrão de 5%',
                    'can_auto_fix' => true,
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
                case 'commission_exceeds_cache':
                case 'liquid_commission_incorrect':
                    // Recalcular usando o serviço financeiro
                    $gig->agency_commission_value = $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig);
                    $gig->booker_commission_value = $this->financialCalculator->calculateBookerCommissionBrl($gig);
                    $gig->liquid_commission_value = $gig->agency_commission_value - $gig->booker_commission_value;
                    $gig->save();
                    $message = "✅ Gig {$gig->id}: Comissões recalculadas";
                    break;

                case 'booker_commission_without_booker':
                    $gig->booker_commission_value = 0;
                    $gig->booker_commission_rate = 0;
                    $gig->save();
                    $message = "✅ Gig {$gig->id}: Comissão de booker zerada";
                    break;

                default:
                    Log::warning('Business rule issue requer atenção manual', [
                        'gig_id' => $gig->id,
                        'issue' => $issue,
                    ]);
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
        $this->info('📊 RELATÓRIO FINAL - AUDITORIA DE REGRAS DE NEGÓCIO');
        $this->info('===================================================');
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
        $this->info('✅ Auditoria de Regras de Negócio concluída!');
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
        $reportPath = storage_path('logs/audit_business_rules_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'command' => 'gig:audit-business-rules',
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
