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
        // Query base com eager loading para otimização
        $query = Gig::with(['artist', 'booker', 'payments'])
            ->select('gigs.*')
            ->leftJoin('artists', 'gigs.artist_id', '=', 'artists.id')
            ->leftJoin('bookers', 'gigs.booker_id', '=', 'bookers.id');

        // Filtros
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

        // Filtro por status de pagamento será aplicado após o cálculo dos dados
        // pois precisa analisar os pagamentos relacionados

        // Filtro por divergência
        if ($request->filled('has_divergence')) {
            $hasDivergence = $request->boolean('has_divergence');
            if ($hasDivergence) {
                // Aplicar filtro para mostrar apenas gigs com divergência
                // Isso será feito após o cálculo, por enquanto mantemos todas
            }
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'gig_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $orderByColumn = match ($sortBy) {
            'artist_name' => 'artists.name',
            'booker_name' => 'bookers.name',
            'contract_number' => 'gigs.contract_number',
            'location' => 'gigs.location_event_details',
            'currency' => 'gigs.currency',
            default => 'gigs.' . $sortBy,
        };
        
        $query->orderBy($orderByColumn, $sortDirection);

        // Buscar todos os registros sem paginação
        $gigs = $query->get();

        // Calcular dados de auditoria para cada gig
        $auditData = [];
        foreach ($gigs as $gig) {
            $auditInfo = $this->calculateAuditData($gig);
            $auditData[$gig->id] = $auditInfo;
        }

        // Separar gigs totalmente pagos dos que possuem divergências
        $fullyPaidGigs = collect();
        $gigsWithIssues = collect();
        
        foreach ($gigs as $gig) {
            $data = $auditData[$gig->id] ?? null;
            if ($data) {
                // Verificar se está totalmente pago (sem divergência e sem pendências)
                $isFullyPaid = abs($data['divergencia']) <= 0.01 && $data['total_pendente'] <= 0.01;
                
                if ($isFullyPaid) {
                    $fullyPaidGigs->push($gig);
                } else {
                    $gigsWithIssues->push($gig);
                }
            }
        }

        // Filtrar por divergência se solicitado (apenas nos gigs com problemas)
        if ($request->filled('has_divergence') && $request->boolean('has_divergence')) {
            $gigsWithIssues = $gigsWithIssues->filter(function ($gig) use ($auditData) {
                return abs($auditData[$gig->id]['divergencia']) > 0.01;
            });
        }

        // Filtrar por status de pagamento baseado nos pagamentos relacionados
        if ($request->filled('payment_status')) {
            $paymentStatus = $request->input('payment_status');
            $gigsWithIssues = $gigsWithIssues->filter(function ($gig) use ($paymentStatus) {
                $totalPaid = $gig->payments->where('status', 'confirmed')->sum('amount_brl');
                $totalDue = $gig->payments->sum('amount_brl');
                
                if ($paymentStatus === 'paid') {
                    return $totalPaid >= $totalDue && $totalDue > 0;
                } elseif ($paymentStatus === 'partial') {
                    return $totalPaid > 0 && $totalPaid < $totalDue;
                } elseif ($paymentStatus === 'pending') {
                    return $totalPaid == 0 && $totalDue > 0;
                }
                
                return true;
            });
        }

        // Agrupar apenas os gigs com problemas por status de pagamento
        $groupedGigs = $this->groupGigsByPaymentStatus($gigsWithIssues, $auditData);

        // Dados para filtros
        $currencies = DB::table('gigs')->select('currency')->distinct()->orderBy('currency')->pluck('currency');

        return view('audit.index', [
            'gigs' => $gigs,
            'auditData' => $auditData,
            'groupedGigs' => $groupedGigs,
            'fullyPaidGigs' => $fullyPaidGigs,
            'gigsWithIssues' => $gigsWithIssues,
            'currencies' => $currencies,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'filters' => $request->only(['search', 'start_date', 'end_date', 'currency', 'payment_status', 'has_divergence'])
        ]);
    }

    /**
     * Calcula os dados de auditoria para uma gig específica.
     * Fórmula da Divergência: Valor do Contrato - (Total Pago + Total Pendente)
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
     * Agrupa as gigs por status de pagamento baseado nas parcelas vencidas.
     *
     * @param \Illuminate\Support\Collection $gigs
     * @param array $auditData
     * @return array
     */
    private function groupGigsByPaymentStatus($gigs, array $auditData): array
    {
        $groups = [
            'multiple_overdue' => ['title' => 'Pagamentos com duas ou mais parcelas vencidas', 'gigs' => collect()],
            'single_overdue' => ['title' => 'Pagamentos vencidos (uma parcela vencida)', 'gigs' => collect()],
            'future_payments' => ['title' => 'Pagamentos a vencer', 'gigs' => collect()]
        ];

        foreach ($gigs as $gig) {
            $overdueCount = $this->countOverduePayments($gig);
            
            if ($overdueCount >= 2) {
                $groups['multiple_overdue']['gigs']->push($gig);
            } elseif ($overdueCount == 1) {
                $groups['single_overdue']['gigs']->push($gig);
            } else {
                $groups['future_payments']['gigs']->push($gig);
            }
        }

        return $groups;
    }

    /**
     * Conta o número de parcelas vencidas para uma gig.
     *
     * @param Gig $gig
     * @return int
     */
    private function countOverduePayments(Gig $gig): int
    {
        $today = now()->startOfDay();
        
        return $gig->payments()
            ->whereNull('confirmed_at')
            ->where('due_date', '<', $today)
            ->count();
    }
}