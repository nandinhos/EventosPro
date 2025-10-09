<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuditController extends Controller
{
    protected GigFinancialCalculatorService $financialCalculator;

    public function __construct(GigFinancialCalculatorService $financialCalculator)
    {
        $this->financialCalculator = $financialCalculator;
    }

    /**
     * Exibe a página principal de auditoria com lista de gigs e divergências financeiras.
     */
    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->input('search'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'currency' => $request->input('currency'),
        ];

        // Query base - ordenação ascendente por data de vencimento
        $query = Gig::with(['artist', 'booker', 'payments'])
            ->whereNotNull('gig_date')
            ->orderBy('gig_date', 'asc');

        // Aplicar filtros
        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('contract_number', 'like', '%'.$filters['search'].'%')
                    ->orWhere('location_event_details', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('artist', function ($artistQuery) use ($filters) {
                        $artistQuery->where('name', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        if ($filters['start_date']) {
            $query->where('gig_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $query->where('gig_date', '<=', $filters['end_date']);
        }

        if ($filters['currency'] && $filters['currency'] !== 'all') {
            $query->where('currency', $filters['currency']);
        }

        $gigs = $query->get();

        // Processar cada gig com nova lógica de negócio
        $auditResults = [];
        foreach ($gigs as $gig) {
            $result = $this->processGigAudit($gig);
            if ($result['needs_audit']) {
                $auditResults[] = $result;
            }
        }

        // Agrupar por categorias
        $groupedResults = $this->groupAuditResults($auditResults);

        // Dados para filtros
        $currencies = Gig::distinct('currency')->whereNotNull('currency')->pluck('currency')->sort();

        return view('audit.index', compact(
            'groupedResults',
            'currencies',
            'filters'
        ));
    }

    /**
     * Processa auditoria de uma gig específica com nova lógica de negócio.
     */
    private function processGigAudit(Gig $gig): array
    {
        $valorContrato = $gig->cache_value ?? 0;

        // Somatório de parcelas pagas (confirmed_at != null)
        $totalPago = $gig->payments->whereNotNull('confirmed_at')->sum('received_value_actual') ?? 0;

        // Somatório de parcelas não pagas (confirmed_at = null)
        $totalNaoPago = $gig->payments->whereNull('confirmed_at')->sum('due_value') ?? 0;

        // Confronto inicial: soma de pagas + não pagas deve ser igual ao valor do contrato
        $somaTotal = $totalPago + $totalNaoPago;
        $confrontoOk = abs($valorContrato - $somaTotal) <= 0.01;

        // Cálculo da diferença para exibição
        $diferenca = $valorContrato - $somaTotal;

        $categoria = null;
        $observacao = '';
        $needsAudit = true;

        if (! $confrontoOk) {
            // 1) Discrepância de valores onde a conta não bate
            if ($somaTotal == 0) {
                $categoria = 'falta_lancamento';
                $observacao = 'Não há lançamentos de pagamento';
            } else {
                $categoria = 'discrepancia_valores';
                $observacao = $diferenca > 0 ?
                    'Falta R$ '.number_format($diferenca, 2, ',', '.') :
                    'Excesso de R$ '.number_format(abs($diferenca), 2, ',', '.');
            }
        } else {
            // Confronto OK - verificar payment_status
            if ($gig->payment_status === 'pago') {
                // Verificar se há parcelas pendentes mesmo com status 'pago'
                $parcelasPendentes = $gig->payments->whereNull('confirmed_at')->count();

                if ($parcelasPendentes > 0) {
                    // Nova categoria: Gig marcada como paga mas tem parcelas em aberto
                    $categoria = 'gigs_pago_com_parcelas_abertas';
                    $observacao = "Status 'pago' mas possui {$parcelasPendentes} parcela(s) não confirmada(s)";
                    $needsAudit = true;
                } else {
                    // Totalmente OK - não precisa ser listado
                    $needsAudit = false;
                }
            } else {
                // 3) ou 4) Verificar se evento já aconteceu
                $hoje = now()->startOfDay();
                $gigDate = $gig->gig_date ? Carbon::parse($gig->gig_date)->startOfDay() : null;

                if ($gigDate && $gigDate->lt($hoje)) {
                    $categoria = 'gigs_vencidas';
                    // Refinar observação baseada no tipo de pagamento
                    if (abs($valorContrato - $totalPago) <= 0.01) {
                        // Caso 1: Contrato = Total Pago
                        $observacao = 'Evento já aconteceu - Alterar status para "pago"';
                    } elseif (abs($valorContrato - $totalNaoPago) <= 0.01) {
                        // Caso 2: Contrato = Total Não Pago
                        $observacao = 'Evento já aconteceu - Verificar pagamento';
                    } else {
                        $observacao = 'Evento já aconteceu - Alterar status para "pago"';
                    }
                } else {
                    $categoria = 'gigs_a_vencer';
                    $observacao = 'Evento ainda não aconteceu - Aguardando';
                }
            }
        }

        return [
            'gig' => $gig,
            'valor_contrato' => $valorContrato,
            'total_pago' => $totalPago,
            'total_nao_pago' => $totalNaoPago,
            'diferenca' => $diferenca,
            'soma_total' => $somaTotal,
            'confronto_ok' => $confrontoOk,
            'categoria' => $categoria,
            'observacao' => $observacao,
            'needs_audit' => $needsAudit,
            'payment_status' => $gig->payment_status,
        ];
    }

    /**
     * Gera observações baseadas na análise dos dados financeiros.
     */
    private function generateObservation(Gig $gig, float $divergencia, float $totalPago, float $totalPendente, float $valorContrato): string
    {
        $observacoes = [];

        // Análise da divergência
        if (abs($divergencia) <= 0.01) {
            $observacoes[] = 'Valores conferem';
        } elseif ($divergencia > 0) {
            $observacoes[] = 'Falta receber R$ '.number_format($divergencia, 2, ',', '.');
        } else {
            $observacoes[] = 'Excesso de R$ '.number_format(abs($divergencia), 2, ',', '.');
        }

        // Análise do status de pagamento
        if ($totalPago == 0 && $totalPendente == 0) {
            $observacoes[] = 'Nenhum pagamento registrado';
        } elseif ($totalPago == $valorContrato) {
            $observacoes[] = 'Totalmente pago';
        } elseif ($totalPago > 0 && $totalPendente > 0) {
            $observacoes[] = 'Pagamento parcial';
        }

        // Verificar se há pagamentos em atraso
        $pagamentosVencidos = $gig->payments()->whereNull('confirmed_at')
            ->where('due_date', '<', now())
            ->count();

        if ($pagamentosVencidos > 0) {
            $observacoes[] = "{$pagamentosVencidos} pagamento(s) vencido(s)";
        }

        return implode(' | ', $observacoes);
    }

    /**
     * Retorna o status da divergência para estilização.
     */
    private function getStatusDivergencia(float $divergencia): string
    {
        if (abs($divergencia) <= 0.01) {
            return 'ok'; // Verde
        } elseif ($divergencia > 0) {
            return 'falta'; // Amarelo/Laranja
        } else {
            return 'excesso'; // Vermelho
        }
    }

    /**
     * Exibe detalhes de auditoria para uma gig específica.
     */
    public function show(Gig $gig): View
    {
        $gig->loadMissing(['artist', 'booker', 'payments' => function ($query) {
            $query->orderBy('due_date', 'asc');
        }, 'gigCosts.costCenter']);

        $auditData = $this->calculateAuditData($gig);

        // Dados financeiros detalhados
        $financialData = [
            'contractValueBrl' => $gig->cache_value_brl,
            'totalReceivedBrl' => $gig->total_received_brl,
            'grossCashBrl' => $this->financialCalculator->calculateGrossCashBrl($gig),
            'agencyCommissionBrl' => $this->financialCalculator->calculateAgencyGrossCommissionBrl($gig),
            'bookerCommissionBrl' => $this->financialCalculator->calculateBookerCommissionBrl($gig),
            'totalExpensesBrl' => $this->financialCalculator->calculateTotalConfirmedExpensesBrl($gig),
        ];

        return view('audit.show', [
            'gig' => $gig,
            'auditData' => $auditData,
            'financialData' => $financialData,
        ]);
    }

    /**
     * Export audit data to Excel
     */
    public function export(Request $request)
    {
        $query = Gig::with(['artist', 'booker', 'payments'])
            ->select('gigs.*')
            ->leftJoin('artists', 'gigs.artist_id', '=', 'artists.id')
            ->leftJoin('bookers', 'gigs.booker_id', '=', 'bookers.id');

        // Apply same filters as index method
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('gigs.contract_number', 'like', "%{$searchTerm}%")
                    ->orWhere('gigs.location_event_details', 'like', "%{$searchTerm}%")
                    ->orWhere('artists.name', 'like', "%{$searchTerm}%")
                    ->orWhere('bookers.name', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->where('gigs.gig_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('gigs.gig_date', '<=', $request->input('end_date'));
        }

        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            $query->where('gigs.currency', $request->input('currency'));
        }

        if ($request->filled('payment_status')) {
            $query->where('gigs.payment_status', $request->input('payment_status'));
        }

        $gigs = $query->get();

        // Calculate audit data for each gig
        $auditData = [];
        foreach ($gigs as $gig) {
            $auditInfo = $this->calculateAuditData($gig);
            $auditData[$gig->id] = $auditInfo;
        }

        // Filter by divergence if requested
        if ($request->filled('has_divergence') && $request->boolean('has_divergence')) {
            $gigs = $gigs->filter(function ($gig) use ($auditData) {
                return abs($auditData[$gig->id]['divergencia']) > 0.01;
            });
        }

        // Create CSV content
        $csvData = [];
        $csvData[] = [
            'Data Gig',
            'Artista',
            'Booker',
            'Local',
            'Número Contrato',
            'Moeda',
            'Valor Contrato',
            'Total Pago',
            'Total Pendente',
            'Divergência',
            'Status',
            'Observação',
        ];

        foreach ($gigs as $gig) {
            $audit = $auditData[$gig->id] ?? null;
            if ($audit) {
                $csvData[] = [
                    $gig->gig_date ? $gig->gig_date->format('d/m/Y') : '',
                    $gig->artist->name ?? '',
                    $gig->booker->name ?? '',
                    $gig->location_event_details ?? '',
                    $gig->contract_number ?? '',
                    $gig->currency ?? '',
                    number_format($audit['valor_contrato'], 2, ',', '.'),
                    number_format($audit['total_pago'], 2, ',', '.'),
                    number_format($audit['total_pendente'], 2, ',', '.'),
                    number_format($audit['divergencia'], 2, ',', '.'),
                    $audit['status_divergencia'],
                    $audit['observacao'],
                ];
            }
        }

        // Generate CSV file
        $filename = 'auditoria_gigs_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($csvData as $row) {
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Agrupa os resultados de auditoria por categorias.
     */
    private function groupAuditResults(array $auditResults): array
    {
        $groups = [
            'discrepancia_valores' => [
                'title' => 'Gigs com Discrepância de Valores',
                'description' => 'Confronto entre payments e contrato não confere',
                'items' => [],
                'color' => 'red',
            ],
            'falta_lancamento' => [
                'title' => 'Falta Lançamento de Pagamento',
                'description' => 'Não há lançamentos de pagamento para o gig',
                'items' => [],
                'color' => 'orange',
            ],
            'gigs_pago_com_parcelas_abertas' => [
                'title' => 'Gigs com Status Pago, com Parcelas em Aberto',
                'description' => 'Gigs marcadas como "pago" mas possuem parcelas não confirmadas',
                'items' => [],
                'color' => 'purple',
            ],
            'gigs_vencidas' => [
                'title' => 'Gigs Vencidas',
                'description' => 'Evento já aconteceu mas status não é "pago"',
                'items' => [],
                'color' => 'yellow',
            ],
            'gigs_a_vencer' => [
                'title' => 'Gigs a Vencer',
                'description' => 'Evento ainda não aconteceu',
                'items' => [],
                'color' => 'blue',
            ],
        ];

        foreach ($auditResults as $result) {
            if ($result['categoria'] && isset($groups[$result['categoria']])) {
                $groups[$result['categoria']]['items'][] = $result;
            }
        }

        // Remover grupos vazios
        return array_filter($groups, function ($group) {
            return count($group['items']) > 0;
        });
    }

    /**
     * Calcula os dados de auditoria para uma gig específica (método mantido para compatibilidade).
     */
    private function calculateAuditData(Gig $gig): array
    {
        try {
            // Valor do contrato na moeda original
            $valorContrato = (float) ($gig->cache_value ?? 0);

            // Total já recebido (confirmado) na moeda original
            $totalPago = $this->financialCalculator->calculateTotalReceivedInOriginalCurrency($gig);

            // Total ainda a receber (pendente) na moeda original
            $totalPendente = $this->financialCalculator->calculateTotalReceivableInOriginalCurrency($gig);

            // Cálculo da divergência
            // Divergência = Valor Contrato - (Total Pago + Total Pendente)
            $divergencia = $valorContrato - ($totalPago + $totalPendente);

            // Observações baseadas na análise
            $observacao = $this->generateObservation($gig, $divergencia, $totalPago, $totalPendente, $valorContrato);

            Log::debug("[AuditController] Gig ID {$gig->id}: Contrato={$valorContrato}, Pago={$totalPago}, Pendente={$totalPendente}, Divergência={$divergencia}");

            return [
                'valor_contrato' => $valorContrato,
                'total_pago' => $totalPago,
                'total_pendente' => $totalPendente,
                'divergencia' => $divergencia,
                'observacao' => $observacao,
                'tem_divergencia' => abs($divergencia) > 0.01, // Considera divergência significativa > R$ 0,01
                'status_divergencia' => $this->getStatusDivergencia($divergencia),
            ];

        } catch (Exception $e) {
            Log::error("[AuditController] Erro ao calcular auditoria para Gig ID {$gig->id}: ".$e->getMessage());

            return [
                'valor_contrato' => 0,
                'total_pago' => 0,
                'total_pendente' => 0,
                'divergencia' => 0,
                'observacao' => 'Erro no cálculo: '.$e->getMessage(),
                'tem_divergencia' => false,
                'status_divergencia' => 'erro',
            ];
        }
    }

    /**
     * Exibe a página de auditoria de dados das gigs
     */
    public function dataAudit(): View
    {
        return view('audit.data-audit');
    }

    /**
     * Executa o comando de auditoria de dados e retorna os resultados
     */
    public function runDataAudit(Request $request)
    {
        $request->validate([
            'scan_only' => 'required|in:true,false,1,0',
            'batch_size' => 'required|numeric|min:1|max:1000',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            // Processar parâmetros
            $scanOnly = $request->scan_only;
            $batchSize = $request->batch_size;
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Preparar parâmetros do comando
            $params = [];

            // Adicionar filtros de data se fornecidos
            if ($dateFrom) {
                $params['--date-from'] = $dateFrom;
            }
            if ($dateTo) {
                $params['--date-to'] = $dateTo;
            }

            // Processar modo de correção
            if ($scanOnly === 'true' || $scanOnly === '1') {
                $params['--scan-only'] = true;
            } else {
                // Usar --auto-fix apenas quando não for scan-only para evitar confirmação interativa
                $params['--auto-fix'] = true;
            }

            // Definir batch size
            $params['--batch-size'] = $batchSize;

            // Capturar output do comando
            $exitCode = Artisan::call('gig:audit-data', $params);
            $output = Artisan::output();

            // Buscar o arquivo de relatório mais recente
            $reportPath = $this->getLatestAuditReport();
            $reportData = null;

            if ($reportPath && file_exists($reportPath)) {
                $reportData = json_decode(file_get_contents($reportPath), true);
            }

            return response()->json([
                'success' => $exitCode === 0,
                'output' => $output,
                'report' => $reportData,
                'report_path' => $reportPath,
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao executar auditoria de dados', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao executar auditoria: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna os dados das gigs com problemas para exibição na tabela
     */
    public function getAuditIssues(Request $request)
    {
        try {
            $reportPath = $request->input('report_path');

            if (! $reportPath || ! file_exists($reportPath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Relatório não encontrado',
                ], 404);
            }

            $reportData = json_decode(file_get_contents($reportPath), true);

            if (! $reportData || ! isset($reportData['issues'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Dados do relatório inválidos',
                ], 400);
            }

            // Processar dados para a tabela
            $tableData = [];
            foreach ($reportData['issues'] as $gigIssue) {
                $gig = Gig::with(['artist', 'booker'])->find($gigIssue['gig_id']);

                if (! $gig) {
                    continue;
                }

                foreach ($gigIssue['issues'] as $issue) {
                    $tableData[] = [
                        'gig_id' => $gig->id,
                        'gig_date' => $gig->gig_date->format('d/m/Y'),
                        'artist_name' => $gig->artist->name ?? 'N/A',
                        'booker_name' => $gig->booker->name ?? 'N/A',
                        'contract_number' => $gig->contract_number ?? 'N/A',
                        'issue_type' => $issue['type'],
                        'severity' => $issue['severity'],
                        'description' => $issue['description'],
                        'field' => $issue['field'] ?? null,
                        'current_value' => $issue['current_value'] ?? '',
                        'suggested_value' => $issue['suggested_value'] ?? '',
                        'suggested_action' => $issue['suggested_action'],
                        'relation_id' => $issue['cost_id'] ?? $issue['payment_id'] ?? null,
                        'can_fix' => isset($issue['suggested_value']) && isset($issue['field']) && ! empty($issue['field']),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $tableData,
                'stats' => $reportData['stats'] ?? [],
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao buscar issues de auditoria', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao buscar dados: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplica uma correção específica
     */
    public function applyFix(Request $request)
    {
        $request->validate([
            'gig_id' => 'required|integer|exists:gigs,id',
            'field' => 'required|string',
            'new_value' => 'required|string',
            'issue_type' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $gig = Gig::findOrFail($request->integer('gig_id'));
            $field = $request->input('field');
            $newValue = $request->input('new_value');
            $issueType = $request->input('issue_type');

            // Validar se o campo pode ser editado
            // Lista completa de campos que os comandos de auditoria podem corrigir
            $allowedFields = [
                // Campos do modelo Gig
                'artist_payment_status',
                'booker_payment_status',
                'artist_id',
                'booker_id',
                'cache_value',
                'currency',
                'contract_date',
                'agency_commission_value',
                'booker_commission_value',
                'liquid_commission_value',
                'contract_status',
                'agency_commission_rate',
                'booker_commission_rate',
                'payment_status',
                'confirmed_at',
                'gig_id',

                // Campos do modelo Settlement
                'settlement_date',
                'artist_payment_value',
                'artist_payment_paid_at',
                'artist_payment_proof',
                'booker_commission_value_paid',
                'booker_commission_paid_at',
                'booker_commission_proof',

                // Campos relacionados (payments e costs) - formato especial
                'payments.due_value',
                'payments.received_value_actual',
                'payments.currency',
                'payments.confirmed_at',
                'costs.currency',
                'costs.value',
                'costs.cost_center_id',
                'costs.is_confirmed',
            ];

            // Campos que pertencem ao Settlement (não à Gig)
            $settlementFields = [
                'settlement_date',
                'artist_payment_value',
                'artist_payment_paid_at',
                'artist_payment_proof',
                'booker_commission_value_paid',
                'booker_commission_paid_at',
                'booker_commission_proof',
            ];

            if (! in_array($field, $allowedFields)) {
                throw new Exception("Campo '{$field}' não pode ser editado via interface");
            }

            // Aplicar correção
            $oldValue = null;

            // Verificar se é um campo de Settlement
            if (in_array($field, $settlementFields)) {
                // Campos de Settlement
                $settlement = $gig->settlement;

                // Se não existe settlement, criar um
                if (! $settlement) {
                    $settlement = new \App\Models\Settlement;
                    $settlement->gig_id = $gig->id;
                    $settlement->settlement_date = $gig->gig_date; // Data padrão
                }

                $oldValue = $settlement->$field;
                $settlement->$field = $newValue;
                $settlement->save();

                // Atualizar status na Gig se necessário
                if ($field === 'artist_payment_paid_at' && $newValue) {
                    $gig->artist_payment_status = 'pago';
                    $gig->save();
                } elseif ($field === 'booker_commission_paid_at' && $newValue) {
                    $gig->booker_payment_status = 'pago';
                    $gig->save();
                }
            } elseif (str_contains($field, '.')) {
                // Campos relacionados (payments.* ou costs.*)
                [$relation, $relationField] = explode('.', $field, 2);

                // Para campos relacionados, precisamos do ID específico
                $relationId = $request->input('relation_id');

                if ($relation === 'payments' && $relationId) {
                    $payment = $gig->payments()->find($relationId);
                    if ($payment) {
                        $oldValue = $payment->$relationField;
                        $payment->$relationField = $newValue;
                        $payment->save();
                    }
                } elseif ($relation === 'costs' && $relationId) {
                    $cost = $gig->gigCosts()->find($relationId);
                    if ($cost) {
                        $oldValue = $cost->$relationField;

                        // Converter valores booleanos string para boolean
                        $finalValue = $newValue;
                        if ($newValue === 'true' || $newValue === 'false') {
                            $finalValue = $newValue === 'true';
                        }

                        $cost->$relationField = $finalValue;
                        $cost->save();
                    }
                } else {
                    throw new Exception("ID do relacionamento não fornecido para campo '{$field}'");
                }
            } else {
                // Campo direto do Gig
                $oldValue = $gig->$field;
                $gig->$field = $newValue;
                $gig->save();
            }

            DB::commit();

            // Log da correção
            Log::info('Correção aplicada via interface web', [
                'gig_id' => $gig->id,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'issue_type' => $issueType,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correção aplicada com sucesso',
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao aplicar correção', [
                'gig_id' => $request->integer('gig_id'),
                'field' => $request->input('field'),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao aplicar correção: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplica correções em lote para múltiplas issues.
     */
    public function applyBulkFix(Request $request)
    {
        $request->validate([
            'fixes' => 'required|array|min:1',
            'fixes.*.gig_id' => 'required|integer|exists:gigs,id',
            'fixes.*.field' => 'required|string',
            'fixes.*.new_value' => 'required',
            'fixes.*.issue_type' => 'required|string',
        ]);

        $fixes = $request->input('fixes');
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        // Lista de campos editáveis (mesmo whitelist do método individual)
        $editableFields = [
            // Campos do modelo Gig
            'artist_payment_status',
            'booker_payment_status',
            'artist_id',
            'booker_id',
            'cache_value',
            'currency',
            'contract_date',
            'agency_commission_value',
            'booker_commission_value',
            'liquid_commission_value',
            'contract_status',
            'agency_commission_rate',
            'booker_commission_rate',
            'payment_status',
            'confirmed_at',
            'gig_id',

            // Campos do modelo Settlement
            'settlement_date',
            'artist_payment_value',
            'artist_payment_paid_at',
            'artist_payment_proof',
            'booker_commission_value_paid',
            'booker_commission_paid_at',
            'booker_commission_proof',

            // Campos relacionados (payments e costs) - formato especial
            'payments.due_value',
            'payments.received_value_actual',
            'payments.currency',
            'payments.confirmed_at',
            'costs.currency',
            'costs.value',
            'costs.cost_center_id',
            'costs.is_confirmed',
        ];

        // Campos que pertencem ao Settlement (não à Gig)
        $settlementFields = [
            'settlement_date',
            'artist_payment_value',
            'artist_payment_paid_at',
            'artist_payment_proof',
            'booker_commission_value_paid',
            'booker_commission_paid_at',
            'booker_commission_proof',
        ];

        DB::beginTransaction();

        try {
            foreach ($fixes as $index => $fix) {
                try {
                    $gigId = $fix['gig_id'];
                    $field = $fix['field'];
                    $newValue = $fix['new_value'];
                    $issueType = $fix['issue_type'];

                    // Verificar se o campo é editável
                    if (! in_array($field, $editableFields)) {
                        $results[$index] = [
                            'success' => false,
                            'error' => "Campo '{$field}' não é editável",
                            'gig_id' => $gigId,
                        ];
                        $errorCount++;

                        continue;
                    }

                    // Buscar o gig
                    $gig = Gig::findOrFail($gigId);

                    $oldValue = null;

                    // Verificar se é um campo de Settlement
                    if (in_array($field, $settlementFields)) {
                        // Campos de Settlement
                        $settlement = $gig->settlement;

                        // Se não existe settlement, criar um
                        if (! $settlement) {
                            $settlement = new \App\Models\Settlement;
                            $settlement->gig_id = $gig->id;
                            $settlement->settlement_date = $gig->gig_date; // Data padrão
                        }

                        $oldValue = $settlement->$field;
                        $settlement->$field = $newValue;
                        $settlement->save();

                        // Atualizar status na Gig se necessário
                        if ($field === 'artist_payment_paid_at' && $newValue) {
                            $gig->artist_payment_status = 'pago';
                            $gig->save();
                        } elseif ($field === 'booker_commission_paid_at' && $newValue) {
                            $gig->booker_payment_status = 'pago';
                            $gig->save();
                        }
                    } elseif (str_contains($field, '.')) {
                        // Campos relacionados (payments.* ou costs.*)
                        [$relation, $relationField] = explode('.', $field, 2);

                        // Para campos relacionados, precisamos do ID específico
                        $relationId = $fix['relation_id'] ?? null;

                        if ($relation === 'payments' && $relationId) {
                            $payment = $gig->payments()->find($relationId);
                            if ($payment) {
                                $oldValue = $payment->$relationField;
                                $payment->$relationField = $newValue;
                                $payment->save();
                            }
                        } elseif ($relation === 'costs' && $relationId) {
                            $cost = $gig->gigCosts()->find($relationId);
                            if ($cost) {
                                $oldValue = $cost->$relationField;

                                // Converter valores booleanos string para boolean
                                $finalValue = $newValue;
                                if ($newValue === 'true' || $newValue === 'false') {
                                    $finalValue = $newValue === 'true';
                                }

                                $cost->$relationField = $finalValue;
                                $cost->save();
                            }
                        } else {
                            throw new Exception("ID do relacionamento não fornecido para campo '{$field}'");
                        }
                    } else {
                        // Campo direto do Gig
                        $oldValue = $gig->$field;
                        $gig->update([$field => $newValue]);
                    }

                    // Log da correção
                    Log::info('Bulk fix applied', [
                        'gig_id' => $gigId,
                        'field' => $field,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                        'issue_type' => $issueType,
                    ]);

                    $results[$index] = [
                        'success' => true,
                        'gig_id' => $gigId,
                        'new_value' => $newValue,
                    ];
                    $successCount++;

                } catch (Exception $e) {
                    $results[$index] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'gig_id' => $fix['gig_id'] ?? null,
                    ];
                    $errorCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => $errorCount === 0,
                'message' => $errorCount === 0
                    ? "Todas as {$successCount} correções foram aplicadas com sucesso"
                    : "{$successCount} correções aplicadas, {$errorCount} falharam",
                'results' => $results,
                'summary' => [
                    'total' => count($fixes),
                    'success' => $successCount,
                    'errors' => $errorCount,
                ],
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk fix failed', [
                'error' => $e->getMessage(),
                'fixes_count' => count($fixes),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao aplicar correções em lote: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca o arquivo de relatório mais recente
     */
    private function getLatestAuditReport(): ?string
    {
        $logsPath = storage_path('logs');
        $files = glob($logsPath.'/gig_audit_*.json');

        if (empty($files)) {
            return null;
        }

        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files[0];
    }

    /**
     * Retorna lista de auditorias disponíveis (Phase 3)
     */
    public function getAvailableAudits()
    {
        try {
            $auditService = app(\App\Services\AuditReportService::class);
            $audits = $auditService->getAvailableAudits();

            // Converter para formato que o frontend espera
            $formattedAudits = [];
            foreach ($audits as $type => $config) {
                $formattedAudits[] = [
                    'type' => $type,
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'command' => $config['command'],
                ];
            }

            return response()->json([
                'success' => true,
                'audits' => $formattedAudits,
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao buscar auditorias disponíveis', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao carregar auditorias: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna dados do dashboard de auditorias (Phase 3)
     */
    public function getDashboard()
    {
        try {
            $auditService = app(\App\Services\AuditReportService::class);
            $consolidatedReport = $auditService->generateConsolidatedReport();

            return response()->json([
                'success' => true,
                'health_score' => $consolidatedReport['health_score'],
                'audits' => $consolidatedReport['audits'],
                'generated_at' => $consolidatedReport['generated_at'],
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao gerar dashboard de auditorias', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao carregar dashboard: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa uma auditoria específica (Phase 3)
     */
    public function runSpecificAudit(Request $request)
    {
        $request->validate([
            'audit_type' => 'required|string',
            'scan_only' => 'required|in:true,false,1,0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $auditType = $request->input('audit_type');
            $scanOnly = $request->scan_only;
            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Mapeamento de tipos para comandos
            $commandMap = [
                'settlements' => 'gig:audit-settlements',
                'payments' => 'gig:audit-payments',
                'business-rules' => 'gig:audit-business-rules',
                'currency' => 'gig:audit-currency',
                'costs' => 'gig:audit-costs',
                'duplicates' => 'gig:audit-duplicates',
            ];

            if (! isset($commandMap[$auditType])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tipo de auditoria inválido',
                ], 400);
            }

            $command = $commandMap[$auditType];

            // Preparar parâmetros do comando
            $params = [];

            if ($dateFrom) {
                $params['--date-from'] = $dateFrom;
            }
            if ($dateTo) {
                $params['--date-to'] = $dateTo;
            }

            // Processar modo de correção
            if ($scanOnly === 'true' || $scanOnly === '1') {
                $params['--scan-only'] = true;
            } else {
                $params['--auto-fix'] = true;
            }

            // Executar comando
            $exitCode = Artisan::call($command, $params);
            $output = Artisan::output();

            // Buscar o arquivo de relatório mais recente
            $reportPath = $this->getLatestAuditReportByType($auditType);

            return response()->json([
                'success' => $exitCode === 0,
                'output' => $output,
                'report_path' => $reportPath,
                'audit_type' => $auditType,
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao executar auditoria específica', [
                'audit_type' => $request->input('audit_type'),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao executar auditoria: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Executa todas as auditorias (Phase 3)
     */
    public function runAllAudits(Request $request)
    {
        $request->validate([
            'scan_only' => 'required|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $scanOnly = $request->boolean('scan_only');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            $commands = [
                'gig:audit-settlements',
                'gig:audit-payments',
                'gig:audit-business-rules',
                'gig:audit-currency',
                'gig:audit-costs',
                'gig:audit-duplicates',
            ];

            $results = [];
            $allSuccessful = true;

            foreach ($commands as $command) {
                $params = [];

                if ($dateFrom) {
                    $params['--date-from'] = $dateFrom;
                }
                if ($dateTo) {
                    $params['--date-to'] = $dateTo;
                }

                if ($scanOnly) {
                    $params['--scan-only'] = true;
                } else {
                    $params['--auto-fix'] = true;
                }

                $exitCode = Artisan::call($command, $params);

                $results[$command] = [
                    'success' => $exitCode === 0,
                    'exit_code' => $exitCode,
                ];

                if ($exitCode !== 0) {
                    $allSuccessful = false;
                }
            }

            return response()->json([
                'success' => $allSuccessful,
                'message' => $allSuccessful
                    ? 'Todas as auditorias foram executadas com sucesso'
                    : 'Algumas auditorias falharam',
                'results' => $results,
            ]);

        } catch (Exception $e) {
            Log::error('Erro ao executar todas as auditorias', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'error' => 'Erro ao executar auditorias: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Busca o arquivo de relatório mais recente de um tipo específico
     */
    private function getLatestAuditReportByType(string $auditType): ?string
    {
        $logsPath = storage_path('logs');
        $files = glob($logsPath."/audit_{$auditType}_*.json");

        if (empty($files)) {
            return null;
        }

        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files[0];
    }
}
