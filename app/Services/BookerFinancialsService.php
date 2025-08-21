<?php

namespace App\Services;

use App\Models\Booker;
use App\Models\Settlement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookerFinancialsService
{
    protected GigFinancialCalculatorService $gigCalculator;

    public function __construct(GigFinancialCalculatorService $gigCalculator)
    {
        $this->gigCalculator = $gigCalculator;
    }

    public function getSalesKpis(Booker $booker, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $booker->gigs()->whereNull('deleted_at');

        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('COALESCE(contract_date, gig_date)'), [$startDate, $endDate]);
        }

        $gigsInPeriod = $query->get();

        return [
            'total_sold_value' => $gigsInPeriod->sum(fn ($gig) => $gig->cache_value_brl),
            'total_gigs_sold' => $gigsInPeriod->count(),
        ];
    }

    public function getCommissionKpis(Booker $booker): array
    {
        $commissionReceived = Settlement::whereHas('gig', fn ($q) => $q->where('booker_id', $booker->id))
            ->whereNotNull('booker_commission_paid_at')
            ->sum('booker_commission_value_paid');

        $commissionToReceive = $booker->gigs()->where('booker_payment_status', 'pendente')
            ->get()
            ->sum(fn ($gig) => $this->gigCalculator->calculateBookerCommissionBrl($gig));

        return [
            'commission_received' => $commissionReceived,
            'commission_to_receive' => $commissionToReceive,
        ];
    }

    public function getCommissionChartData(Booker $booker): array
    {
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now()->endOfMonth();

        $commissionsByMonthData = Settlement::query()
            ->select(
                DB::raw('YEAR(booker_commission_paid_at) as year, MONTH(booker_commission_paid_at) as month, SUM(booker_commission_value_paid) as total_commission')
            )
            ->whereHas('gig', fn ($q) => $q->where('booker_id', $booker->id))
            ->whereNotNull('booker_commission_paid_at')
            ->whereBetween('booker_commission_paid_at', [$startDate, $endDate])
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')->orderBy('month', 'asc')
            ->get()->keyBy(fn ($item) => $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $data = [];
        $currentMonth = $startDate->copy();
        while ($currentMonth <= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            $labels[] = $currentMonth->translatedFormat('M/y');
            $data[] = $commissionsByMonthData->get($monthKey)->total_commission ?? 0;
            $currentMonth->addMonth();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getTopArtists(Booker $booker, ?Carbon $startDate = null, ?Carbon $endDate = null, int $limit = 10): Collection
    {
        $query = $booker->gigs()->whereNull('deleted_at');
        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('COALESCE(contract_date, gig_date)'), [$startDate, $endDate]);
        }

        $gigsInPeriod = $query->with('artist:id,name')->get();

        return $gigsInPeriod
            ->groupBy('artist.name')
            ->map(function ($gigsForArtist, $artistName) {

                // ***** CORREÇÃO DA ORDENAÇÃO AQUI *****
                $sortedGigs = $gigsForArtist->sortByDesc(function ($gig) {
                    return $gig->contract_date ?? $gig->gig_date;
                });

                return (object) [
                    'artist_name' => $artistName,
                    'gigs_count' => $gigsForArtist->count(),
                    'total_value' => $gigsForArtist->sum(fn ($gig) => $gig->cache_value_brl),
                    'gigs' => $sortedGigs->map(function ($gig) { // Usa a coleção já ordenada
                        return (object) [
                            'sale_date' => Carbon::parse($gig->contract_date ?? $gig->gig_date)->format('d/m/Y'),
                            'location' => $gig->location_event_details,
                            'value' => $gig->cache_value_brl,
                        ];
                    }),
                ];
            })
            ->sortByDesc('total_value')
            ->take($limit)
            ->values();
    }

    public function getRecentGigs(Booker $booker, int $limit = 10): Collection
    {
        return $booker->gigs()->whereNull('deleted_at')
            // ***** ALTERAÇÃO AQUI: Eager load dos custos *****
            ->with(['artist:id,name', 'costs'])
            ->orderByDesc(DB::raw('COALESCE(contract_date, gig_date)'))
            ->limit($limit)
            ->get();
    }

    public function getGigsForPeriod(Booker $booker, Carbon $startDate, Carbon $endDate): Collection
    {
        return $booker->gigs()->whereNull('deleted_at')
            ->select(
                'gigs.*',
                DB::raw('COALESCE(gigs.contract_date, gigs.gig_date) as sale_date') // <-- CORREÇÃO AQUI
            )
            ->with(['artist'])
            ->whereBetween(DB::raw('COALESCE(contract_date, gig_date)'), [$startDate, $endDate])
            ->orderByDesc(DB::raw('COALESCE(contract_date, gig_date)'))
            ->get();
    }
}
