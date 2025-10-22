<?php

namespace App\Console\Commands;

use App\Models\Gig;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-payments
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--auto-fix : Corrigir automaticamente sem confirmações}
                            {--batch-size=100 : Tamanho do lote para processamento}
                            {--date-from= : Data inicial para filtrar payments (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar payments (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria de parcelas de pagamento do cliente - valida consistência entre payments e gigs';

    protected array $issues = [];

    protected array $stats = [
        'total_gigs' => 0,
        'issues_found' => 0,
        'corrections_applied' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Payments (Parcelas de Pagamento)');
        $this->info('==========================================================');

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
            Log::error('AuditPayments Error', ['exception' => $e]);

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

        $this->warn('⚠️  ATENÇÃO: Esta operação irá analisar payments e potencialmente modificar dados.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Construir query base
        $query = Gig::with(['payments', 'artist', 'booker']);

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
                $this->auditGigPayments($gig, $scanOnly, $autoFix);
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

    protected function auditGigPayments(Gig $gig, $scanOnly, $autoFix)
    {
        try {
            $gigIssues = [];
            $now = Carbon::now();

            // 1. Verificar se há payments órfãos (sem gig)
            $this->checkOrphanPayments($gig, $gigIssues);

            // 2. Verificar soma de parcelas vs valor do contrato
            $this->checkPaymentsTotalVsContract($gig, $gigIssues);

            // 3. Verificar parcelas com received_value_actual > due_value
            $this->checkOverpayments($gig, $gigIssues);

            // 4. Verificar parcelas confirmadas sem received_value_actual
            $this->checkConfirmedWithoutValue($gig, $gigIssues);

            // 5. Verificar moeda das parcelas vs moeda da gig
            $this->checkCurrencyConsistency($gig, $gigIssues);

            // 6. Verificar parcelas vencidas não confirmadas
            $this->checkOverduePayments($gig, $now, $gigIssues);

            // 7. Verificar payment_status inconsistente
            $this->checkPaymentStatusConsistency($gig, $gigIssues);

            // Registrar issues encontradas
            $this->recordIssues($gig, $gigIssues, $scanOnly, $autoFix);

        } catch (Exception $e) {
            $this->stats['errors']++;
            Log::error("Erro ao auditar Payments da Gig ID {$gig->id}", ['exception' => $e]);
        }
    }

    protected function checkOrphanPayments(Gig $gig, array &$issues)
    {
        // Esta verificação já é feita pela constraint FK, mas vamos listar para completude
        $orphanPayments = Payment::whereNull('gig_id')->orWhereDoesntHave('gig')->count();

        if ($orphanPayments > 0) {
            $issues[] = [
                'type' => 'orphan_payments',
                'severity' => 'critical',
                'description' => "Existem {$orphanPayments} pagamentos órfãos no sistema",
                'field' => 'gig_id',
                'suggested_action' => 'Remover pagamentos órfãos ou associar a gig válida',
                'can_auto_fix' => true,
            ];
        }
    }

    protected function checkPaymentsTotalVsContract(Gig $gig, array &$issues)
    {
        $contractValue = (float) $gig->cache_value;
        $paymentsTotal = $gig->payments->sum('due_value');
        $divergence = abs($contractValue - $paymentsTotal);

        // Tolerância de R$ 0.01
        if ($divergence > 0.01) {
            $percentDivergence = $contractValue > 0 ? ($divergence / $contractValue) * 100 : 0;

            $issues[] = [
                'type' => 'payment_total_divergence',
                'severity' => $percentDivergence > 5 ? 'critical' : 'warning',
                'description' => "Soma das parcelas (R$ {$paymentsTotal}) diferente do valor do contrato (R$ {$contractValue})",
                'field' => 'payments.due_value',
                'current_value' => number_format($paymentsTotal, 2, '.', ''),
                'suggested_value' => number_format($contractValue, 2, '.', ''),
                'suggested_action' => 'Ajustar valores das parcelas para totalizar o valor do contrato',
                'can_auto_fix' => false,
                'details' => "Divergência: R$ {$divergence} (".round($percentDivergence, 2).'%)',
            ];
        }
    }

    protected function checkOverpayments(Gig $gig, array &$issues)
    {
        foreach ($gig->payments as $payment) {
            if ($payment->received_value_actual && $payment->received_value_actual > $payment->due_value) {
                $excess = $payment->received_value_actual - $payment->due_value;

                $issues[] = [
                    'type' => 'overpayment',
                    'severity' => 'warning',
                    'description' => "Parcela #{$payment->id}: valor recebido (R$ {$payment->received_value_actual}) maior que devido (R$ {$payment->due_value})",
                    'field' => 'received_value_actual',
                    'current_value' => number_format($payment->received_value_actual, 2, '.', ''),
                    'suggested_value' => number_format($payment->due_value, 2, '.', ''),
                    'suggested_action' => 'Verificar valor recebido',
                    'can_auto_fix' => false,
                    'details' => "Excesso: R$ {$excess}",
                    'payment_id' => $payment->id,
                ];
            }
        }
    }

    protected function checkConfirmedWithoutValue(Gig $gig, array &$issues)
    {
        foreach ($gig->payments as $payment) {
            if ($payment->confirmed_at && (! $payment->received_value_actual || $payment->received_value_actual == 0)) {
                $issues[] = [
                    'type' => 'confirmed_without_value',
                    'severity' => 'warning',
                    'description' => "Parcela #{$payment->id} confirmada mas sem valor recebido",
                    'field' => 'received_value_actual',
                    'current_value' => $payment->received_value_actual ?? 0,
                    'suggested_value' => number_format($payment->due_value, 2, '.', ''),
                    'suggested_action' => 'Preencher valor recebido ou desconfirmar parcela',
                    'can_auto_fix' => true,
                    'payment_id' => $payment->id,
                ];
            }
        }
    }

    protected function checkCurrencyConsistency(Gig $gig, array &$issues)
    {
        $gigCurrency = $gig->currency;

        foreach ($gig->payments as $payment) {
            if ($payment->currency && $payment->currency !== $gigCurrency) {
                $issues[] = [
                    'type' => 'currency_mismatch',
                    'severity' => 'critical',
                    'description' => "Parcela #{$payment->id} com moeda diferente da gig",
                    'field' => 'currency',
                    'current_value' => $payment->currency,
                    'suggested_value' => $gigCurrency,
                    'suggested_action' => 'Sincronizar moeda com a gig',
                    'can_auto_fix' => true,
                    'payment_id' => $payment->id,
                ];
            }
        }
    }

    protected function checkOverduePayments(Gig $gig, Carbon $now, array &$issues)
    {
        $overduePayments = $gig->payments
            ->whereNull('confirmed_at')
            ->where('due_date', '<', $now->copy()->subDays(30))
            ->count();

        if ($overduePayments > 0) {
            $issues[] = [
                'type' => 'overdue_payments',
                'severity' => 'warning',
                'description' => "{$overduePayments} parcela(s) vencida(s) há mais de 30 dias sem confirmação",
                'field' => 'confirmed_at',
                'suggested_action' => 'Confirmar pagamento ou ajustar data de vencimento',
                'can_auto_fix' => false,
            ];
        }
    }

    protected function checkPaymentStatusConsistency(Gig $gig, array &$issues)
    {
        $paymentsConfirmed = $gig->payments->whereNotNull('confirmed_at')->count();
        $totalPayments = $gig->payments->count();
        $paymentsPending = $totalPayments - $paymentsConfirmed;

        // Se todas as parcelas estão confirmadas mas status não é "pago"
        if ($totalPayments > 0 && $paymentsConfirmed === $totalPayments && $gig->payment_status !== 'pago') {
            $issues[] = [
                'type' => 'payment_status_inconsistency',
                'severity' => 'critical',
                'description' => 'Todas as parcelas confirmadas mas payment_status não é "pago"',
                'field' => 'payment_status',
                'current_value' => $gig->payment_status,
                'suggested_value' => 'pago',
                'suggested_action' => 'Atualizar payment_status para "pago"',
                'can_auto_fix' => true,
            ];
        }

        // Se há parcelas pendentes mas status é "pago"
        if ($paymentsPending > 0 && $gig->payment_status === 'pago') {
            $issues[] = [
                'type' => 'payment_status_inconsistency',
                'severity' => 'critical',
                'description' => "Gig marcada como 'pago' mas possui {$paymentsPending} parcela(s) pendente(s)",
                'field' => 'payment_status',
                'current_value' => $gig->payment_status,
                'suggested_value' => 'a_vencer',
                'suggested_action' => 'Atualizar payment_status ou confirmar parcelas',
                'can_auto_fix' => true,
            ];
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
                case 'orphan_payments':
                    Payment::whereNull('gig_id')->orWhereDoesntHave('gig')->delete();
                    $message = '✅ Pagamentos órfãos removidos';
                    break;

                case 'confirmed_without_value':
                    $payment = Payment::find($issue['payment_id']);
                    if ($payment) {
                        $payment->received_value_actual = $issue['suggested_value'];
                        $payment->save();
                        $message = "✅ Payment {$payment->id}: valor recebido preenchido";
                    }
                    break;

                case 'currency_mismatch':
                    $payment = Payment::find($issue['payment_id']);
                    if ($payment) {
                        $payment->currency = $issue['suggested_value'];
                        $payment->save();
                        $message = "✅ Payment {$payment->id}: moeda sincronizada";
                    }
                    break;

                case 'payment_status_inconsistency':
                    $gig->payment_status = $issue['suggested_value'];
                    $gig->save();
                    $message = "✅ Gig {$gig->id}: payment_status atualizado para '{$issue['suggested_value']}'";
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
        $this->info('📊 RELATÓRIO FINAL - AUDITORIA DE PAYMENTS');
        $this->info('==========================================');
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
        $this->info('✅ Auditoria de Payments concluída!');
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
        $reportPath = storage_path('logs/audit_payments_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'command' => 'gig:audit-payments',
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
