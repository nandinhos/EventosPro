<?php

namespace App\Services;

use App\Models\Gig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected $gigCalculator;

    protected $startDate;

    protected $endDate;

    protected $filters = [];

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
        $this->setDefaultPeriod();
    }

    public function setFilters(array $filters = [])
    {
        $this->filters = $filters;
        $this->startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])
            : Carbon::now()->startOfMonth();

        $this->endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])
            : Carbon::now()->endOfMonth();

        return $this;
    }

    protected function setDefaultPeriod()
    {
        $this->startDate = Carbon::now()->startOfMonth();
        $this->endDate = Carbon::now()->endOfMonth();
    }

    /**
     * Obtém o primeiro e o último mês com dados de gigs
     *
     * @return array
     */
    public function getFirstAndLastMonth()
    {
        // Obtém a data da primeira e última gig cadastrada
        $firstGig = Gig::orderBy('gig_date', 'asc')->first();
        $lastGig = Gig::orderBy('gig_date', 'desc')->first();

        // Se não houver gigs, retorna o mês atual
        if (! $firstGig || ! $lastGig) {
            $now = Carbon::now();

            return [
                'first_month' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'last_month' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ];
        }

        return [
            'first_month' => $firstGig->gig_date->copy()->startOfMonth()->format('Y-m-d'),
            'last_month' => $lastGig->gig_date->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }

    public function getDashboardData()
    {
        $today = Carbon::today();
        $startOfMonth = $this->startDate->copy()->startOfMonth();
        $endOfMonth = $this->endDate->copy()->endOfMonth();

        // Obtém o primeiro e último mês disponível para o link do relatório completo
        $dateRange = $this->getFirstAndLastMonth();

        // Dados para cards superiores
        $data = [
            'today' => $today,
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
            'totalGigsCount' => Gig::count(),
            'activeFutureGigsCount' => Gig::where('gig_date', '>=', $today)->count(),
            'overdueClientPaymentsCount' => Gig::where('payment_status', 'vencido')->count(),
            'pendingArtistPaymentsCount' => Gig::where('artist_payment_status', 'pendente')->count(),
            'pendingBookerPaymentsCount' => Gig::where('booker_payment_status', 'pendente')->count(),
        ];

        // Gigs do mês (data do evento)
        $gigsThisMonth = Gig::with(['payments', 'costs', 'artist', 'booker'])
            ->whereBetween('gig_date', [$startOfMonth, $endOfMonth])
            ->get();

        // Calcula valores usando o GigFinancialCalculatorService para consistência
        $data['gigsThisMonthCount'] = $gigsThisMonth->count();

        // Calcula o cache bruto usando o serviço para consistência
        $data['totalCacheThisMonth'] = $gigsThisMonth->sum(function ($gig) {
            return $this->gigCalculator->calculateGrossCashBrl($gig);
        });

        // Calcula comissões usando o serviço para consistência
        $data['totalAgencyCommissionThisMonth'] = $gigsThisMonth->sum(function ($gig) {
            return $this->gigCalculator->calculateAgencyGrossCommissionBrl($gig);
        });

        $data['totalBookerCommissionThisMonth'] = $gigsThisMonth->sum(function ($gig) {
            return $this->gigCalculator->calculateBookerCommissionBrl($gig);
        });

        // Vendas do mês (data de contrato) - Usando a mesma lógica do relatório de desempenho
        $salesThisMonth = Gig::with(['payments', 'costs', 'artist', 'booker'])
            ->whereNull('deleted_at')
            ->whereBetween(DB::raw('COALESCE(contract_date, gig_date)'), [$startOfMonth, $endOfMonth])
            ->whereIn('contract_status', ['assinado', 'concluido', 'para_assinatura', 'n/a'])
            ->get();

        $data['salesThisMonthCount'] = $salesThisMonth->count();
        $data['totalSalesThisMonth'] = $salesThisMonth->sum(function ($gig) {
            return $this->gigCalculator->calculateGrossCashBrl($gig);
        });

        // Adiciona URLs para os relatórios
        $data['performanceReportUrl'] = route('reports.performance.index', [
            'start_date' => $startOfMonth->format('Y-m-d'),
            'end_date' => $endOfMonth->format('Y-m-d'),
        ]);

        // URL para o relatório completo (todo o período disponível)
        $data['fullPerformanceReportUrl'] = route('reports.performance.index', [
            'start_date' => $dateRange['first_month'],
            'end_date' => $dateRange['last_month'],
        ]);

        // Próximas gigs
        $data['nextGigs'] = Gig::with('artist')
            ->where('gig_date', '>=', $today)
            ->orderBy('gig_date', 'asc')
            ->limit(5)
            ->get();

        // Dados para o gráfico de faturamento mensal
        $this->prepareMonthlyRevenueChartData($data);

        return $data;
    }

    protected function prepareMonthlyRevenueChartData(&$data)
    {
        $endDateForChart = Carbon::now()->endOfYear();
        $startDateForChart = Carbon::now()->subMonths(11)->startOfMonth();

        // Primeiro, obtemos os IDs das gigs no período desejado
        $gigs = Gig::where(function ($query) use ($startDateForChart, $endDateForChart) {
            $query->whereBetween('contract_date', [$startDateForChart, $endDateForChart])
                ->orWhere(function ($q) use ($startDateForChart, $endDateForChart) {
                    $q->whereNull('contract_date')
                        ->whereBetween('gig_date', [$startDateForChart, $endDateForChart]);
                });
        })
            ->whereIn('contract_status', ['assinado', 'concluido', 'para_assinatura', 'n/a'])
            ->with(['payments', 'gigCosts', 'artist', 'booker'])
            ->get();

        // Agrupa os dados por mês/ano
        $monthlyData = [];

        foreach ($gigs as $gig) {
            $date = $gig->contract_date ?? $gig->gig_date;
            $year = $date->format('Y');
            $month = $date->format('m');
            $key = $year.'-'.$month;

            if (! isset($monthlyData[$key])) {
                $monthlyData[$key] = [
                    'year' => $year,
                    'month' => $month,
                    'total_revenue_brl' => 0,
                    'gigs_count' => 0,
                ];
            }

            // Usa o serviço para calcular o valor em BRL
            $monthlyData[$key]['total_revenue_brl'] += $this->gigCalculator->calculateGrossCashBrl($gig);
            $monthlyData[$key]['gigs_count']++;
        }

        // Converte para coleção e ordena
        $monthlyRevenueData = collect($monthlyData)->values()->sortBy(['year', 'month']);

        $chartLabels = [];
        $chartData = [];
        $chartGigsCount = [];

        $currentMonthIterator = $startDateForChart->copy();
        while ($currentMonthIterator->lessThanOrEqualTo($endDateForChart)) {
            $year = $currentMonthIterator->year;
            $month = $currentMonthIterator->month;
            $monthKey = $currentMonthIterator->format('Y-m');

            $chartLabels[] = $currentMonthIterator->translatedFormat('M/y');

            $revenueForMonth = $monthlyRevenueData->first(function ($item) use ($year, $month) {
                return $item['year'] == $year && $item['month'] == $month;
            });

            $chartData[] = $revenueForMonth ? (float) $revenueForMonth['total_revenue_brl'] : 0;
            $chartGigsCount[$monthKey] = $revenueForMonth ? (int) $revenueForMonth['gigs_count'] : 0;

            $currentMonthIterator->addMonth();
        }

        $data['chartLabels'] = $chartLabels;
        $data['chartData'] = $chartData;
        $data['chartGigsCount'] = $chartGigsCount;
    }
}
