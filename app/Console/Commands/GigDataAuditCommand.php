<?php

namespace App\Console\Commands;

use App\Models\Gig;
use App\Models\Artist;
use App\Models\Booker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GigDataAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-data 
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--auto-fix : Corrigir automaticamente sem confirmações}
                            {--batch-size=100 : Tamanho do lote para processamento}
                            {--date-from= : Data inicial para filtrar gigs (Y-m-d)}
                            {--date-to= : Data final para filtrar gigs (Y-m-d)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria e correção de dados das gigs com base nas regras de negócio';

    protected array $issues = [];
    protected array $stats = [
        'total_gigs' => 0,
        'issues_found' => 0,
        'fixes_applied' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Dados das Gigs');
        $this->info('=====================================');

        // Configurações
        $scanOnly = $this->option('scan-only');
        $autoFix = $this->option('auto-fix');
        $batchSize = (int) $this->option('batch-size');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        // Log temporário para debug
        Log::info('GigDataAudit Debug - Parâmetros recebidos:', [
            'scanOnly' => $scanOnly,
            'scanOnly_type' => gettype($scanOnly),
            'autoFix' => $autoFix,
            'autoFix_type' => gettype($autoFix),
            'batchSize' => $batchSize
        ]);

        // Validar parâmetros
        if ($autoFix && $scanOnly) {
            $this->error('❌ Não é possível usar --scan-only e --auto-fix simultaneamente');
            return 1;
        }

        // Mostrar configurações
        $this->displayConfiguration($scanOnly, $autoFix, $batchSize, $dateFrom, $dateTo);

        // Confirmar execução se não for auto-fix
        if (!$autoFix && !$this->confirmExecution()) {
            $this->info('⏹️  Operação cancelada pelo usuário');
            return 0;
        }

        try {
            // Executar auditoria
            $this->performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix);
            
            // Mostrar relatório final
            $this->displayFinalReport();
            
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro durante a execução: {$e->getMessage()}");
            Log::error('GigDataAudit Error', ['exception' => $e]);
            return 1;
        }
    }

    protected function displayConfiguration($scanOnly, $autoFix, $batchSize, $dateFrom, $dateTo)
    {
        $this->info('📋 Configurações:');
        $this->line("   Modo: " . ($scanOnly ? 'Apenas Escaneamento' : ($autoFix ? 'Correção Automática' : 'Correção Interativa')));
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
        $this->warn('⚠️  ATENÇÃO: Esta operação irá analisar e potencialmente modificar dados das gigs.');
        $this->warn('   Certifique-se de ter um backup do banco de dados antes de prosseguir.');
        $this->newLine();
        
        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Construir query base
        $query = Gig::with(['artist', 'booker']);
        
        if ($dateFrom) {
            $query->where('gig_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('gig_date', '<=', $dateTo);
        }

        $this->stats['total_gigs'] = $query->count();
        $this->info("📊 Total de gigs para análise: {$this->stats['total_gigs']}");
        $this->newLine();

        // Processar em lotes
        $progressBar = $this->output->createProgressBar($this->stats['total_gigs']);
        $progressBar->start();

        $query->chunk($batchSize, function ($gigs) use ($scanOnly, $autoFix, $progressBar) {
            foreach ($gigs as $gig) {
                $this->auditGig($gig, $scanOnly, $autoFix);
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);
    }

    protected function auditGig(Gig $gig, $scanOnly, $autoFix)
    {
        try {
            $gigIssues = [];
            $now = Carbon::now();

            // 1. Verificar integridade referencial
            $this->checkReferentialIntegrity($gig, $gigIssues);

            // 2. Verificar regras de status de pagamento
            $this->checkPaymentStatusRules($gig, $now, $gigIssues);

            // 3. Verificar consistência de valores de comissão
            $this->checkCommissionConsistency($gig, $gigIssues);

            // 4. Verificar dados obrigatórios
            $this->checkRequiredFields($gig, $gigIssues);

            // 5. Verificar datas lógicas
            $this->checkDateLogic($gig, $gigIssues);

            // Processar issues encontradas
            if (!empty($gigIssues)) {
                $this->stats['issues_found'] += count($gigIssues);
                $this->issues[] = [
                    'gig_id' => $gig->id,
                    'gig_date' => $gig->gig_date->format('Y-m-d'),
                    'artist' => $gig->artist->name ?? 'N/A',
                    'issues' => $gigIssues
                ];

                if (!$scanOnly) {
                    $this->processIssues($gig, $gigIssues, $autoFix);
                }
            }

        } catch (\Exception $e) {
            $this->stats['errors']++;
            Log::error("Erro ao auditar Gig ID {$gig->id}", ['exception' => $e]);
        }
    }

    protected function checkReferentialIntegrity(Gig $gig, array &$issues)
    {
        // Verificar se artista existe
        if (!$gig->artist_id || !$gig->artist) {
            $issues[] = [
                'type' => 'referential_integrity',
                'severity' => 'critical',
                'description' => 'Gig sem artista válido',
                'field' => 'artist_id',
                'current_value' => $gig->artist_id,
                'suggested_action' => 'Atribuir artista válido ou remover gig'
            ];
        }

        // Verificar booker se especificado
        if ($gig->booker_id && !$gig->booker) {
            $issues[] = [
                'type' => 'referential_integrity',
                'severity' => 'warning',
                'description' => 'Booker ID especificado mas booker não encontrado',
                'field' => 'booker_id',
                'current_value' => $gig->booker_id,
                'suggested_action' => 'Limpar booker_id ou atribuir booker válido'
            ];
        }
    }

    protected function checkPaymentStatusRules(Gig $gig, Carbon $now, array &$issues)
    {
        $gigDate = Carbon::parse($gig->gig_date);
        $isFutureEvent = $gigDate->isAfter($now);
        $isPastEvent = $gigDate->isBefore($now->startOfDay());

        // Regra crítica: Eventos futuros não podem ter comissões pagas
        if ($isFutureEvent) {
            if ($gig->artist_payment_status === 'pago') {
                $issues[] = [
                    'type' => 'payment_status_rule',
                    'severity' => 'critical',
                    'description' => 'Evento futuro com pagamento de artista marcado como pago',
                    'field' => 'artist_payment_status',
                    'current_value' => $gig->artist_payment_status,
                    'suggested_value' => 'pendente',
                    'suggested_action' => 'Alterar para pendente'
                ];
            }

            if ($gig->booker_payment_status === 'pago') {
                $issues[] = [
                    'type' => 'payment_status_rule',
                    'severity' => 'critical',
                    'description' => 'Evento futuro com pagamento de booker marcado como pago',
                    'field' => 'booker_payment_status',
                    'current_value' => $gig->booker_payment_status,
                    'suggested_value' => 'pendente',
                    'suggested_action' => 'Alterar para pendente'
                ];
            }
        }

        // Verificar eventos muito antigos ainda pendentes
        if ($isPastEvent && $gigDate->diffInDays($now) > 30) {
            if ($gig->artist_payment_status === 'pendente') {
                $issues[] = [
                    'type' => 'payment_status_rule',
                    'severity' => 'warning',
                    'description' => 'Evento antigo (>30 dias) com pagamento de artista ainda pendente',
                    'field' => 'artist_payment_status',
                    'current_value' => $gig->artist_payment_status,
                    'suggested_action' => 'Verificar se pagamento foi realizado'
                ];
            }

            if ($gig->booker_payment_status === 'pendente' && $gig->booker_id) {
                $issues[] = [
                    'type' => 'payment_status_rule',
                    'severity' => 'warning',
                    'description' => 'Evento antigo (>30 dias) com pagamento de booker ainda pendente',
                    'field' => 'booker_payment_status',
                    'current_value' => $gig->booker_payment_status,
                    'suggested_action' => 'Verificar se pagamento foi realizado'
                ];
            }
        }
    }

    protected function checkCommissionConsistency(Gig $gig, array &$issues)
    {
        // Verificar se booker tem comissão mas não tem booker_id
        if (!$gig->booker_id && ($gig->booker_commission_value > 0 || $gig->booker_commission_rate > 0)) {
            $issues[] = [
                'type' => 'commission_consistency',
                'severity' => 'warning',
                'description' => 'Comissão de booker definida mas sem booker atribuído',
                'field' => 'booker_commission',
                'suggested_action' => 'Limpar comissão de booker ou atribuir booker'
            ];
        }

        // Verificar valores negativos
        if ($gig->agency_commission_value < 0) {
            $issues[] = [
                'type' => 'commission_consistency',
                'severity' => 'error',
                'description' => 'Valor de comissão da agência negativo',
                'field' => 'agency_commission_value',
                'current_value' => $gig->agency_commission_value,
                'suggested_action' => 'Recalcular comissão'
            ];
        }

        if ($gig->booker_commission_value < 0) {
            $issues[] = [
                'type' => 'commission_consistency',
                'severity' => 'error',
                'description' => 'Valor de comissão do booker negativo',
                'field' => 'booker_commission_value',
                'current_value' => $gig->booker_commission_value,
                'suggested_action' => 'Recalcular comissão'
            ];
        }
    }

    protected function checkRequiredFields(Gig $gig, array &$issues)
    {
        $requiredFields = [
            'cache_value' => 'Valor do cachê',
            'currency' => 'Moeda',
            'gig_date' => 'Data do evento',
        ];

        foreach ($requiredFields as $field => $description) {
            if (empty($gig->$field)) {
                $issues[] = [
                    'type' => 'required_field',
                    'severity' => 'error',
                    'description' => "{$description} não informado",
                    'field' => $field,
                    'suggested_action' => 'Preencher campo obrigatório'
                ];
            }
        }

        // Verificar valor do cachê
        if ($gig->cache_value <= 0) {
            $issues[] = [
                'type' => 'required_field',
                'severity' => 'warning',
                'description' => 'Valor do cachê deve ser maior que zero',
                'field' => 'cache_value',
                'current_value' => $gig->cache_value,
                'suggested_action' => 'Verificar valor do contrato'
            ];
        }
    }

    protected function checkDateLogic(Gig $gig, array &$issues)
    {
        // Verificar se data do contrato é posterior à data do evento
        if ($gig->contract_date && $gig->gig_date) {
            $contractDate = Carbon::parse($gig->contract_date);
            $gigDate = Carbon::parse($gig->gig_date);

            if ($contractDate->isAfter($gigDate)) {
                $issues[] = [
                    'type' => 'date_logic',
                    'severity' => 'warning',
                    'description' => 'Data do contrato posterior à data do evento',
                    'field' => 'contract_date',
                    'current_value' => $gig->contract_date,
                    'suggested_action' => 'Verificar datas'
                ];
            }
        }
    }

    protected function processIssues(Gig $gig, array $gigIssues, $autoFix)
    {
        foreach ($gigIssues as $issue) {
            if ($issue['severity'] === 'critical' && isset($issue['suggested_value'])) {
                if ($autoFix) {
                    $this->applyFix($gig, $issue);
                } elseif ($this->confirmFix($gig, $issue)) {
                    $this->applyFix($gig, $issue);
                }
            }
        }
    }

    protected function confirmFix(Gig $gig, array $issue): bool
    {
        $this->newLine();
        $this->warn("🔧 Correção disponível para Gig ID {$gig->id}:");
        $this->line("   Problema: {$issue['description']}");
        $this->line("   Campo: {$issue['field']}");
        $this->line("   Valor atual: {$issue['current_value']}");
        $this->line("   Valor sugerido: {$issue['suggested_value']}");
        
        return $this->confirm('Aplicar correção?', false);
    }

    protected function applyFix(Gig $gig, array $issue)
    {
        try {
            DB::beginTransaction();

            $field = $issue['field'];
            $newValue = $issue['suggested_value'];
            
            $gig->$field = $newValue;
            $gig->save();

            DB::commit();
            
            $this->stats['fixes_applied']++;
            $this->info("✅ Correção aplicada: Gig {$gig->id} - {$field} = {$newValue}");
            
            Log::info("GigDataAudit: Correção aplicada", [
                'gig_id' => $gig->id,
                'field' => $field,
                'old_value' => $issue['current_value'],
                'new_value' => $newValue,
                'issue_type' => $issue['type']
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Erro ao aplicar correção: {$e->getMessage()}");
            Log::error("GigDataAudit: Erro ao aplicar correção", [
                'gig_id' => $gig->id,
                'issue' => $issue,
                'exception' => $e
            ]);
        }
    }

    protected function displayFinalReport()
    {
        $this->newLine();
        $this->info('📊 RELATÓRIO FINAL');
        $this->info('==================');
        $this->line("Total de gigs analisadas: {$this->stats['total_gigs']}");
        $this->line("Issues encontradas: {$this->stats['issues_found']}");
        $this->line("Correções aplicadas: {$this->stats['fixes_applied']}");
        $this->line("Erros durante execução: {$this->stats['errors']}");

        if (!empty($this->issues)) {
            $this->newLine();
            $this->warn('🚨 RESUMO DOS PROBLEMAS ENCONTRADOS:');
            
            $issuesByType = [];
            foreach ($this->issues as $gigIssue) {
                foreach ($gigIssue['issues'] as $issue) {
                    $type = $issue['type'];
                    $severity = $issue['severity'];
                    $key = "{$type}_{$severity}";
                    
                    if (!isset($issuesByType[$key])) {
                        $issuesByType[$key] = [
                            'type' => $type,
                            'severity' => $severity,
                            'count' => 0,
                            'gigs' => []
                        ];
                    }
                    
                    $issuesByType[$key]['count']++;
                    $issuesByType[$key]['gigs'][] = $gigIssue['gig_id'];
                }
            }

            foreach ($issuesByType as $issueGroup) {
                $emoji = $this->getSeverityEmoji($issueGroup['severity']);
                $this->line("{$emoji} {$issueGroup['type']} ({$issueGroup['severity']}): {$issueGroup['count']} ocorrências");
            }

            // Salvar relatório detalhado
            $this->saveDetailedReport();
        }

        $this->newLine();
        $this->info('✅ Auditoria concluída!');
    }

    protected function getSeverityEmoji($severity): string
    {
        return match($severity) {
            'critical' => '🔴',
            'error' => '🟠',
            'warning' => '🟡',
            default => 'ℹ️'
        };
    }

    protected function saveDetailedReport()
    {
        $reportPath = storage_path('logs/gig_audit_' . now()->format('Y-m-d_H-i-s') . '.json');
        
        $report = [
            'timestamp' => now()->toISOString(),
            'stats' => $this->stats,
            'issues' => $this->issues
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
