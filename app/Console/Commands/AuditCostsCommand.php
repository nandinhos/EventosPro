<?php

namespace App\Console\Commands;

use App\Models\Gig;
use App\Models\GigCost;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditCostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-costs
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--auto-fix : Corrigir automaticamente sem confirmações}
                            {--batch-size=100 : Tamanho do lote para processamento}
                            {--date-from= : Data inicial para filtrar gigs (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar gigs (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria de custos das gigs - valida GigCosts e cost centers';

    protected array $issues = [];

    protected array $stats = [
        'total_gigs' => 0,
        'total_costs' => 0,
        'issues_found' => 0,
        'corrections_applied' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Custos');
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
            Log::error('AuditCosts Error', ['exception' => $e]);

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

        $this->warn('⚠️  ATENÇÃO: Esta operação irá validar custos e potencialmente modificar dados.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Construir query base
        $query = Gig::with(['gigCosts.costCenter', 'artist', 'booker']);

        if ($dateFrom) {
            $query->where('gig_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('gig_date', '<=', $dateTo);
        }

        // Contar total de registros
        $this->stats['total_gigs'] = $query->count();
        $this->info("📊 Total de gigs para análise: {$this->stats['total_gigs']}");

        // Contar total de custos
        $this->stats['total_costs'] = GigCost::whereHas('gig', function ($q) use ($dateFrom, $dateTo) {
            if ($dateFrom) {
                $q->where('gig_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $q->where('gig_date', '<=', $dateTo);
            }
        })->count();
        $this->info("📊 Total de custos para análise: {$this->stats['total_costs']}");
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
                $this->auditGigCosts($gig, $scanOnly, $autoFix);
                $progressBar->advance();
            }

            unset($gigs);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Auditar custos órfãos
        $this->auditOrphanCosts($scanOnly, $autoFix);
    }

    protected function auditGigCosts(Gig $gig, $scanOnly, $autoFix)
    {
        try {
            $gigIssues = [];

            foreach ($gig->gigCosts as $cost) {
                // 1. Verificar se cost tem cost_center válido
                $this->checkCostCenter($cost, $gigIssues);

                // 2. Verificar valor do custo
                $this->checkCostValue($cost, $gigIssues);

                // 3. Verificar moeda do custo vs gig
                $this->checkCostCurrency($cost, $gig, $gigIssues);

                // 4. Verificar status de confirmação
                $this->checkCostConfirmation($cost, $gig, $gigIssues);
            }

            // Registrar issues encontradas
            $this->recordIssues($gig, $gigIssues, $scanOnly, $autoFix);

        } catch (Exception $e) {
            $this->stats['errors']++;
            Log::error("Erro ao auditar Costs da Gig ID {$gig->id}", ['exception' => $e]);
        }
    }

    protected function checkCostCenter($cost, array &$issues)
    {
        if (! $cost->cost_center_id || ! $cost->costCenter) {
            $issues[] = [
                'type' => 'missing_cost_center',
                'severity' => 'critical',
                'description' => "GigCost #{$cost->id} sem cost center válido",
                'field' => 'cost_center_id',
                'current_value' => $cost->cost_center_id,
                'suggested_action' => 'Atribuir cost center ou remover custo',
                'can_auto_fix' => false,
                'cost_id' => $cost->id,
            ];
        }
    }

    protected function checkCostValue($cost, array &$issues)
    {
        if ($cost->value < 0) {
            $issues[] = [
                'type' => 'negative_cost_value',
                'severity' => 'critical',
                'description' => "GigCost #{$cost->id} com valor negativo",
                'field' => 'value',
                'current_value' => number_format($cost->value, 2, '.', ''),
                'suggested_action' => 'Corrigir valor negativo',
                'can_auto_fix' => false,
                'cost_id' => $cost->id,
            ];
        }
    }

    protected function checkCostCurrency($cost, Gig $gig, array &$issues)
    {
        if ($cost->currency && $gig->currency && $cost->currency !== $gig->currency) {
            $issues[] = [
                'type' => 'cost_currency_mismatch',
                'severity' => 'warning',
                'description' => "GigCost #{$cost->id} com moeda diferente da gig ({$cost->currency} vs {$gig->currency})",
                'field' => 'costs.currency',
                'current_value' => $cost->currency,
                'suggested_value' => $gig->currency,
                'suggested_action' => 'Alinhar moeda do custo com a moeda da gig',
                'can_auto_fix' => true,
                'cost_id' => $cost->id,
            ];
        }
    }

    protected function checkCostConfirmation($cost, Gig $gig, array &$issues)
    {
        // Se o custo foi confirmado mas o evento ainda não aconteceu
        if ($cost->is_confirmed && $gig->gig_date->isFuture()) {
            $issues[] = [
                'type' => 'cost_confirmed_future_event',
                'severity' => 'warning',
                'description' => "GigCost #{$cost->id} confirmado mas evento ainda não aconteceu",
                'field' => 'costs.is_confirmed',
                'current_value' => 'true',
                'suggested_value' => 'false',
                'suggested_action' => 'Desmarcar confirmação do custo',
                'can_auto_fix' => true,
                'cost_id' => $cost->id,
            ];
        }
    }

    protected function auditOrphanCosts($scanOnly, $autoFix)
    {
        $orphanCosts = GigCost::whereNull('gig_id')
            ->orWhereDoesntHave('gig')
            ->get();

        if ($orphanCosts->count() > 0) {
            $this->newLine();
            $this->warn("🔴 Encontrados {$orphanCosts->count()} custos órfãos (sem gig válido)");

            $this->stats['issues_found'] += $orphanCosts->count();

            foreach ($orphanCosts as $cost) {
                $this->issues[] = [
                    'gig_id' => null,
                    'gig_date' => 'N/A',
                    'artist' => 'N/A',
                    'contract_number' => 'N/A',
                    'issues' => [
                        [
                            'type' => 'orphan_cost',
                            'severity' => 'critical',
                            'description' => "GigCost #{$cost->id} sem gig válido (órfão)",
                            'field' => 'gig_id',
                            'current_value' => $cost->gig_id,
                            'suggested_action' => 'Remover custo órfão',
                            'can_auto_fix' => true,
                            'cost_id' => $cost->id,
                        ],
                    ],
                ];
            }

            if (! $scanOnly && ($autoFix || $this->confirm('Remover custos órfãos?', false))) {
                $deleted = GigCost::whereNull('gig_id')->orWhereDoesntHave('gig')->delete();
                $this->info("✅ {$deleted} custos órfãos removidos");
                $this->stats['corrections_applied'] += $deleted;
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
            if ($issue['can_auto_fix'] ?? false) {
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

            $costId = $issue['cost_id'] ?? null;
            $field = $issue['field'] ?? null;
            $suggestedValue = $issue['suggested_value'] ?? null;

            if (! $costId || ! $field || $suggestedValue === null) {
                Log::warning('Cost issue sem dados suficientes para correção automática', [
                    'gig_id' => $gig->id,
                    'issue' => $issue,
                ]);
                DB::rollBack();

                return;
            }

            // Buscar o custo
            $cost = GigCost::find($costId);
            if (! $cost) {
                $this->warn("⚠️  GigCost #{$costId} não encontrado");
                DB::rollBack();

                return;
            }

            // Extrair nome do campo (costs.currency -> currency)
            $fieldName = str_contains($field, '.') ? explode('.', $field)[1] : $field;

            // Aplicar correção
            $oldValue = $cost->$fieldName;

            // Converter valores booleanos string para boolean
            $finalValue = $suggestedValue;
            if ($suggestedValue === 'true' || $suggestedValue === 'false') {
                $finalValue = $suggestedValue === 'true';
            }

            $cost->$fieldName = $finalValue;
            $cost->save();

            DB::commit();

            $this->stats['corrections_applied']++;
            $this->info("✅ Corrigido: GigCost #{$costId} campo '{$fieldName}' de '{$oldValue}' para '{$suggestedValue}'");

            Log::info('Cost correction applied', [
                'gig_id' => $gig->id,
                'cost_id' => $costId,
                'field' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => $suggestedValue,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;
            $this->error("❌ Erro ao aplicar correção: {$e->getMessage()}");
            Log::error('Cost correction error', [
                'gig_id' => $gig->id,
                'issue' => $issue,
                'exception' => $e,
            ]);
        }
    }

    protected function displayFinalReport()
    {
        $this->newLine();
        $this->info('📊 RELATÓRIO FINAL - AUDITORIA DE CUSTOS');
        $this->info('=========================================');
        $this->line("Total de gigs analisadas: {$this->stats['total_gigs']}");
        $this->line("Total de custos analisados: {$this->stats['total_costs']}");
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
        $this->info('✅ Auditoria de Custos concluída!');
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
        $reportPath = storage_path('logs/audit_costs_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'command' => 'gig:audit-costs',
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
