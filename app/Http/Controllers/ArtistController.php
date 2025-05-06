<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Tag;
use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ArtistController extends Controller
{
    /** Display a listing of the resource. */
    public function index(Request $request): View
    {
        $query = Artist::withCount('gigs')->latest(); // Ordena por mais recente, conta gigs

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $artists = $query->paginate(20)->withQueryString();
        return view('artists.index', compact('artists'));
    }

    /** Show the form for creating a new resource. */
    public function create(): View
    {
        $tags = Tag::orderBy('name')->get()->groupBy('type');
        return view('artists.create', compact('tags'));
    }

    /** Store a newly created resource in storage. */
    public function store(StoreArtistRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $artist = Artist::create($request->validated());
            if ($request->filled('tags')) {
                $artist->tags()->sync($request->input('tags'));
            }
            DB::commit();
            return redirect()->route('artists.index')->with('success', 'Artista criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar Artista: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao criar artista.');
        }
    }

    /** Show the form for editing the specified resource. */
    public function edit(Artist $artist): View
    {
        $tags = Tag::orderBy('name')->get()->groupBy('type');
        $selectedTags = $artist->tags()->pluck('id')->toArray();
        return view('artists.edit', compact('artist', 'tags', 'selectedTags'));
    }

    /** Update the specified resource in storage. */
    public function update(UpdateArtistRequest $request, Artist $artist): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $artist->update($request->validated());
            $artist->tags()->sync($request->input('tags', []));
            DB::commit();
            return redirect()->route('artists.index')->with('success', 'Artista atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar Artista: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Erro ao atualizar artista.');
        }
    }

    /** Remove the specified resource from storage. */
    public function destroy(Artist $artist): RedirectResponse
    {
         // Adicionar verificação se o artista tem gigs futuras antes de excluir?
        try {
            $artist->delete(); // Soft delete
            return redirect()->route('artists.index')->with('success', 'Artista excluído com sucesso!');
        } catch (\Exception $e) {
             Log::error('Erro ao excluir Artista: ' . $e->getMessage());
            return back()->with('error', 'Erro ao excluir artista.');
        }
    }

    /** Display the specified resource. */
    /** Display the specified resource. */
    public function show(Artist $artist, Request $request): View // Adicionar Request
    {
        $artist->load('tags');
        $gigsQuery = $artist->gigs()->with('booker'); // Carrega booker

        // --- Métricas Financeiras do ARTISTA ---
        $totalGigs = $gigsQuery->clone()->count();

        // Soma do CACHÊ LÍQUIDO (Valor BRL - Comissões Totais) onde o pagamento AO ARTISTA foi feito
        // Isso é uma aproximação, o valor exato pago pode estar no settlement ou ser calculado
        // Vamos somar cache_value_brl ONDE artist_payment_status = 'pago' por enquanto
        // Idealmente, teríamos o valor exato pago ao artista registrado em algum lugar.
        $cacheReceivedByArtist = $gigsQuery->clone()
                                            ->where('artist_payment_status', 'pago')
                                            ->sum(DB::raw('cache_value - IFNULL(agency_commission_value, 0)')); // Soma (Cachê BRL - Comissão Agência)
                                            // Ou ->sum('artist_net_amount') se tivéssemos essa coluna

        // Soma do CACHÊ LÍQUIDO onde o pagamento AO ARTISTA está pendente
        $cachePendingForArtist = $gigsQuery->clone()
                                            ->where('artist_payment_status', 'pendente')
                                             ->sum(DB::raw('cache_value - IFNULL(agency_commission_value, 0)')); // Soma (Cachê BRL - Comissão Agência)

        // --- Filtragem e Paginação das Gigs (igual antes) ---
         if ($request->filled('gig_status')) { $gigsQuery->where('payment_status', $request->input('gig_status')); }
         if ($request->filled('start_date')) { $gigsQuery->where('gig_date', '>=', $request->input('start_date')); }
         if ($request->filled('end_date')) { $gigsQuery->where('gig_date', '<=', $request->input('end_date')); }
         // Adicionar filtro por booker?
         // if ($request->filled('booker_id')) { ... }

        $gigs = $gigsQuery->latest('gig_date')->paginate(15)->withQueryString();

        // Montar dados para os cards de métricas
        $metrics = [
            'total_gigs' => $totalGigs,
            'cache_received_brl' => $cacheReceivedByArtist ?? 0,
            'cache_pending_brl' => $cachePendingForArtist ?? 0,
        ];

         // Dados para filtros (se adicionar)
        // $bookers = Booker::orderBy('name')->pluck('name', 'id');

        return view('artists.show', compact('artist', 'gigs', 'metrics')); // Remover $artists, $bookers se não usar nos filtros aqui
    }
}