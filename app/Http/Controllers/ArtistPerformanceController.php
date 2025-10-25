<?php

namespace App\Http\Controllers;

use App\Exports\ArtistPerformanceReportExport;
use App\Models\Artist;
use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ArtistPerformanceController extends Controller
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
     * Exibe a página principal de desempenho de artistas.
     */
    public function index(Request $request)
    {
        // Pega os dados filtrados
        $performanceData = $this->getPerformanceData($request);

        // Pega todos os artistas para o filtro de dropdown
        $artists = Artist::orderBy('name')->get();

        return view('artist-performance.index', [
            'performanceData' => $performanceData,
            'artists' => $artists,
            'filters' => $request->only(['start_date', 'end_date', 'artist_id']),
        ]);
    }

    /**
     * Exporta o relatório de desempenho para PDF.
     */
    public function exportPdf(Request $request)
    {
        $performanceData = $this->getPerformanceData($request);
        $filters = $request->only(['start_date', 'end_date', 'artist_id']);

        $pdf = Pdf::loadView('artist-performance.export_pdf', [
            'performanceData' => $performanceData,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-desempenho-artistas-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Exporta o relatório de desempenho para Excel.
     */
    public function exportExcel(Request $request)
    {
        $performanceData = $this->getPerformanceData($request);
        $tableData = $performanceData['tableData'];

        return Excel::download(
            new ArtistPerformanceReportExport($tableData),
            'relatorio-desempenho-artistas-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * Lógica de negócio centralizada para buscar e processar os dados de desempenho.
     */
    private function getPerformanceData(Request $request): array
    {
        // 1. Validar e definir o período
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();
        $artistId = $request->input('artist_id');

        // 2. Query base para buscar Gigs
        $query = Gig::query()
            ->whereNull('deleted_at')
            ->select(
                'gigs.*',
                DB::raw('COALESCE(gigs.contract_date, gigs.gig_date) as sale_date')
            )
            ->whereBetween(DB::raw('COALESCE(gigs.contract_date, gigs.gig_date)'), [$startDate, $endDate])
            ->with(['artist', 'booker']);

        // 3. Aplicar filtro de Artista se houver
        if ($artistId) {
            $query->where('artist_id', $artistId);
        }

        // 4. Executar a query e ordenar
        $gigs = $query->orderBy('sale_date', 'desc')->get();

        // 5. Calcular totais gerais para os cards
        $totalGigsSold = $gigs->count();
        $totalContractValue = $gigs->sum('cache_value_brl');

        $grandTotalGrossCash = $gigs->sum(fn ($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));
        $grandTotalNetPayout = $gigs->sum(fn ($gig) => $this->gigCalculator->calculateArtistNetPayoutBrl($gig));

        // 6. Agrupar por Artista e preparar a estrutura de dados final
        $dataByArtist = $gigs->groupBy(fn ($gig) => $gig->artist->name ?? 'Artista Desconhecido')
            ->map(function ($gigsForArtist) {

                $totalGrossCash = $gigsForArtist->sum(fn ($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));
                $totalNetPayout = $gigsForArtist->sum(fn ($gig) => $this->gigCalculator->calculateArtistNetPayoutBrl($gig));

                // Agrupar gigs por mês para melhor visualização
                $gigsByMonth = $gigsForArtist->groupBy(function ($gig) {
                    return Carbon::parse($gig->contract_date ?? $gig->gig_date)->format('m/Y');
                })->map(function ($gigsInMonth) {
                    $monthName = Carbon::parse($gigsInMonth->first()->contract_date ?? $gigsInMonth->first()->gig_date)->format('M/Y');
                    $totalContractMonth = $gigsInMonth->sum('cache_value_brl');
                    $totalGrossCashMonth = $gigsInMonth->sum(fn ($gig) => $this->gigCalculator->calculateGrossCashBrl($gig));
                    $totalNetPayoutMonth = $gigsInMonth->sum(fn ($gig) => $this->gigCalculator->calculateArtistNetPayoutBrl($gig));

                    return [
                        'month_name' => $monthName,
                        'month_total_contract' => $totalContractMonth,
                        'month_total_gross_cash' => $totalGrossCashMonth,
                        'month_total_net_payout' => $totalNetPayoutMonth,
                        'month_gigs_count' => $gigsInMonth->count(),
                        'gigs' => $gigsInMonth->map(function ($gig) {
                            $gross_cash_brl = $this->gigCalculator->calculateGrossCashBrl($gig);
                            $net_payout_brl = $this->gigCalculator->calculateArtistNetPayoutBrl($gig);

                            return [
                                'gig_id' => $gig->id,
                                'sale_date' => Carbon::parse($gig->sale_date)->format('d/m/Y'),
                                'gig_date' => $gig->gig_date->format('d/m/Y'),
                                'booker_name' => $gig->booker->name ?? 'N/A',
                                'location_event_details' => $gig->location_event_details,
                                'contract_value' => $gig->cache_value_brl,
                                'gross_cash_brl' => $gross_cash_brl,
                                'net_payout_brl' => $net_payout_brl,
                            ];
                        }),
                    ];
                })->sortKeys();

                return [
                    'artist_name' => $gigsForArtist->first()->artist->name ?? 'Artista Desconhecido',
                    'total_contract' => $gigsForArtist->sum('cache_value_brl'),
                    'total_gross_cash' => $totalGrossCash,
                    'total_net_payout' => $totalNetPayout,
                    'gigs_count' => $gigsForArtist->count(),
                    'gigs_by_month' => $gigsByMonth,
                    'gigs' => $gigsForArtist->map(function ($gig) {

                        $gross_cash_brl = $this->gigCalculator->calculateGrossCashBrl($gig);
                        $net_payout_brl = $this->gigCalculator->calculateArtistNetPayoutBrl($gig);

                        return [
                            'gig_id' => $gig->id,
                            'sale_date' => Carbon::parse($gig->sale_date)->format('d/m/Y'),
                            'gig_date' => $gig->gig_date->format('d/m/Y'),
                            'booker_name' => $gig->booker->name ?? 'N/A',
                            'location_event_details' => $gig->location_event_details,
                            'contract_value' => $gig->cache_value_brl,
                            'gross_cash_brl' => $gross_cash_brl,
                            'net_payout_brl' => $net_payout_brl,
                        ];
                    }),
                ];
            })
            ->sortByDesc('total_contract');

        return [
            'summaryCards' => [
                'total_gigs' => $gigs->count(),
                'total_value' => $gigs->sum('cache_value_brl'),
                'total_gross_cash' => $grandTotalGrossCash,
                'total_net_payout' => $grandTotalNetPayout,
            ],
            'tableData' => $dataByArtist,
        ];
    }
}
