<?php

namespace App\Console\Commands;

use App\Models\Settlement;
use App\Services\CommissionPaymentValidationService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditSettlementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-settlements
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--auto-fix : Corrigir automaticamente sem confirmações}
                            {--batch-size=100 : Tamanho do lote para processamento}
                            {--date-from= : Data inicial para filtrar settlements (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar settlements (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria de acertos financeiros (settlements) - valida consistência de pagamentos a artistas e bookers';

    protected array $issues = [];

    protected array $stats = [
        'total_settlements' => 0,
        'issues_found' => 0,
        'corrections_applied' => 0,
        'errors' => 0,
    ];

    protected GigFinancialCalculatorService $financialCalculator;

    protected CommissionPaymentValidationService $commissionValidator;

    public function __construct(
        GigFinancialCalculatorService $financialCalculator,
        CommissionPaymentValidationService $commissionValidator
    ) {
        parent::__construct();
        $this->financialCalculator = $financialCalculator;
        $this->commissionValidator = $commissionValidator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Settlements (Acertos Financeiros)');
        $this->info('===========================================================');

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

        // Confirmar execução se não for auto-fix
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
            Log::error('AuditSettlements Error', ['exception' => $e]);

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
        if (! $dateFrom && ! $dateTo) {
            $this->line('   Escopo: Todos os registros (sem filtro de data)');
        }

        $this->newLine();
    }

    protected function confirmExecution(): bool
    {
        if (! defined('STDIN') || ! is_resource(STDIN)) {
            return true;
        }

        $this->warn('⚠️  ATENÇÃO: Esta operação irá analisar settlements e potencialmente modificar dados.');
        $this->warn('   Certifique-se de ter um backup do banco de dados antes de prosseguir.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Construir query base
        $query = Settlement::with(['gig.artist', 'gig.booker']);

        if ($dateFrom) {
            $query->where('settlement_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('settlement_date', '<=', $dateTo);
        }

        // Contar total de registros
        $this->stats['total_settlements'] = $query->count();
        $this->info("📊 Total de settlements para análise: {$this->stats['total_settlements']}");
        $this->newLine();

        // Verificar se há dados para processar
        if ($this->stats['total_settlements'] === 0) {
            $this->warn('⚠️  Nenhum settlement encontrado para os critérios especificados.');

            return;
        }

        // Processar em lotes
        $progressBar = $this->output->createProgressBar($this->stats['total_settlements']);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - %message%');
        $progressBar->setMessage('Iniciando processamento...');
        $progressBar->start();

        $query->chunk($batchSize, function ($settlements) use ($scanOnly, $autoFix, $progressBar) {
            foreach ($settlements as $settlement) {
                $this->auditSettlement($settlement, $scanOnly, $autoFix);
                $progressBar->advance();
            }

            // Liberar memória
            unset($settlements);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->setMessage('Processamento concluído!');
        $progressBar->finish();
        $this->newLine(2);
    }

    protected function auditSettlement(Settlement $settlement, $scanOnly, $autoFix)
    {
        try {
            $settlementIssues = [];
            $now = Carbon::now();

            // 1. Verificar se settlement tem gig válido
            $this->checkReferentialIntegrity($settlement, $settlementIssues);

            if (! $settlement->gig) {
                // Se não tem gig, pular outras validações
                $this->recordIssues($settlement, $settlementIssues, $scanOnly, $autoFix);

                return;
            }

            $gig = $settlement->gig;

            // 2. Verificar pagamentos de artista para eventos futuros
            $this->checkArtistPaymentRules($settlement, $gig, $now, $settlementIssues);

            // 3. Verificar pagamentos de booker para eventos futuros
            $this->checkBookerPaymentRules($settlement, $gig, $now, $settlementIssues);

            // 4. Verificar divergências de valores - Artista
            $this->checkArtistValueDivergence($settlement, $gig, $settlementIssues);

            // 5. Verificar divergências de valores - Booker
            $this->checkBookerValueDivergence($settlement, $gig, $settlementIssues);

            // 6. Verificar comprovantes de pagamento
            $this->checkPaymentProofs($settlement, $settlementIssues);

            // 7. Verificar datas lógicas
            $this->checkDateLogic($settlement, $gig, $settlementIssues);

            // Registrar issues encontradas
            $this->recordIssues($settlement, $settlementIssues, $scanOnly, $autoFix);

        } catch (Exception $e) {
            $this->stats['errors']++;
            Log::error("Erro ao auditar Settlement ID {$settlement->id}", ['exception' => $e]);
        }
    }

    protected function checkReferentialIntegrity(Settlement $settlement, array &$issues)
    {
        if (! $settlement->gig_id || ! $settlement->gig) {
            $issues[] = [
                'type' => 'referential_integrity',
                'severity' => 'critical',
                'description' => 'Settlement sem gig válido',
                'field' => 'gig_id',
                'current_value' => $settlement->gig_id,
                'suggested_action' => 'Remover settlement órfão',
                'can_auto_fix' => true,
            ];
        }
    }

    protected function checkArtistPaymentRules(Settlement $settlement, $gig, Carbon $now, array &$issues)
    {
        // Se há pagamento de artista registrado
        if ($settlement->artist_payment_value > 0 && $settlement->artist_payment_paid_at) {
            $gigDate = Carbon::parse($gig->gig_date);

            // Se o evento é futuro
            if ($gigDate->isAfter($now)) {
                // Verificar se há exceção autorizada
                $validation = $this->commissionValidator->validateArtistPayment($gig, true);

                if (! $validation['valid']) {
                    $issues[] = [
                        'type' => 'payment_rule_violation',
                        'severity' => 'critical',
                        'description' => 'Pagamento de artista registrado para evento futuro sem exceção autorizada',
                        'field' => 'artist_payment_paid_at',
                        'current_value' => $settlement->artist_payment_paid_at->format('Y-m-d'),
                        'suggested_value' => null,
                        'suggested_action' => 'Reverter pagamento ou adicionar exceção autorizada',
                        'can_auto_fix' => false,
                        'details' => $validation['message'],
                    ];
                }
            }
        }
    }

    protected function checkBookerPaymentRules(Settlement $settlement, $gig, Carbon $now, array &$issues)
    {
        // Se há pagamento de booker registrado
        if ($settlement->booker_commission_value_paid > 0 && $settlement->booker_commission_paid_at) {
            $gigDate = Carbon::parse($gig->gig_date);

            // Se o evento é futuro
            if ($gigDate->isAfter($now)) {
                // Verificar se há exceção autorizada
                $validation = $this->commissionValidator->validateBookerCommissionPayment($gig, true);

                if (! $validation['valid']) {
                    $issues[] = [
                        'type' => 'payment_rule_violation',
                        'severity' => 'critical',
                        'description' => 'Pagamento de booker registrado para evento futuro sem exceção autorizada',
                        'field' => 'booker_commission_paid_at',
                        'current_value' => $settlement->booker_commission_paid_at->format('Y-m-d'),
                        'suggested_value' => null,
                        'suggested_action' => 'Reverter pagamento ou adicionar exceção autorizada',
                        'can_auto_fix' => false,
                        'details' => $validation['message'],
                    ];
                }
            }
        }
    }

    protected function checkArtistValueDivergence(Settlement $settlement, $gig, array &$issues)
    {
        if ($settlement->artist_payment_value > 0) {
            $expectedValue = $this->financialCalculator->calculateArtistNetPayoutBrl($gig);
            $divergence = abs($settlement->artist_payment_value - $expectedValue);

            // Tolerância de R$ 0.50 (pode haver arredondamentos)
            if ($divergence > 0.50) {
                $percentDivergence = $expectedValue > 0 ? ($divergence / $expectedValue) * 100 : 0;

                $issues[] = [
                    'type' => 'value_divergence',
                    'severity' => $percentDivergence > 5 ? 'critical' : 'warning',
                    'description' => "Divergência no valor pago ao artista (diferença: R$ {$divergence})",
                    'field' => 'artist_payment_value',
                    'current_value' => number_format($settlement->artist_payment_value, 2, '.', ''),
                    'suggested_value' => number_format($expectedValue, 2, '.', ''),
                    'suggested_action' => 'Ajustar valor do pagamento ao artista',
                    'can_auto_fix' => false,
                    'details' => 'Divergência percentual: '.round($percentDivergence, 2).'%',
                ];
            }
        }
    }

    protected function checkBookerValueDivergence(Settlement $settlement, $gig, array &$issues)
    {
        if ($settlement->booker_commission_value_paid > 0 && $gig->booker_id) {
            $expectedValue = $this->financialCalculator->calculateBookerCommissionBrl($gig);
            $divergence = abs($settlement->booker_commission_value_paid - $expectedValue);

            // Tolerância de R$ 0.50
            if ($divergence > 0.50) {
                $percentDivergence = $expectedValue > 0 ? ($divergence / $expectedValue) * 100 : 0;

                $issues[] = [
                    'type' => 'value_divergence',
                    'severity' => $percentDivergence > 5 ? 'critical' : 'warning',
                    'description' => "Divergência no valor pago ao booker (diferença: R$ {$divergence})",
                    'field' => 'booker_commission_value_paid',
                    'current_value' => number_format($settlement->booker_commission_value_paid, 2, '.', ''),
                    'suggested_value' => number_format($expectedValue, 2, '.', ''),
                    'suggested_action' => 'Ajustar valor da comissão do booker',
                    'can_auto_fix' => false,
                    'details' => 'Divergência percentual: '.round($percentDivergence, 2).'%',
                ];
            }
        }
    }

    protected function checkPaymentProofs(Settlement $settlement, array &$issues)
    {
        // Se há pagamento de artista mas não há comprovante
        if ($settlement->artist_payment_value > 0 && $settlement->artist_payment_paid_at) {
            if (empty($settlement->artist_payment_proof)) {
                $issues[] = [
                    'type' => 'missing_proof',
                    'severity' => 'warning',
                    'description' => 'Pagamento ao artista sem comprovante',
                    'field' => 'artist_payment_proof',
                    'current_value' => null,
                    'suggested_action' => 'Anexar comprovante de pagamento',
                    'can_auto_fix' => false,
                ];
            }
        }

        // Se há pagamento de booker mas não há comprovante
        if ($settlement->booker_commission_value_paid > 0 && $settlement->booker_commission_paid_at) {
            if (empty($settlement->booker_commission_proof)) {
                $issues[] = [
                    'type' => 'missing_proof',
                    'severity' => 'warning',
                    'description' => 'Pagamento ao booker sem comprovante',
                    'field' => 'booker_commission_proof',
                    'current_value' => null,
                    'suggested_action' => 'Anexar comprovante de pagamento',
                    'can_auto_fix' => false,
                ];
            }
        }
    }

    protected function checkDateLogic(Settlement $settlement, $gig, array &$issues)
    {
        // Verificar se data do settlement é anterior à data do evento
        $settlementDate = Carbon::parse($settlement->settlement_date);
        $gigDate = Carbon::parse($gig->gig_date);

        if ($settlementDate->isBefore($gigDate)) {
            $issues[] = [
                'type' => 'date_logic',
                'severity' => 'warning',
                'description' => 'Data do settlement anterior à data do evento',
                'field' => 'settlement_date',
                'current_value' => $settlement->settlement_date->format('Y-m-d'),
                'suggested_action' => 'Verificar datas',
                'can_auto_fix' => false,
            ];
        }
    }

    protected function recordIssues(Settlement $settlement, array $settlementIssues, $scanOnly, $autoFix)
    {
        if (! empty($settlementIssues)) {
            $this->stats['issues_found'] += count($settlementIssues);

            $this->issues[] = [
                'settlement_id' => $settlement->id,
                'gig_id' => $settlement->gig_id,
                'settlement_date' => $settlement->settlement_date->format('Y-m-d'),
                'issues' => $settlementIssues,
            ];

            if (! $scanOnly) {
                $this->processIssues($settlement, $settlementIssues, $autoFix);
            }
        }
    }

    protected function processIssues(Settlement $settlement, array $settlementIssues, $autoFix)
    {
        foreach ($settlementIssues as $issue) {
            if ($issue['severity'] === 'critical' && ($issue['can_auto_fix'] ?? false)) {
                if ($autoFix || $this->confirmFix($settlement, $issue)) {
                    $this->applyFix($settlement, $issue);
                }
            }
        }
    }

    protected function confirmFix(Settlement $settlement, array $issue): bool
    {
        if (! defined('STDIN') || ! is_resource(STDIN)) {
            return false;
        }

        $this->newLine();
        $this->warn("🔧 Correção disponível para Settlement ID {$settlement->id}:");
        $this->line("   Problema: {$issue['description']}");
        $this->line("   Campo: {$issue['field']}");
        $this->line('   Valor atual: '.($issue['current_value'] ?? 'N/A'));
        if (isset($issue['suggested_value'])) {
            $this->line("   Valor sugerido: {$issue['suggested_value']}");
        }

        return $this->confirm('Aplicar correção?', false);
    }

    protected function applyFix(Settlement $settlement, array $issue)
    {
        try {
            DB::beginTransaction();

            // Aplicar correção específica baseada no tipo
            if ($issue['type'] === 'referential_integrity' && ! $settlement->gig) {
                // Remover settlement órfão
                $settlement->delete();
                $message = "✅ Settlement {$settlement->id} removido (órfão)";
            } else {
                // Para outros casos, logar sem aplicar correção automática
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
        $this->info('📊 RELATÓRIO FINAL - AUDITORIA DE SETTLEMENTS');
        $this->info('==============================================');
        $this->line("Total de settlements analisados: {$this->stats['total_settlements']}");
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

            // Salvar relatório detalhado
            $this->saveDetailedReport();
        }

        $this->newLine();
        $this->info('✅ Auditoria de Settlements concluída!');
    }

    protected function groupIssuesByType(): array
    {
        $grouped = [];

        foreach ($this->issues as $settlementIssue) {
            foreach ($settlementIssue['issues'] as $issue) {
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
        $reportPath = storage_path('logs/audit_settlements_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'command' => 'gig:audit-settlements',
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
