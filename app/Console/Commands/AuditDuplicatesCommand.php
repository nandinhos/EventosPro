<?php

namespace App\Console\Commands;

use App\Models\Gig;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditDuplicatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gig:audit-duplicates
                            {--scan-only : Apenas escanear sem fazer correções}
                            {--include-deleted : Incluir gigs soft-deleted na análise}
                            {--date-from= : Data inicial para filtrar gigs (YYYY-MM-DD)}
                            {--date-to= : Data final para filtrar gigs (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Auditoria de gigs duplicadas - detecta contratos duplicados e eventos similares';

    protected array $issues = [];

    protected array $stats = [
        'total_gigs' => 0,
        'duplicates_found' => 0,
        'potential_duplicates' => 0,
        'errors' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando Auditoria de Duplicatas');
        $this->info('====================================');

        // Configurações
        $scanOnly = $this->option('scan-only');
        $includeDeleted = $this->option('include-deleted');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');

        // Mostrar configurações
        $this->displayConfiguration($scanOnly, $includeDeleted, $dateFrom, $dateTo);

        // Confirmar execução
        if (! $this->confirmExecution()) {
            $this->info('⏹️  Operação cancelada pelo usuário');

            return 0;
        }

        try {
            // Executar auditoria
            $this->performAudit($dateFrom, $dateTo, $includeDeleted);

            // Mostrar relatório final
            $this->displayFinalReport();

            return 0;
        } catch (Exception $e) {
            $this->error("❌ Erro durante a execução: {$e->getMessage()}");
            Log::error('AuditDuplicates Error', ['exception' => $e]);

            return 1;
        }
    }

    protected function displayConfiguration($scanOnly, $includeDeleted, $dateFrom, $dateTo)
    {
        $this->info('📋 Configurações:');
        $this->line('   Modo: '.($scanOnly ? 'Apenas Escaneamento' : 'Detecção e Relatório'));
        $this->line('   Incluir deletadas: '.($includeDeleted ? 'Sim' : 'Não'));

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

        $this->warn('⚠️  ATENÇÃO: Esta operação irá analisar gigs em busca de duplicatas.');
        $this->warn('   Nenhuma modificação será feita automaticamente.');
        $this->newLine();

        return $this->confirm('Deseja continuar?', true);
    }

    protected function performAudit($dateFrom, $dateTo, $includeDeleted)
    {
        $this->info('🔎 Analisando duplicatas por contract_number...');
        $this->checkDuplicateContractNumbers($dateFrom, $dateTo, $includeDeleted);

        $this->newLine();
        $this->info('🔎 Analisando eventos similares (mesmo artista + data + local)...');
        $this->checkSimilarEvents($dateFrom, $dateTo, $includeDeleted);

        $this->newLine();
        $this->info('🔎 Analisando gigs soft-deleted que podem ser duplicatas...');
        $this->checkSoftDeletedDuplicates($dateFrom, $dateTo);
    }

    protected function checkDuplicateContractNumbers($dateFrom, $dateTo, $includeDeleted)
    {
        // Query base
        $query = Gig::select('contract_number', DB::raw('COUNT(*) as count'), DB::raw('GROUP_CONCAT(id) as gig_ids'))
            ->whereNotNull('contract_number')
            ->where('contract_number', '!=', '')
            ->groupBy('contract_number')
            ->having('count', '>', 1);

        if ($dateFrom) {
            $query->where('gig_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('gig_date', '<=', $dateTo);
        }

        if ($includeDeleted) {
            $query = $query->withTrashed();
        }

        $duplicates = $query->get();

        $this->stats['total_gigs'] = Gig::when($includeDeleted, fn ($q) => $q->withTrashed())
            ->when($dateFrom, fn ($q) => $q->where('gig_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('gig_date', '<=', $dateTo))
            ->count();

        foreach ($duplicates as $duplicate) {
            $gigIds = explode(',', $duplicate->gig_ids);
            $gigs = Gig::when($includeDeleted, fn ($q) => $q->withTrashed())
                ->whereIn('id', $gigIds)
                ->with(['artist', 'booker'])
                ->get();

            $this->stats['duplicates_found']++;

            $this->issues[] = [
                'type' => 'duplicate_contract_number',
                'severity' => 'critical',
                'contract_number' => $duplicate->contract_number,
                'count' => $duplicate->count,
                'gigs' => $gigs->map(function ($gig) {
                    return [
                        'id' => $gig->id,
                        'gig_date' => $gig->gig_date->format('Y-m-d'),
                        'artist' => $gig->artist->name ?? 'N/A',
                        'booker' => $gig->booker->name ?? 'N/A',
                        'cache_value' => $gig->cache_value,
                        'currency' => $gig->currency,
                        'location' => $gig->location_event_details,
                        'contract_status' => $gig->contract_status,
                        'deleted_at' => $gig->deleted_at?->format('Y-m-d H:i:s'),
                    ];
                })->toArray(),
                'suggested_action' => 'Revisar contratos e verificar se são duplicatas ou apenas mesmo número de contrato',
            ];
        }

        if ($duplicates->count() > 0) {
            $this->warn("   🔴 {$duplicates->count()} contract_number(s) duplicado(s) encontrado(s)");
        } else {
            $this->info('   ✅ Nenhum contract_number duplicado encontrado');
        }
    }

    protected function checkSimilarEvents($dateFrom, $dateTo, $includeDeleted)
    {
        // Buscar gigs agrupadas por artista + data + primeiras palavras do local
        $query = Gig::with(['artist', 'booker'])
            ->whereNotNull('artist_id')
            ->whereNotNull('gig_date');

        if ($dateFrom) {
            $query->where('gig_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('gig_date', '<=', $dateTo);
        }

        if ($includeDeleted) {
            $query = $query->withTrashed();
        }

        $gigs = $query->get();

        // Agrupar manualmente por similaridade
        $groups = [];
        foreach ($gigs as $gig) {
            $key = $gig->artist_id.'_'.$gig->gig_date->format('Y-m-d').'_'.substr($gig->location_event_details ?? '', 0, 20);

            if (! isset($groups[$key])) {
                $groups[$key] = [];
            }

            $groups[$key][] = $gig;
        }

        // Filtrar apenas grupos com mais de 1 gig
        $similarGroups = array_filter($groups, fn ($group) => count($group) > 1);

        foreach ($similarGroups as $group) {
            $firstGig = $group[0];

            $this->stats['potential_duplicates']++;

            $this->issues[] = [
                'type' => 'similar_events',
                'severity' => 'warning',
                'artist' => $firstGig->artist->name ?? 'N/A',
                'gig_date' => $firstGig->gig_date->format('Y-m-d'),
                'location_prefix' => substr($firstGig->location_event_details ?? '', 0, 50),
                'count' => count($group),
                'gigs' => collect($group)->map(function ($gig) {
                    return [
                        'id' => $gig->id,
                        'contract_number' => $gig->contract_number ?? 'N/A',
                        'booker' => $gig->booker->name ?? 'N/A',
                        'cache_value' => $gig->cache_value,
                        'currency' => $gig->currency,
                        'location' => $gig->location_event_details,
                        'contract_status' => $gig->contract_status,
                        'deleted_at' => $gig->deleted_at?->format('Y-m-d H:i:s'),
                    ];
                })->toArray(),
                'suggested_action' => 'Verificar se são eventos duplicados ou múltiplas apresentações no mesmo dia',
            ];
        }

        if (count($similarGroups) > 0) {
            $this->warn('   🟡 '.count($similarGroups).' grupo(s) de eventos similares encontrado(s)');
        } else {
            $this->info('   ✅ Nenhum evento similar encontrado');
        }
    }

    protected function checkSoftDeletedDuplicates($dateFrom, $dateTo)
    {
        // Buscar gigs soft-deleted que podem ter sido recriadas
        $deletedGigs = Gig::onlyTrashed()
            ->with(['artist', 'booker'])
            ->when($dateFrom, fn ($q) => $q->where('gig_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('gig_date', '<=', $dateTo))
            ->get();

        $potentialRestores = [];

        foreach ($deletedGigs as $deletedGig) {
            // Buscar gigs ativas com mesmo contract_number
            $activeWithSameContract = Gig::where('contract_number', $deletedGig->contract_number)
                ->whereNotNull('contract_number')
                ->where('contract_number', '!=', '')
                ->where('id', '!=', $deletedGig->id)
                ->exists();

            if ($activeWithSameContract) {
                $potentialRestores[] = [
                    'deleted_gig_id' => $deletedGig->id,
                    'contract_number' => $deletedGig->contract_number,
                    'artist' => $deletedGig->artist->name ?? 'N/A',
                    'gig_date' => $deletedGig->gig_date->format('Y-m-d'),
                    'deleted_at' => $deletedGig->deleted_at->format('Y-m-d H:i:s'),
                ];
            }
        }

        if (count($potentialRestores) > 0) {
            $this->issues[] = [
                'type' => 'soft_deleted_duplicates',
                'severity' => 'warning',
                'description' => 'Gigs soft-deleted com contract_number que existe em gigs ativas',
                'count' => count($potentialRestores),
                'gigs' => $potentialRestores,
                'suggested_action' => 'Avaliar se gigs deletadas devem ser restauradas ou permanentemente removidas',
            ];

            $this->warn('   🟡 '.count($potentialRestores).' gig(s) soft-deleted com possível duplicata ativa');
        } else {
            $this->info('   ✅ Nenhuma gig soft-deleted duplicada encontrada');
        }
    }

    protected function displayFinalReport()
    {
        $this->newLine();
        $this->info('📊 RELATÓRIO FINAL - AUDITORIA DE DUPLICATAS');
        $this->info('============================================');
        $this->line("Total de gigs analisadas: {$this->stats['total_gigs']}");
        $this->line("Duplicatas exatas encontradas: {$this->stats['duplicates_found']}");
        $this->line("Possíveis duplicatas (similares): {$this->stats['potential_duplicates']}");
        $this->line("Erros durante execução: {$this->stats['errors']}");

        if (! empty($this->issues)) {
            $this->newLine();
            $this->warn('🚨 RESUMO DOS PROBLEMAS ENCONTRADOS:');

            $issuesByType = $this->groupIssuesByType();

            foreach ($issuesByType as $type => $data) {
                $emoji = $this->getSeverityEmoji($data['severity']);
                $this->line("{$emoji} {$type}: {$data['count']} ocorrência(s)");
            }

            $this->saveDetailedReport();
        } else {
            $this->newLine();
            $this->info('✅ Nenhuma duplicata encontrada!');
        }

        $this->newLine();
        $this->info('✅ Auditoria de Duplicatas concluída!');
        $this->newLine();
        $this->comment('💡 Dica: Revise o relatório JSON para detalhes completos de cada duplicata encontrada.');
    }

    protected function groupIssuesByType(): array
    {
        $grouped = [];

        foreach ($this->issues as $issue) {
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
        $reportPath = storage_path('logs/audit_duplicates_'.now()->format('Y-m-d_H-i-s').'.json');

        $report = [
            'timestamp' => now()->toISOString(),
            'command' => 'gig:audit-duplicates',
            'stats' => $this->stats,
            'issues' => $this->issues,
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("📄 Relatório detalhado salvo em: {$reportPath}");
    }
}
