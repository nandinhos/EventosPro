<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Log;

class ReportController extends Controller
{
    public function financial(Request $request)
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfMonth();

        // Busca os eventos do período agrupados por artista
        $events = Gig::with(['artist', 'booker'])
            ->whereBetween('gig_date', [$startDate, $endDate])
            ->join('artists', 'gigs.artist_id', '=', 'artists.id')
            ->orderBy('artists.name')
            ->orderBy('gig_date', 'desc')
            ->select(
                'gigs.*',
                'artists.name as artist_name',
                DB::raw('gigs.cache_value * gigs.booker_commission_rate / 100 as booker_commission'),
                DB::raw('gigs.cache_value * gigs.agency_commission_rate / 100 as agency_commission')
            )
            ->get()
            ->groupBy('artist_name')
            ->map(function ($artistGigs) {
                return $artistGigs->map(function ($gig) {
                    return (object) [
                        'date' => Carbon::parse($gig->gig_date)->format('d/m/Y'),
                        'artist' => $gig->artist_name,
                        'location' => $gig->location_event_details,
                        'booker' => $gig->booker->name,
                        'cache' => $gig->cache_value,
                        'booker_commission' => $gig->booker_commission,
                        'agency_commission' => $gig->agency_commission,
                    ];
                });
            });

        // Dados para o gráfico de faturamento por booker
        $bookerRevenue = Gig::whereBetween('gig_date', [$startDate, $endDate])
            ->join('bookers', 'gigs.booker_id', '=', 'bookers.id')
            ->select('bookers.name', DB::raw('SUM(cache_value) as total'))
            ->groupBy('bookers.id', 'bookers.name')
            ->get();

        $bookerRevenueData = [
            'labels' => $bookerRevenue->pluck('name')->toArray(),
            'values' => $bookerRevenue->pluck('total')->toArray(),
        ];

        Log::info('Dados do gráfico de faturamento por booker:', $bookerRevenueData);

        // Dados para o gráfico de comissões
        $commissions = Gig::whereBetween('gig_date', [$startDate, $endDate])
            ->join('bookers', 'gigs.booker_id', '=', 'bookers.id')
            ->select(
                'bookers.name',
                DB::raw('SUM(cache_value * booker_commission_rate / 100) as booker_commission'),
                DB::raw('SUM(cache_value * agency_commission_rate / 100) as agency_commission')
            )
            ->groupBy('bookers.id', 'bookers.name')
            ->get();

        $commissionsData = [
            'labels' => $commissions->pluck('name')->toArray(),
            'booker' => $commissions->pluck('booker_commission')->toArray(),
            'agency' => $commissions->pluck('agency_commission')->toArray(),
        ];

        Log::info('Dados do gráfico de comissões:', $commissionsData);

        return view('reports.financial', compact('events', 'bookerRevenueData', 'commissionsData'));
    }
}
