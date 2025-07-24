<?php

namespace App\Http\Controllers;

use App\Models\Booker;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\GigFinancialCalculatorService;

class PerformanceReportController extends Controller
{
    // ***** PASSO 2: Injetar o Service no construtor *****
    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }
    
    /**
     * Exibe a página principal de desempenho.
     */
    public function index(Request $request)
    {
        // Pega os dados filtrados
        $performanceData = $this->getPerformanceData($request);
        
        // Pega todos os bookers para o filtro de dropdown
        $bookers = Booker::orderBy('name')->get();

        return view('performance.index', [
            'performanceData' => $performanceData,
            'bookers' => $bookers,
            'filters' => $request->only(['start_date', 'end_date', 'booker_id']) // Passa os filtros atuais para a view
        ]);
    }

    /**
     * Exporta o relatório de desempenho para PDF.
     */
    public function exportPdf(Request $request)
    {
        $performanceData = $this->getPerformanceData($request);
        $filters = $request->only(['start_date', 'end_date', 'booker_id']);

        $pdf = Pdf::loadView('performance.export_pdf', [
            'performanceData' => $performanceData,
            'filters' => $filters
        ]);
        
        return $pdf->download('relatorio-desempenho-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Lógica de negócio centralizada para buscar e processar os dados de desempenho.
     */
    private function getPerformanceData(Request $request): array
    {
        // 1. Validar e definir o período
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();
        $bookerId = $request->input('booker_id');

        // 2. Query base para buscar Gigs
        $query = Gig::query()
            ->whereNull('deleted_at')
            ->select(
                'gigs.*',
                DB::raw('COALESCE(gigs.contract_date, gigs.gig_date) as sale_date')
            )
            ->whereBetween(DB::raw('COALESCE(gigs.contract_date, gigs.gig_date)'), [$startDate, $endDate])
            ->with(['artist', 'booker']);

        // 3. Aplicar filtro de Booker se houver
        if ($bookerId) {
            $query->where('booker_id', $bookerId);
        }

        // 4. Executar a query e ordenar
        $gigs = $query->orderBy('sale_date', 'desc')->get();

        // 5. Calcular totais gerais para os cards
        $totalGigsSold = $gigs->count();
        $totalContractValue = $gigs->sum('cache_value_brl'); // Usando o acessor BRL

        $grandTotalGrossCash = $gigs->sum(fn($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));

        // 6. Agrupar por Booker e preparar a estrutura de dados final
        $dataByBooker = $gigs->groupBy(fn($gig) => $gig->booker->name ?? 'Agência Direta')
        ->map(function($gigsForBooker) {
            
            $totalGrossCash = $gigsForBooker->sum(fn($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));

            return [
                'booker_name' => $gigsForBooker->first()->booker->name ?? 'Agência Direta',
                'total_contract' => $gigsForBooker->sum('cache_value_brl'),
                'total_gross_cash' => $totalGrossCash, // Nova variável para o subtotal
                'gigs_count' => $gigsForBooker->count(),
                'gigs' => $gigsForBooker->map(function($gig) {
                    
                    $gross_cash_brl = $this->gigCalculator->calculateGrossCashBrl($gig);

                    return [
                        'sale_date' => Carbon::parse($gig->sale_date)->format('d/m/Y'),
                        'gig_date' => $gig->gig_date->format('d/m/Y'),
                        'artist_local' => $gig->artist->name . ' @ ' . Str::limit($gig->location_event_details, 90),
                        'contract_value' => $gig->cache_value_brl,
                        'gross_cash_brl' => $gross_cash_brl, // Nova variável para a linha
                    ];
                })
            ];
        })
        ->sortByDesc('total_contract');

        return [
            'summaryCards' => [
                'total_gigs' => $gigs->count(),
                'total_value' => $gigs->sum('cache_value_brl'),
                'total_gross_cash' => $grandTotalGrossCash, // Nova variável para o card de resumo
            ],
            'tableData' => $dataByBooker
        ];
    }
}