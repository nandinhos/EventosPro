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

        $pdf = PDF::loadView('reports.exports.monthly_closing_pdf', [
            'reportData' => $reportData,
            'booker' => $booker,
            'month' => $startDate->format('m/Y'),
        ]);

        return $pdf->download(sprintf('fechamento-mensal-%s%s.pdf',
            $startDate->format('m-Y'),
            $booker ? '-'.Str::slug($booker->name) : ''
        ));
    }

    protected function generateReportData($startDate, $endDate, $bookerId = null)
    {
        $query = Gig::with(['artist', 'booker', 'payments', 'costs'])
            ->whereBetween('gig_date', [$startDate, $endDate])
            ->orderBy('gig_date');

        if ($bookerId) {
            $query->where('booker_id', $bookerId);
        }

        $gigs = $query->get();

        // Calcular totais e agregações
        $totalGigs = $gigs->count();

        // Usar o serviço para calcular os valores corretos
        $totalCacheLiquidoBase = $gigs->sum(function ($gig) {
            return $this->gigCalculator->calculateGrossCashBrl($gig);
        });

        $totalBookerCommission = $gigs->sum(function ($gig) {
            return $this->gigCalculator->calculateBookerCommissionBrl($gig);
        });

        $totalAgencyCommission = $gigs->sum(function ($gig) {
            return $this->gigCalculator->calculateAgencyNetCommissionBrl($gig);
        });

        $totalCacheBrl = $gigs->sum('cache_value_brl');
        $totalDespesas = $gigs->sum(function ($gig) {
            return $gig->costs->where('is_confirmed', true)->sum('value');
        });

        // Agrupar por booker para o gráfico e tabela
        $bookerData = $gigs->groupBy('booker_id')->map(function ($bookerGigs) {
            $booker = $bookerGigs->first()->booker;

            // Calcular totais usando o serviço para cada gig
            $totalCacheLiquidoBase = 0;
            $totalBookerCommission = 0;
            $totalAgencyCommission = 0;
            $totalCacheBrl = 0;
            $totalDespesas = 0;
            $totalPago = 0;
            $totalPendente = 0;

            foreach ($bookerGigs as $gig) {
                $cacheLiquido = $this->gigCalculator->calculateGrossCashBrl($gig);
                $bookerCommission = $this->gigCalculator->calculateBookerCommissionBrl($gig);
                $agencyCommission = $this->gigCalculator->calculateAgencyNetCommissionBrl($gig);
                $pago = $gig->payments->sum('amount_paid_brl');
                $despesas = $gig->costs->where('is_confirmed', true)->sum('value');

                $totalCacheLiquidoBase += $cacheLiquido;
                $totalBookerCommission += $bookerCommission;
                $totalAgencyCommission += $agencyCommission;
                $totalCacheBrl += $gig->cache_value_brl;
                $totalDespesas += $despesas;
                $totalPago += $pago;
                $totalPendente += ($gig->cache_value_brl - $pago) > 0 ? ($gig->cache_value_brl - $pago) : 0;
            }

            $lucroLiquido = $totalCacheLiquidoBase - $totalBookerCommission - $totalAgencyCommission;

            return [
                'booker' => $booker,
                'total_gigs' => $bookerGigs->count(),
                'total_cache_brl' => $totalCacheBrl,
                'total_despesas' => $totalDespesas,
                'cache_liquido_base' => $totalCacheLiquidoBase,
                'total_booker_commission' => $totalBookerCommission,
                'total_agency_commission' => $totalAgencyCommission,
                'total_paid' => $totalPago,
                'pending_payments' => $totalPendente,
                'net_value' => $lucroLiquido,
                'lucro_percentual' => $totalCacheLiquidoBase > 0 ? ($lucroLiquido / $totalCacheLiquidoBase) * 100 : 0,
            ];
        })->filter()->sortByDesc('cache_liquido_base'); // Remove entradas nulas e ordena por faturamento

        // Agrupar por artista e ordenar por data
        $artistGigs = $gigs->groupBy('artist_id')
            ->map(function ($gigs) {
                $artist = $gigs->first()->artist;

                // Ordenar as gigs por data
                $sortedGigs = $gigs->sortBy('gig_date');

                return [
                    'artist' => $artist,
                    'gigs' => $sortedGigs,
                    'total_gigs' => $gigs->count(),
                    'total_cache_brl' => $gigs->sum('cache_value_brl'),
                    'total_booker_commission' => $gigs->sum('booker_commission_value'),
                    'total_agency_commission' => $gigs->sum('agency_commission_value'),
                ];
            })
            ->sortBy('artist.name'); // Ordenar artistas por nome

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_gigs' => $totalGigs,
            'total_cache_brl' => $totalCacheBrl,
            'total_booker_commission' => $totalBookerCommission,
            'total_agency_commission' => $totalAgencyCommission,
            'booker_data' => $bookerData,
            'artist_gigs' => $artistGigs,
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
