<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Models\Artist;
use App\Models\Tag;
use App\Services\ArtistFinancialsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

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
            Log::error('Erro ao criar Artista: '.$e->getMessage());

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
            Log::error('Erro ao atualizar Artista: '.$e->getMessage());

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
            Log::error('Erro ao excluir Artista: '.$e->getMessage());

            return back()->with('error', 'Erro ao excluir artista.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  ArtistFinancialsService  $financialsService  // Injeção de dependência
     */
    public function show(Artist $artist, Request $request, ArtistFinancialsService $financialsService): View
    {
        $artist->load('tags'); // Eager load tags

        // Inicia a query para as gigs, permitindo filtros
        $gigsQuery = $artist->gigs()->with('booker')->latest('gig_date');

        // Aplicar filtros da requisição, se houver
        // Ex: if ($request->filled('period')) { ... }

        // Pagina o resultado da query de gigs
        $gigs = $gigsQuery->paginate(15)->withQueryString();

        // **A LÓGICA DE CÁLCULO AGORA É DELEGADA PARA O SERVICE**
        // Passamos a coleção de gigs já paginada (ou poderíamos passar a query inteira)
        // para o service calcular as métricas apenas sobre essa seleção.
        // Para métricas GERAIS do artista, passamos a coleção completa.
        $allGigs = $artist->gigs; // Busca todas as gigs para métricas totais
        $metrics = $financialsService->getFinancialMetrics($artist, $allGigs);

        return view('artists.show', compact('artist', 'gigs', 'metrics'));
    }
}
