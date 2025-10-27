<?php

namespace App\Http\Controllers;

use App\Models\Booker;
use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceReportController extends Controller
{
    /**
     * O serviço de cálculo financeiro
     *
     * @var GigFinancialCalculatorService
     */
    protected $gigCalculator;

    /**
     * Cria uma nova instância do controlador
     *
     * @return void
     */
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
            'filters' => $request->only(['start_date', 'end_date', 'booker_id']), // Passa os filtros atuais para a view
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
            'filters' => $filters,
        ]);

        return $pdf->download('relatorio-desempenho-'.now()->format('Y-m-d').'.pdf');
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

        $grandTotalGrossCash = $gigs->sum(fn ($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));
        $grandTotalBookerCommission = $gigs->sum(fn ($gig) => $this->gigCalculator->calculateBookerCommissionBrl($gig));

        // 6. Agrupar por Booker e preparar a estrutura de dados final
        $dataByBooker = $gigs->groupBy(fn ($gig) => $gig->booker->name ?? 'Agência Direta')
            ->map(function ($gigsForBooker) {

                $totalGrossCash = $gigsForBooker->sum(fn ($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));
                $totalBookerCommission = $gigsForBooker->sum(fn ($gig) => $this->gigCalculator->calculateBookerCommissionBrl($gig));

                // Agrupar gigs por mês para melhor visualização
                $gigsByMonth = $gigsForBooker->groupBy(function ($gig) {
                    return Carbon::parse($gig->contract_date ?? $gig->gig_date)->isoFormat('MM/YYYY');
                })->map(function ($gigsInMonth) {
                    $monthName = Carbon::parse($gigsInMonth->first()->contract_date ?? $gigsInMonth->first()->gig_date)->isoFormat('MMM/YYYY');
                    $totalContractMonth = $gigsInMonth->sum('cache_value_brl');
                    $totalGrossCashMonth = $gigsInMonth->sum(fn ($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));
                    $totalBookerCommissionMonth = $gigsInMonth->sum(fn ($gig) => $this->gigCalculator->calculateBookerCommissionBrl($gig));

                    return [
                        'month_name' => $monthName,
                        'month_total_contract' => $totalContractMonth,
                        'month_total_gross_cash' => $totalGrossCashMonth,
                        'month_total_booker_commission' => $totalBookerCommissionMonth,
                        'month_gigs_count' => $gigsInMonth->count(),
                        'gigs' => $gigsInMonth->map(function ($gig) {
                            $gross_cash_brl = $this->gigCalculator->calculateGrossCashBrl($gig);
                            $booker_commission_brl = $this->gigCalculator->calculateBookerCommissionBrl($gig);

                            return [
                                'gig_id' => $gig->id,
                                'sale_date' => Carbon::parse($gig->sale_date)->isoFormat('L'),
                                'gig_date' => $gig->gig_date->isoFormat('L'),
                                'artist_name' => $gig->artist->name,
                                'artist_local' => $gig->artist->name.' @ '.Str::limit($gig->location_event_details, 90),
                                'location_event_details' => $gig->location_event_details,
                                'contract_value' => $gig->cache_value_brl,
                                'gross_cash_brl' => $gross_cash_brl,
                                'booker_commission_brl' => $booker_commission_brl,
                            ];
                        }),
                    ];
                })->sortKeys();

                return [
                    'booker_name' => $gigsForBooker->first()->booker->name ?? 'Agência Direta',
                    'total_contract' => $gigsForBooker->sum('cache_value_brl'),
                    'total_gross_cash' => $totalGrossCash, // Nova variável para o subtotal
                    'total_booker_commission' => $totalBookerCommission,
                    'gigs_count' => $gigsForBooker->count(),
                    'gigs_by_month' => $gigsByMonth,
                    'gigs' => $gigsForBooker->map(function ($gig) {

                        $gross_cash_brl = $this->gigCalculator->calculateGrossCashBrl($gig);

                        return [
                            'gig_id' => $gig->id,
                            'sale_date' => Carbon::parse($gig->sale_date)->isoFormat('L'),
                            'gig_date' => $gig->gig_date->isoFormat('L'),
                            'artist_name' => $gig->artist->name,
                            'artist_local' => $gig->artist->name.' @ '.Str::limit($gig->location_event_details, 90),
                            'location_event_details' => $gig->location_event_details,
                            'contract_value' => $gig->cache_value_brl,
                            'gross_cash_brl' => $gross_cash_brl,
                        ];
                    }),
                ];
            })
            ->sortByDesc('total_contract');

        return [
            'summaryCards' => [
                'total_gigs' => $gigs->count(),
                'total_value' => $gigs->sum('cache_value_brl'),
                'total_gross_cash' => $grandTotalGrossCash, // Nova variável para o card de resumo
                'total_commission' => $grandTotalBookerCommission,
            ],
            'tableData' => $dataByBooker,
        ];
    }
}
