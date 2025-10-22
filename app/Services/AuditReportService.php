<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AuditReportService
{
    protected array $availableAudits = [
        'settlements' => [
            'name' => 'Acertos Financeiros',
            'command' => 'gig:audit-settlements',
            'description' => 'Auditoria de acertos financeiros (pagamentos a artistas e bookers)',
            'icon' => 'fas fa-hand-holding-usd',
            'color' => 'blue',
            'critical_types' => ['payment_rule_violation', 'referential_integrity', 'value_divergence'],
        ],
        'payments' => [
            'name' => 'Parcelas de Pagamento',
            'command' => 'gig:audit-payments',
            'description' => 'Auditoria de parcelas de pagamento do cliente',
            'icon' => 'fas fa-money-bill-wave',
            'color' => 'green',
            'critical_types' => ['orphan_payments', 'payment_total_divergence', 'payment_status_inconsistency'],
        ],
        'business-rules' => [
            'name' => 'Regras de Negócio',
            'command' => 'gig:audit-business-rules',
            'description' => 'Validação de regras de negócio e cálculos de comissões',
            'icon' => 'fas fa-calculator',
            'color' => 'purple',
            'critical_types' => ['commission_exceeds_cache', 'liquid_commission_incorrect', 'invalid_commission_rate'],
        ],
        'currency' => [
            'name' => 'Moedas',
            'command' => 'gig:audit-currency',
            'description' => 'Consistência de moedas entre gigs, payments e costs',
            'icon' => 'fas fa-dollar-sign',
            'color' => 'yellow',
            'critical_types' => ['missing_currency', 'payment_currency_mismatch'],
        ],
        'costs' => [
            'name' => 'Custos',
            'command' => 'gig:audit-costs',
            'description' => 'Validação de custos das gigs e cost centers',
            'icon' => 'fas fa-receipt',
            'color' => 'orange',
            'critical_types' => ['orphan_cost', 'invalid_cost_value', 'missing_cost_center'],
        ],
        'duplicates' => [
            'name' => 'Duplicatas',
            'command' => 'gig:audit-duplicates',
            'description' => 'Detecção de gigs duplicadas e eventos similares',
            'icon' => 'fas fa-clone',
            'color' => 'red',
            'critical_types' => ['duplicate_contract_number'],
        ],
    ];

    /**
     * Retorna lista de auditorias disponíveis
     */
    public function getAvailableAudits(): array
    {
        return $this->availableAudits;
    }

    /**
     * Busca o relatório mais recente de uma auditoria específica
     */
    public function getLatestReport(string $auditType): ?array
    {
        $pattern = storage_path("logs/audit_{$auditType}_*.json");
        $files = glob($pattern);

        if (empty($files)) {
            return null;
        }

        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        $latestFile = $files[0];

        try {
            $content = File::get($latestFile);
            $report = json_decode($content, true);

            if (! $report) {
                return null;
            }

            $report['file_path'] = $latestFile;
            $report['file_name'] = basename($latestFile);
            $report['file_size'] = filesize($latestFile);
            $report['file_modified'] = Carbon::createFromTimestamp(filemtime($latestFile));

            return $report;
        } catch (\Exception $e) {
            Log::error("Erro ao ler relatório de auditoria: {$latestFile}", ['exception' => $e]);

            return null;
        }
    }

    /**
     * Busca todos os relatórios de uma auditoria específica
     */
    public function getAllReports(string $auditType, int $limit = 10): array
    {
        $pattern = storage_path("logs/audit_{$auditType}_*.json");
        $files = glob($pattern);

        if (empty($files)) {
            return [];
        }

        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

        // Limitar quantidade
        $files = array_slice($files, 0, $limit);

        $reports = [];

        foreach ($files as $file) {
            try {
                $content = File::get($file);
                $report = json_decode($content, true);

                if ($report) {
                    $report['file_path'] = $file;
                    $report['file_name'] = basename($file);
                    $report['file_size'] = filesize($file);
                    $report['file_modified'] = Carbon::createFromTimestamp(filemtime($file));
                    $reports[] = $report;
                }
            } catch (\Exception $e) {
                // Log::warning("Erro ao ler relatório: {$file}", ['exception' => $e]);
            }
        }

        return $reports;
    }

    /**
     * Gera health score geral do sistema baseado em todos os relatórios
     */
    public function calculateHealthScore(): array
    {
        $allReports = [];
        $totalIssues = 0;
        $totalCritical = 0;
        $totalWarnings = 0;
        $totalGigs = 0;

        foreach (array_keys($this->availableAudits) as $auditType) {
            $report = $this->getLatestReport($auditType);

            if ($report) {
                $allReports[$auditType] = $report;

                // Contar issues
                $stats = $report['stats'] ?? [];
                $totalIssues += $stats['issues_found'] ?? 0;

                // Contar critical e warnings
                $issues = $report['issues'] ?? [];
                foreach ($issues as $gigIssue) {
                    foreach ($gigIssue['issues'] ?? [] as $issue) {
                        if ($issue['severity'] === 'critical') {
                            $totalCritical++;
                        } elseif ($issue['severity'] === 'warning') {
                            $totalWarnings++;
                        }
                    }
                }

                // Pegar total de gigs da primeira auditoria que tiver
                if ($totalGigs === 0 && isset($stats['total_gigs'])) {
                    $totalGigs = $stats['total_gigs'];
                }
            }
        }

        // Calcular health score (0-100)
        // Score baseado na proporção de issues críticas vs total de gigs
        if ($totalGigs > 0) {
            $criticalRate = ($totalCritical / $totalGigs) * 100;
            $healthScore = max(0, 100 - $criticalRate);
        } else {
            $healthScore = 100;
        }

        // Classificar health status
        if ($healthScore >= 90) {
            $healthStatus = 'excellent';
            $healthColor = 'green';
            $healthEmoji = '✅';
        } elseif ($healthScore >= 70) {
            $healthStatus = 'good';
            $healthColor = 'blue';
            $healthEmoji = '👍';
        } elseif ($healthScore >= 50) {
            $healthStatus = 'fair';
            $healthColor = 'yellow';
            $healthEmoji = '⚠️';
        } elseif ($healthScore >= 30) {
            $healthStatus = 'poor';
            $healthColor = 'orange';
            $healthEmoji = '⚠️';
        } else {
            $healthStatus = 'critical';
            $healthColor = 'red';
            $healthEmoji = '🔴';
        }

        return [
            'health_score' => round($healthScore, 1),
            'health_status' => $healthStatus,
            'health_color' => $healthColor,
            'health_emoji' => $healthEmoji,
            'total_issues' => $totalIssues,
            'total_critical' => $totalCritical,
            'total_warnings' => $totalWarnings,
            'total_gigs' => $totalGigs,
            'reports' => $allReports,
            'last_updated' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Gera relatório consolidado de todas as auditorias
     */
    public function generateConsolidatedReport(): array
    {
        $healthScore = $this->calculateHealthScore();
        $auditSummaries = [];

        foreach ($this->availableAudits as $type => $config) {
            $report = $this->getLatestReport($type);

            $summary = [
                'type' => $type,
                'name' => $config['name'],
                'description' => $config['description'],
                'icon' => $config['icon'],
                'color' => $config['color'],
                'last_run' => null,
                'status' => 'not_run',
                'issues_found' => 0,
                'critical_issues' => 0,
                'warnings' => 0,
            ];

            if ($report) {
                $stats = $report['stats'] ?? [];
                $timestamp = $report['timestamp'] ?? null;

                $summary['last_run'] = $timestamp ? Carbon::parse($timestamp)->format('d/m/Y H:i') : null;
                $summary['issues_found'] = $stats['issues_found'] ?? 0;

                // Contar critical e warnings
                $criticalCount = 0;
                $warningCount = 0;

                foreach ($report['issues'] ?? [] as $gigIssue) {
                    foreach ($gigIssue['issues'] ?? [] as $issue) {
                        if ($issue['severity'] === 'critical') {
                            $criticalCount++;
                        } elseif ($issue['severity'] === 'warning') {
                            $warningCount++;
                        }
                    }
                }

                $summary['critical_issues'] = $criticalCount;
                $summary['warnings'] = $warningCount;

                // Definir status
                if ($criticalCount > 0) {
                    $summary['status'] = 'critical';
                } elseif ($warningCount > 0) {
                    $summary['status'] = 'warning';
                } else {
                    $summary['status'] = 'ok';
                }
            }

            $auditSummaries[] = $summary;
        }

        return [
            'health_score' => $healthScore,
            'audits' => $auditSummaries,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Limpa relatórios antigos (mantém apenas os N mais recentes)
     */
    public function cleanOldReports(int $keepLast = 30): array
    {
        $deleted = [];

        foreach (array_keys($this->availableAudits) as $auditType) {
            $pattern = storage_path("logs/audit_{$auditType}_*.json");
            $files = glob($pattern);

            if (count($files) <= $keepLast) {
                continue;
            }

            // Ordenar por data de modificação (mais recente primeiro)
            usort($files, fn ($a, $b) => filemtime($b) - filemtime($a));

            // Remover os mais antigos
            $filesToDelete = array_slice($files, $keepLast);

            foreach ($filesToDelete as $file) {
                try {
                    File::delete($file);
                    $deleted[] = basename($file);
                } catch (\Exception $e) {
                    Log::error("Erro ao deletar relatório: {$file}", ['exception' => $e]);
                }
            }
        }

        return $deleted;
    }

    /**
     * Busca issues específicas por tipo
     */
    public function findIssuesByType(string $issueType, ?string $auditType = null): array
    {
        $results = [];

        $auditTypes = $auditType ? [$auditType] : array_keys($this->availableAudits);

        foreach ($auditTypes as $type) {
            $report = $this->getLatestReport($type);

            if (! $report) {
                continue;
            }

            foreach ($report['issues'] ?? [] as $gigIssue) {
                foreach ($gigIssue['issues'] ?? [] as $issue) {
                    if ($issue['type'] === $issueType) {
                        $results[] = [
                            'audit_type' => $type,
                            'gig_id' => $gigIssue['gig_id'] ?? null,
                            'gig_date' => $gigIssue['gig_date'] ?? null,
                            'artist' => $gigIssue['artist'] ?? 'N/A',
                            'issue' => $issue,
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Estatísticas de tendência (comparar últimos N relatórios)
     */
    public function getTrends(string $auditType, int $limit = 7): array
    {
        $reports = $this->getAllReports($auditType, $limit);

        if (empty($reports)) {
            return [];
        }

        $trends = [
            'dates' => [],
            'issues_found' => [],
            'critical_count' => [],
            'warning_count' => [],
        ];

        foreach (array_reverse($reports) as $report) {
            $timestamp = $report['timestamp'] ?? null;
            if (! $timestamp) {
                continue;
            }

            $date = Carbon::parse($timestamp)->format('d/m');
            $trends['dates'][] = $date;

            $stats = $report['stats'] ?? [];
            $trends['issues_found'][] = $stats['issues_found'] ?? 0;

            // Contar critical e warnings
            $criticalCount = 0;
            $warningCount = 0;

            foreach ($report['issues'] ?? [] as $gigIssue) {
                foreach ($gigIssue['issues'] ?? [] as $issue) {
                    if ($issue['severity'] === 'critical') {
                        $criticalCount++;
                    } elseif ($issue['severity'] === 'warning') {
                        $warningCount++;
                    }
                }
            }

            $trends['critical_count'][] = $criticalCount;
            $trends['warning_count'][] = $warningCount;
        }

        return $trends;
    }
}
