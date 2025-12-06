<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Gig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArtistSettlementsController extends Controller
{
    /**
     * Exibe a página de fechamentos de artistas.
     */
    public function index(Request $request)
    {
        // Carregar lista de artistas para o filtro
        $artists = Artist::orderBy('name')->get();

        // Query base: gigs realizadas com artista
        $query = Gig::query()
            ->with(['artist', 'booker'])
            ->whereNotNull('artist_id')
            ->where('gig_date', '<=', now());

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('artist', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('booker', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhere('location_event_details', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('artist_id')) {
            $query->where('artist_id', $request->input('artist_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('gig_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_until')) {
            $query->whereDate('gig_date', '<=', $request->input('date_until'));
        }

        if ($request->filled('status')) {
            $query->where('artist_payment_status', $request->input('status'));
        }

        // Métricas para os cards
        $baseQuery = Gig::query()
            ->whereNotNull('artist_id')
            ->where('gig_date', '<=', now());

        $pendingCount = (clone $baseQuery)->where('artist_payment_status', 'pendente')->count();
        $paidCount = (clone $baseQuery)->where('artist_payment_status', 'pago')->count();
        $pendingTotal = (clone $baseQuery)->where('artist_payment_status', 'pendente')
            ->get()
            ->sum('calculated_artist_net_payout_brl');

        // Ordenação e paginação
        $gigs = $query->orderBy('gig_date', 'desc')->paginate(25)->withQueryString();

        return view('artists.settlements.index', compact(
            'gigs',
            'artists',
            'pendingCount',
            'paidCount',
            'pendingTotal'
        ));
    }

    /**
     * Processa o pagamento em massa de cachês de artistas.
     */
    public function settleBatch(Request $request)
    {
        $request->validate([
            'gig_ids' => 'required|array|min:1',
            'gig_ids.*' => 'exists:gigs,id',
            'payment_date' => 'nullable|date',
        ]);

        $paymentDate = $request->input('payment_date', now()->toDateString());
        $settledCount = 0;

        DB::transaction(function () use ($request, $paymentDate, &$settledCount) {
            $gigs = Gig::whereIn('id', $request->input('gig_ids'))
                ->where('artist_payment_status', '!=', 'pago')
                ->get();

            foreach ($gigs as $gig) {
                $gig->update(['artist_payment_status' => 'pago']);

                // Atualiza ou cria o settlement
                $gig->settlement()->updateOrCreate(
                    ['gig_id' => $gig->id],
                    [
                        'settlement_date' => $paymentDate,
                        'artist_payment_value' => $gig->calculated_artist_invoice_value_brl,
                        'artist_payment_paid_at' => $paymentDate,
                    ]
                );

                $settledCount++;
            }
        });

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', "{$settledCount} fechamento(s) marcado(s) como pago(s).");
    }

    /**
     * Reverte o pagamento em massa de cachês de artistas.
     */
    public function unsettleBatch(Request $request)
    {
        $request->validate([
            'gig_ids' => 'required|array|min:1',
            'gig_ids.*' => 'exists:gigs,id',
        ]);

        $unsettledCount = 0;

        DB::transaction(function () use ($request, &$unsettledCount) {
            $gigs = Gig::whereIn('id', $request->input('gig_ids'))
                ->where('artist_payment_status', 'pago')
                ->get();

            foreach ($gigs as $gig) {
                $gig->update(['artist_payment_status' => 'pendente']);

                // Limpa os dados do settlement
                $gig->settlement()->update([
                    'artist_payment_value' => null,
                    'artist_payment_paid_at' => null,
                ]);

                $unsettledCount++;
            }
        });

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', "{$unsettledCount} fechamento(s) revertido(s) para pendente.");
    }
}
