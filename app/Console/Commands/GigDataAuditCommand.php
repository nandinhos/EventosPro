<?php

namespace App\Console\Commands;

use App\Models\Booker;
use App\Models\Gig;
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
                            {--date-from= : Data inicial para filtrar gigs (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar gigs (YYYY-MM-DD)}
                            {--full-database : Processar todo o banco de dados (ignora filtros de data)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria e correção de dados das gigs com base nas regras de negócio';

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
        $this->info('🔍 Iniciando Auditoria de Dados das Gigs');
        $this->info('=====================================');

        // Configurações
        $scanOnly = $this->option('scan-only');
        $autoFix = $this->option('auto-fix');
        $batchSize = (int) $this->option('batch-size');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $fullDatabase = $this->option('full-database');

        // Se full-database for especificado, ignorar filtros de data
        if ($fullDatabase) {
            $dateFrom = null;
            $dateTo = null;
            $this->info('🌐 Modo BANCO COMPLETO ativado - processando TODOS os registros');
        }

        // Log temporário para debug
        Log::info('GigDataAudit Debug - Parâmetros recebidos:', [
            'scanOnly' => $scanOnly,
            'scanOnly_type' => gettype($scanOnly),
            'autoFix' => $autoFix,
            'autoFix_type' => gettype($autoFix),
            'batchSize' => $batchSize,
            'fullDatabase' => $fullDatabase,
            'environment' => $this->isRunningInConsole() ? 'console' : 'web',
        ]);

        // Validar parâmetros
        if ($autoFix && $scanOnly) {
            $this->error('❌ Não é possível usar --scan-only e --auto-fix simultaneamente');

            return 1;
        }

        // Mostrar configurações
        $this->displayConfiguration($scanOnly, $autoFix, $batchSize, $dateFrom, $dateTo, $fullDatabase);

        // Confirmar execução se não for auto-fix E se estivermos em ambiente console
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
        } catch (\Exception $e) {
            $this->error("❌ Erro durante a execução: {$e->getMessage()}");
            Log::error('GigDataAudit Error', ['exception' => $e]);

            return 1;
        }
    }

    /**
     * Verifica se o comando está sendo executado em ambiente console (terminal)
     * ou via web (Artisan::call)
     */
    protected function isRunningInConsole(): bool
    {
        // Verifica se temos acesso ao STDIN
        if (! defined('STDIN') || ! is_resource(STDIN)) {
            return false;
        }

        // Verifica se estamos em modo CLI real
        if (php_sapi_name() !== 'cli') {
            return false;
        }

        // Verifica se o STDIN está disponível para leitura
        $read = [STDIN];
        $write = [];
        $except = [];
        $result = stream_select($read, $write, $except, 0);

        return $result !== false;
    }

    protected function displayConfiguration($scanOnly, $autoFix, $batchSize, $dateFrom, $dateTo, $fullDatabase = false)
    {
        $this->info('📋 Configurações:');
        $this->line('   Modo: '.($scanOnly ? 'Apenas Escaneamento' : ($autoFix ? 'Correção Automática' : 'Correção Interativa')));
        $this->line("   Tamanho do lote: {$batchSize}");

        if ($fullDatabase) {
            $this->line('   Escopo: BANCO COMPLETO (todos os registros)');
        } else {
            if ($dateFrom) {
                $this->line("   Data inicial: {$dateFrom}");
            }
            if ($dateTo) {
                $this->line("   Data final: {$dateTo}");
            }
            if (! $dateFrom && ! $dateTo) {
                $this->line('   Escopo: Todos os registros (sem filtro de data)');
            }
        }

        $this->newLine();
    }

    protected function confirmExecution(): bool
    {
        // Se não estivermos em ambiente console, assumir confirmação automática
        if (! $this->isRunningInConsole()) {
            $this->info('🤖 Execução automática (ambiente web) - prosseguindo sem confirmação');

            return true;
        }

        $this->warn('⚠️  ATENÇÃO: Esta operação irá analisar e potencialmente modificar dados das gigs.');
        $this->warn('   Certifique-se de ter um backup do banco de dados antes de prosseguir.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', false);
    }

    protected function performAudit($batchSize, $dateFrom, $dateTo, $scanOnly, $autoFix)
    {
        // Log início da auditoria
        Log::info('GigDataAudit: Iniciando auditoria completa', [
            'batch_size' => $batchSize,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'scan_only' => $scanOnly,
            'auto_fix' => $autoFix,
            'memory_usage_start' => memory_get_usage(true),
        ]);

        // Construir query base
        $query = Gig::with(['artist', 'booker', 'payments']);

        if ($dateFrom) {
            $query->where('gig_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('gig_date', '<=', $dateTo);
        }

        // Contar total de registros
        $this->stats['total_gigs'] = $query->count();
        $this->info("📊 Total de gigs para análise: {$this->stats['total_gigs']}");

        // Log detalhado da distribuição de dados
        $this->logDatabaseDistribution($query);

        $this->newLine();

        // Verificar se há dados para processar
        if ($this->stats['total_gigs'] === 0) {
            $this->warn('⚠️  Nenhuma gig encontrada para os critérios especificados.');

            return;
        }

        // Processar em lotes com otimizações
        $progressBar = $this->output->createProgressBar($this->stats['total_gigs']);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - %message%');
        $progressBar->setMessage('Iniciando processamento...');
        $progressBar->start();

        $processedCount = 0;
        $batchNumber = 0;
        $startTime = microtime(true);

        $query->chunk($batchSize, function ($gigs) use ($scanOnly, $autoFix, $progressBar, &$processedCount, &$batchNumber, $startTime) {
            $batchNumber++;
            $batchStartTime = microtime(true);
            $batchIssuesFound = 0;

            $progressBar->setMessage("Processando lote {$batchNumber}...");

            foreach ($gigs as $gig) {
                $issuesBeforeAudit = count($this->issues);
                $this->auditGig($gig, $scanOnly, $autoFix);
                $issuesAfterAudit = count($this->issues);

                if ($issuesAfterAudit > $issuesBeforeAudit) {
                    $batchIssuesFound += ($issuesAfterAudit - $issuesBeforeAudit);
                }

                $processedCount++;
                $progressBar->advance();

                // Atualizar mensagem do progresso a cada 10 registros
                if ($processedCount % 10 === 0) {
                    $elapsedTime = microtime(true) - $startTime;
                    $rate = $processedCount / $elapsedTime;
                    $progressBar->setMessage(sprintf(
                        'Processando... (%.1f gigs/s, %d issues encontradas)',
                        $rate,
                        $this->stats['issues_found']
                    ));
                }
            }

            $batchTime = microtime(true) - $batchStartTime;

            // Log detalhado do lote
            Log::info("GigDataAudit: Lote {$batchNumber} processado", [
                'batch_number' => $batchNumber,
                'batch_size' => count($gigs),
                'batch_time_seconds' => round($batchTime, 3),
                'batch_issues_found' => $batchIssuesFound,
                'total_processed' => $processedCount,
                'total_issues_so_far' => $this->stats['issues_found'],
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);

            // Liberar memória a cada lote
            unset($gigs);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });

        $progressBar->setMessage('Processamento concluído!');
        $progressBar->finish();
        $this->newLine(2);

        $totalTime = microtime(true) - $startTime;

        // Log final detalhado
        Log::info('GigDataAudit: Processamento completo finalizado', [
            'total_gigs_processed' => $processedCount,
            'total_time_seconds' => round($totalTime, 3),
            'average_rate_per_second' => round($processedCount / $totalTime, 2),
            'total_issues_found' => $this->stats['issues_found'],
            'total_corrections_applied' => $this->stats['corrections_applied'],
            'total_errors' => $this->stats['errors'],
            'memory_peak_usage' => memory_get_peak_usage(true),
            'batch_count' => $batchNumber,
        ]);

        $this->info('⚡ Processamento concluído em '.round($totalTime, 2).' segundos');
        $this->info('📈 Taxa média: '.round($processedCount / $totalTime, 1).' gigs/segundo');
    }

    /**
     * Log detalhado da distribuição de dados no banco
     */
    protected function logDatabaseDistribution($query)
    {
        try {
            // Clonar query para não afetar a principal
            $distributionQuery = clone $query;

            // Estatísticas por status de pagamento
            $paymentStatusStats = $distributionQuery->select('payment_status', DB::raw('COUNT(*) as count'))
                ->groupBy('payment_status')
                ->pluck('count', 'payment_status')
                ->toArray();

            // Estatísticas por status de contrato
            $contractStatusStats = $distributionQuery->select('contract_status', DB::raw('COUNT(*) as count'))
                ->groupBy('contract_status')
                ->pluck('count', 'contract_status')
                ->toArray();

            // Estatísticas por ano
            $yearStats = $distributionQuery->select(DB::raw('YEAR(gig_date) as year'), DB::raw('COUNT(*) as count'))
                ->groupBy(DB::raw('YEAR(gig_date)'))
                ->orderBy('year')
                ->pluck('count', 'year')
                ->toArray();

            // Gigs com problemas potenciais (valores zerados, datas nulas, etc.)
            $potentialIssues = [
                'cache_value_zero' => $distributionQuery->where('cache_value', 0)->count(),
                'currency_empty' => $distributionQuery->whereNull('currency')->orWhere('currency', '')->count(),
                'gig_date_null' => $distributionQuery->whereNull('gig_date')->count(),
                'artist_id_null' => $distributionQuery->whereNull('artist_id')->count(),
            ];

            Log::info('GigDataAudit: Distribuição de dados no banco', [
                'payment_status_distribution' => $paymentStatusStats,
                'contract_status_distribution' => $contractStatusStats,
                'year_distribution' => $yearStats,
                'potential_issues_count' => $potentialIssues,
                'total_gigs_in_scope' => $this->stats['total_gigs'],
            ]);

            // Mostrar resumo no console
            $this->info('📊 Distribuição dos dados:');
            $this->line('   Status de pagamento: '.json_encode($paymentStatusStats));
            $this->line('   Status de contrato: '.json_encode($contractStatusStats));
            $this->line('   Problemas potenciais detectados: '.array_sum($potentialIssues));

        } catch (\Exception $e) {
            Log::warning('GigDataAudit: Erro ao gerar estatísticas de distribuição', [
                'error' => $e->getMessage(),
            ]);
        }
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
            if (! empty($gigIssues)) {
                $this->stats['issues_found'] += count($gigIssues);
                $this->issues[] = [
                    'gig_id' => $gig->id,
                    'gig_date' => $gig->gig_date->format('Y-m-d'),
                    'artist' => $gig->artist->name ?? 'N/A',
                    'issues' => $gigIssues,
                ];

                if (! $scanOnly) {
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
        if (! $gig->artist_id || ! $gig->artist) {
            $issues[] = [
                'type' => 'referential_integrity',
                'severity' => 'critical',
                'description' => 'Gig sem artista válido',
                'field' => 'artist_id',
                'current_value' => $gig->artist_id,
                'suggested_action' => 'Atribuir artista válido ou remover gig',
            ];
        }

        // Verificar booker se especificado
        if ($gig->booker_id && ! $gig->booker) {
            $issues[] = [
                'type' => 'referential_integrity',
                'severity' => 'warning',
                'description' => 'Booker ID especificado mas booker não encontrado',
                'field' => 'booker_id',
                'current_value' => $gig->booker_id,
                'suggested_action' => 'Limpar booker_id ou atribuir booker válido',
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
                    'suggested_action' => 'Alterar para pendente',
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
                    'suggested_action' => 'Alterar para pendente',
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
                    'suggested_action' => 'Verificar se pagamento foi realizado',
                ];
            }

            if ($gig->booker_payment_status === 'pendente' && $gig->booker_id) {
                $issues[] = [
                    'type' => 'payment_status_rule',
                    'severity' => 'warning',
                    'description' => 'Evento antigo (>30 dias) com pagamento de booker ainda pendente',
                    'field' => 'booker_payment_status',
                    'current_value' => $gig->booker_payment_status,
                    'suggested_action' => 'Verificar se pagamento foi realizado',
                ];
            }
        }
    }

    protected function checkCommissionConsistency(Gig $gig, array &$issues)
    {
        // Verificar se booker tem comissão mas não tem booker_id
        if (! $gig->booker_id && ($gig->booker_commission_value > 0 || $gig->booker_commission_rate > 0)) {
            $issues[] = [
                'type' => 'commission_consistency',
                'severity' => 'warning',
                'description' => 'Comissão de booker definida mas sem booker atribuído',
                'field' => 'booker_commission',
                'suggested_action' => 'Limpar comissão de booker ou atribuir booker',
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
                'suggested_action' => 'Recalcular comissão',
            ];
        }

        if ($gig->booker_commission_value < 0) {
            $issues[] = [
                'type' => 'commission_consistency',
                'severity' => 'error',
                'description' => 'Valor de comissão do booker negativo',
                'field' => 'booker_commission_value',
                'current_value' => $gig->booker_commission_value,
                'suggested_action' => 'Recalcular comissão',
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
                    'suggested_action' => 'Preencher campo obrigatório',
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
                'suggested_action' => 'Verificar valor do contrato',
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
                    'suggested_action' => 'Verificar datas',
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
                } elseif ($this->isRunningInConsole() && $this->confirmFix($gig, $issue)) {
                    $this->applyFix($gig, $issue);
                } elseif (! $this->isRunningInConsole()) {
                    // Em ambiente web, logar a issue crítica que precisa de atenção manual
                    Log::warning('GigDataAudit: Issue crítica detectada em ambiente web', [
                        'gig_id' => $gig->id,
                        'issue_type' => $issue['type'],
                        'description' => $issue['description'],
                        'field' => $issue['field'],
                        'current_value' => $issue['current_value'] ?? null,
                        'suggested_value' => $issue['suggested_value'] ?? null,
                        'action_required' => 'Correção manual necessária',
                    ]);
                }
            }
        }
    }

    protected function confirmFix(Gig $gig, array $issue): bool
    {
        // Se não estivermos em ambiente console, não fazer correções interativas
        if (! $this->isRunningInConsole()) {
            $this->line("🤖 Ambiente web detectado - pulando correção interativa para Gig ID {$gig->id}");

            return false;
        }

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
            $oldValue = $issue['current_value'] ?? $gig->$field;

            $gig->$field = $newValue;
            $gig->save();

            DB::commit();

            $this->stats['fixes_applied']++;

            $message = "✅ Correção aplicada: Gig {$gig->id} - {$field} = {$newValue}";
            $this->info($message);

            Log::info('GigDataAudit: Correção aplicada', [
                'gig_id' => $gig->id,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'issue_type' => $issue['type'],
                'environment' => $this->isRunningInConsole() ? 'console' : 'web',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->stats['errors']++;

            $errorMessage = "❌ Erro ao aplicar correção: {$e->getMessage()}";
            $this->error($errorMessage);

            Log::error('GigDataAudit: Erro ao aplicar correção', [
                'gig_id' => $gig->id,
                'issue' => $issue,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'environment' => $this->isRunningInConsole() ? 'console' : 'web',
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
        $this->line("Correções aplicadas: {$this->stats['corrections_applied']}");
        $this->line("Erros durante execução: {$this->stats['errors']}");

        // Informação sobre ambiente de execução
        $environment = $this->isRunningInConsole() ? 'Console (Terminal)' : 'Web (Interface)';
        $this->line("Ambiente de execução: {$environment}");

        if (! empty($this->issues)) {
            $this->newLine();
            $this->warn('🚨 RESUMO DOS PROBLEMAS ENCONTRADOS:');

            $issuesByType = [];
            $criticalIssuesCount = 0;

            foreach ($this->issues as $gigIssue) {
                foreach ($gigIssue['issues'] as $issue) {
                    $type = $issue['type'];
                    $severity = $issue['severity'];
                    $key = "{$type}_{$severity}";

                    if ($severity === 'critical') {
                        $criticalIssuesCount++;
                    }

                    if (! isset($issuesByType[$key])) {
                        $issuesByType[$key] = [
                            'type' => $type,
                            'severity' => $severity,
                            'count' => 0,
                            'gigs' => [],
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

            // Aviso especial para issues críticas em ambiente web
            if (! $this->isRunningInConsole() && $criticalIssuesCount > 0) {
                $this->newLine();
                $this->warn("⚠️  ATENÇÃO: {$criticalIssuesCount} issues críticas detectadas em ambiente web.");
                $this->warn('   Essas issues requerem correção manual ou execução via terminal com confirmação.');
                $this->warn('   Verifique os logs para detalhes completos.');
            }

            // Salvar relatório detalhado
            $this->saveDetailedReport();
        }

        $this->newLine();
        $this->info('✅ Auditoria concluída!');

        // Log final do resumo
        Log::info('GigDataAudit: Auditoria concluída', [
            'stats' => $this->stats,
            'environment' => $this->isRunningInConsole() ? 'console' : 'web',
            'total_issues_types' => count($issuesByType ?? []),
        ]);
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
        $reportPath = storage_path('logs/gig_audit_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
