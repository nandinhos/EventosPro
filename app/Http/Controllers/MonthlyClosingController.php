<?php

namespace App\Http\Controllers;

use App\Models\Booker;
use App\Models\Gig;
use App\Services\GigFinancialCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonthlyClosingController extends Controller
{
    protected $gigCalculator;

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }

    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $bookerId = $request->input('booker_id');

        // Definir período do mês
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Obter dados do relatório
        $reportData = $this->generateReportData($startDate, $endDate, $bookerId);

        // Obter lista de bookers para o filtro
        $bookers = Booker::orderBy('name')->get();

        return view('finance.monthly-closing.index', [
            'reportData' => $reportData,
            'bookers' => $bookers,
            'selectedBookerId' => $bookerId,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'months' => $this->getMonthsList(),
            'years' => $this->getYearsList(),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $bookerId = $request->input('booker_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $reportData = $this->generateReportData($startDate, $endDate, $bookerId);
        $booker = $bookerId ? Booker::find($bookerId) : null;

        $filename = 'fechamento-mensal-'.$startDate->format('m-Y');
        if ($booker) {
            $filename .= '-booker-'.Str::slug($booker->name);
        }
        $filename .= '.pdf';

        $pdf = PDF::loadView('finance.monthly-closing.pdf', [
            'reportData' => $reportData,
            'booker' => $booker,
            'period' => $startDate->format('m/Y'),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    protected function generateReportData($startDate, $endDate, $bookerId = null)
    {
        // Query base com eager loading
        $query = Gig::with(['artist', 'booker', 'payments', 'gigCosts.costCenter'])
            ->whereBetween('gig_date', [$startDate, $endDate])
            ->orderBy('gig_date');

        if ($bookerId) {
            $query->where('booker_id', $bookerId);
        }

        $gigs = $query->get();

        // ============================
        // CARDS PRINCIPAIS
        // ============================

        $totalFaturamento = 0;      // Soma de Cachê Líquido (Cachê Bruto - Despesas)
        $totalComissaoAgencia = 0;  // Comissão Líquida da Agência
        $totalComissaoBooker = 0;   // Comissão do Booker
        $totalGigs = $gigs->count();

        foreach ($gigs as $gig) {
            $totalFaturamento += $this->gigCalculator->calculateGrossCashBrl($gig);
            $totalComissaoAgencia += $this->gigCalculator->calculateAgencyNetCommissionBrl($gig);
            $totalComissaoBooker += $this->gigCalculator->calculateBookerCommissionBrl($gig);
        }

        // ============================
        // TABELA 1: ANALÍTICA POR ARTISTA
        // ============================

        $artistData = $gigs->groupBy('artist_id')->map(function ($artistGigs) {
            $artist = $artistGigs->first()->artist;

            if (! $artist) {
                return null;
            }

            // Processar cada gig com os cálculos do service
            $gigsDetailed = $artistGigs->sortBy('gig_date')->map(function ($gig) {
                return [
                    'gig' => $gig,
                    'date' => $gig->gig_date,
                    'location' => $gig->location_event_details,
                    'city_state' => $gig->location_city.'/'.$gig->location_state,
                    'cache_liquido' => $this->gigCalculator->calculateGrossCashBrl($gig),
                    'comissao_agencia' => $this->gigCalculator->calculateAgencyNetCommissionBrl($gig),
                    'comissao_booker' => $this->gigCalculator->calculateBookerCommissionBrl($gig),
                    'comissao_liquida' => $this->gigCalculator->calculateAgencyNetCommissionBrl($gig),
                ];
            });

            // Totais do artista
            $cacheLiquido = $gigsDetailed->sum('cache_liquido');
            $comissaoAgencia = $gigsDetailed->sum('comissao_agencia');
            $comissaoBooker = $gigsDetailed->sum('comissao_booker');
            $comissaoLiquida = $gigsDetailed->sum('comissao_liquida');
            $vendas = $gigsDetailed->count();

            return [
                'artist' => $artist,
                'cache_liquido' => $cacheLiquido,
                'comissao_agencia' => $comissaoAgencia,
                'comissao_booker' => $comissaoBooker,
                'comissao_liquida' => $comissaoLiquida,
                'vendas' => $vendas,
                'gigs_detailed' => $gigsDetailed,
            ];
        })->filter()->sortByDesc('cache_liquido');

        // ============================
        // TABELA 2: ANALÍTICA POR BOOKER
        // ============================

        $bookerData = $gigs->groupBy('booker_id')->map(function ($bookerGigs) {
            $booker = $bookerGigs->first()->booker;

            if (! $booker) {
                return null;
            }

            // Processar cada gig com os cálculos do service
            $gigsDetailed = $bookerGigs->sortBy('gig_date')->map(function ($gig) {
                return [
                    'gig' => $gig,
                    'date' => $gig->gig_date,
                    'artist_name' => $gig->artist->name ?? 'N/A',
                    'location' => $gig->location_event_details,
                    'city_state' => $gig->location_city.'/'.$gig->location_state,
                    'cache_liquido' => $this->gigCalculator->calculateGrossCashBrl($gig),
                    'comissao_booker' => $this->gigCalculator->calculateBookerCommissionBrl($gig),
                ];
            });

            // Totais do booker
            $cacheLiquido = $gigsDetailed->sum('cache_liquido');
            $comissaoBooker = $gigsDetailed->sum('comissao_booker');
            $vendas = $gigsDetailed->count();

            return [
                'booker' => $booker,
                'cache_liquido' => $cacheLiquido,
                'comissao_booker' => $comissaoBooker,
                'vendas' => $vendas,
                'gigs_detailed' => $gigsDetailed,
            ];
        })->filter()->sortByDesc('cache_liquido');

        // ============================
        // DADOS PARA GRÁFICOS
        // ============================

        // Gráfico 1: Comparativo de Comissões por Booker
        $chartBookerComparison = $bookerData->take(10)->values()->map(function ($data, $index) {
            $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'];

            return [
                'label' => $data['booker']->name,
                'cache_liquido' => $data['cache_liquido'],
                'comissao_booker' => $data['comissao_booker'],
                'vendas' => $data['vendas'],
                'color' => $colors[$index % count($colors)],
            ];
        })->toArray();

        // Gráfico 2: Top 10 Artistas por Faturamento
        $chartTopArtists = $artistData->take(10)->values()->map(function ($data, $index) {
            $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'];

            return [
                'label' => $data['artist']->name,
                'cache_liquido' => $data['cache_liquido'],
                'comissao_agencia' => $data['comissao_agencia'],
                'vendas' => $data['vendas'],
                'color' => $colors[$index % count($colors)],
            ];
        })->toArray();

        // Gráfico 3: Distribuição de Faturamento
        $chartDistribution = [
            ['label' => 'Comissão Agência', 'value' => $totalComissaoAgencia, 'color' => '#6366f1'],
            ['label' => 'Comissão Booker', 'value' => $totalComissaoBooker, 'color' => '#8b5cf6'],
            ['label' => 'Cachê Líquido Artista', 'value' => $totalFaturamento - $totalComissaoAgencia - $totalComissaoBooker, 'color' => '#10b981'],
        ];

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_gigs' => $totalGigs,

            // Cards principais
            'total_faturamento' => $totalFaturamento,
            'total_comissao_agencia' => $totalComissaoAgencia,
            'total_comissao_booker' => $totalComissaoBooker,

            // Tabelas
            'artist_data' => $artistData,
            'booker_data' => $bookerData,

            // Gráficos
            'chart_booker_comparison' => $chartBookerComparison,
            'chart_top_artists' => $chartTopArtists,
            'chart_distribution' => $chartDistribution,

            // Dados brutos
            'gigs' => $gigs,
        ];
    }

    protected function getMonthsList()
    {
        return [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro',
        ];
    }

    protected function getYearsList()
    {
        $startYear = 2023; // Ano de início dos registros
        $currentYear = (int) date('Y');
        $years = [];

        for ($year = $startYear; $year <= $currentYear; $year++) {
            $years[$year] = $year;
        }

        return array_reverse($years, true);
    }
}
