<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditController extends Controller
{
    protected GigFinancialCalculatorService $financialCalculator;

    public function __construct(GigFinancialCalculatorService $financialCalculator)
    {
        $this->financialCalculator = $financialCalculator;
    }

    /**
     * Exibe a página principal de auditoria com lista de gigs e divergências financeiras.
     *
     * @param Request $request
     * @return View
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
                $q->where('contract_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('location_event_details', 'like', '%' . $filters['search'] . '%')
                  ->orWhereHas('artist', function ($artistQuery) use ($filters) {
                      $artistQuery->where('name', 'like', '%' . $filters['search'] . '%');
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
     *
     * @param Gig $gig
     * @return array
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
        
        if (!$confrontoOk) {
            // 1) Discrepância de valores onde a conta não bate
            if ($somaTotal == 0) {
                $categoria = 'falta_lancamento';
                $observacao = 'Não há lançamentos de pagamento';
            } else {
                $categoria = 'discrepancia_valores';
                $observacao = $diferenca > 0 ? 
                    'Falta R$ ' . number_format($diferenca, 2, ',', '.') : 
                    'Excesso de R$ ' . number_format(abs($diferenca), 2, ',', '.');
            }
        } else {
            // Confronto OK - verificar payment_status
            if ($gig->payment_status === 'pago') {
                // Totalmente OK - não precisa ser listado
                $needsAudit = false;
            } else {
                // 3) ou 4) Verificar se evento já aconteceu
                $hoje = now()->startOfDay();
                $gigDate = $gig->gig_date ? \Carbon\Carbon::parse($gig->gig_date)->startOfDay() : null;
                
                if ($gigDate && $gigDate->lt($hoje)) {
                    $categoria = 'gigs_vencidas';
                    // Refinar observação baseada no tipo de pagamento
                    if (abs($valorContrato - $totalPago) <= 0.01) {
                        // Caso 1: Contrato = Total Pago
                        $observacao = 'Evento já aconteceu - Alterar status para "pago"';
                    } else if (abs($valorContrato - $totalNaoPago) <= 0.01) {
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
            'payment_status' => $gig->payment_status
        ];
    }

    /**
     * Gera observações baseadas na análise dos dados financeiros.
     *
     * @param Gig $gig
     * @param float $divergencia
     * @param float $totalPago
     * @param float $totalPendente
     * @param float $valorContrato
     * @return string
     */
    private function generateObservation(Gig $gig, float $divergencia, float $totalPago, float $totalPendente, float $valorContrato): string
    {
        $observacoes = [];
        
        // Análise da divergência
        if (abs($divergencia) <= 0.01) {
            $observacoes[] = "Valores conferem";
        } elseif ($divergencia > 0) {
            $observacoes[] = "Falta receber R$ " . number_format($divergencia, 2, ',', '.');
        } else {
            $observacoes[] = "Excesso de R$ " . number_format(abs($divergencia), 2, ',', '.');
        }
        
        // Análise do status de pagamento
        if ($totalPago == 0 && $totalPendente == 0) {
            $observacoes[] = "Nenhum pagamento registrado";
        } elseif ($totalPago == $valorContrato) {
            $observacoes[] = "Totalmente pago";
        } elseif ($totalPago > 0 && $totalPendente > 0) {
            $observacoes[] = "Pagamento parcial";
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
     *
     * @param float $divergencia
     * @return string
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
     *
     * @param Gig $gig
     * @return View
     */
    public function show(Gig $gig): View
    {
        $gig->loadMissing(['artist', 'booker', 'payments' => function($query) {
            $query->orderBy('due_date', 'asc');
        }, 'costs.costCenter']);
        
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
            'financialData' => $financialData
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
            'Observação'
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
                    $audit['observacao']
                ];
            }
        }
        
        // Generate CSV file
        $filename = 'auditoria_gigs_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($csvData) {
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
     *
     * @param array $auditResults
     * @return array
     */
    private function groupAuditResults(array $auditResults): array
    {
        $groups = [
            'discrepancia_valores' => [
                'title' => 'Gigs com Discrepância de Valores',
                'description' => 'Confronto entre payments e contrato não confere',
                'items' => [],
                'color' => 'red'
            ],
            'falta_lancamento' => [
                'title' => 'Falta Lançamento de Pagamento',
                'description' => 'Não há lançamentos de pagamento para o gig',
                'items' => [],
                'color' => 'orange'
            ],
            'gigs_vencidas' => [
                'title' => 'Gigs Vencidas',
                'description' => 'Evento já aconteceu mas status não é "pago"',
                'items' => [],
                'color' => 'yellow'
            ],
            'gigs_a_vencer' => [
                'title' => 'Gigs a Vencer',
                'description' => 'Evento ainda não aconteceu',
                'items' => [],
                'color' => 'blue'
            ]
        ];

        foreach ($auditResults as $result) {
            if ($result['categoria'] && isset($groups[$result['categoria']])) {
                $groups[$result['categoria']]['items'][] = $result;
            }
        }

        // Remover grupos vazios
        return array_filter($groups, function($group) {
            return count($group['items']) > 0;
        });
    }

    /**
     * Calcula os dados de auditoria para uma gig específica (método mantido para compatibilidade).
     *
     * @param Gig $gig
     * @return array
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
                'status_divergencia' => $this->getStatusDivergencia($divergencia)
            ];
            
        } catch (\Exception $e) {
            Log::error("[AuditController] Erro ao calcular auditoria para Gig ID {$gig->id}: " . $e->getMessage());
            
            return [
                'valor_contrato' => 0,
                'total_pago' => 0,
                'total_pendente' => 0,
                'divergencia' => 0,
                'observacao' => 'Erro no cálculo: ' . $e->getMessage(),
                'tem_divergencia' => false,
                'status_divergencia' => 'erro'
            ];
        }
    }
}