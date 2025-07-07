<?php

namespace App\Services;

use App\Models\Booker;
use App\Models\Gig;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookerFinancialsService
{
    protected GigFinancialCalculatorService $gigCalculator;

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }

    // --- MÉTODOS EXISTENTES (REVISADOS) ---

    public function getCommissionMetrics(Booker $booker): array
    {
        $commissionReceived = Settlement::whereHas('gig', fn($q) => $q->where('booker_id', $booker->id))
                                       ->whereNotNull('booker_commission_paid_at')
                                       ->sum('booker_commission_value_paid');
        
        $commissionToReceive = $booker->gigs()->where('booker_payment_status', 'pendente')
                                         ->get()
                                         ->sum(fn($gig) => $this->gigCalculator->calculateBookerCommissionBrl($gig));

        return [
            'commission_received' => $commissionReceived,
            'commission_to_receive' => $commissionToReceive,
        ];
    }
    
    // --- NOVOS MÉTODOS PARA O DASHBOARD DO BOOKER ---

    public function getSalesKpis(Booker $booker, Carbon $startDate, Carbon $endDate): array
    {
        $gigsInPeriod = $booker->gigs()->whereNull('deleted_at')
            ->whereBetween(DB::raw('COALESCE(contract_date, gig_date)'), [$startDate, $endDate])
            ->get();

        return [
            'total_sold_value' => $gigsInPeriod->sum(fn($gig) => $gig->cache_value_brl),
            'total_gigs_sold' => $gigsInPeriod->count(),
        ];
    }

    public function getCommissionChartData(Booker $booker): array
    {
        $commissionsByMonthData = Settlement::query()
            ->select(
                DB::raw("YEAR(booker_commission_paid_at) as year, MONTH(booker_commission_paid_at) as month, SUM(booker_commission_value_paid) as total_commission")
            )
            ->whereHas('gig', fn($q) => $q->where('booker_id', $booker->id))
            ->whereNotNull('booker_commission_paid_at')
            ->where('booker_commission_paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')->orderBy('month', 'asc')
            ->get()->keyBy(fn($item) => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $data = [];
        $currentMonth = now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $monthKey = $currentMonth->format('Y-m');
            $labels[] = $currentMonth->translatedFormat('M/y');
            $data[] = $commissionsByMonthData->get($monthKey)->total_commission ?? 0;
            $currentMonth->addMonth();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getTopArtists(Booker $booker, int $limit = 5): Collection
    {
        return $booker->gigs()->whereNull('deleted_at')
            ->selectRaw('artist_id, COUNT(*) as gigs_count')
            ->groupBy('artist_id')
            ->with('artist:id,name')
            ->orderByDesc('gigs_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) use ($booker) { // ***** CORREÇÃO: Passa $booker para o closure *****
                $gigsForThisArtist = Gig::where('booker_id', $booker->id) // Usa o $booker passado
                                        ->where('artist_id', $item->artist_id)
                                        ->get();
                $item->total_value = $gigsForThisArtist->sum(fn($gig) => $gig->cache_value_brl);
                return $item;
            });
    }

    public function getRecentGigs(Booker $booker, int $limit = 10): Collection
    {
        return $booker->gigs()->whereNull('deleted_at')
            ->with('artist:id,name')
            ->orderByDesc(DB::raw('COALESCE(contract_date, gig_date)'))
            ->limit($limit)
            ->get();
    }
}