<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArtistRequest;
use App\Http\Requests\UpdateArtistRequest;
use App\Models\Artist;
use App\Models\CostCenter;
use App\Models\Gig;
use App\Models\Settlement;
use App\Models\Tag;
use App\Services\ArtistFinancialsService;
use App\Services\GigFinancialCalculatorService;
use Carbon\Carbon;
use Exception;
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        $artist->load('tags');

        // 1. Período (com valores padrão)
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : Carbon::now()->startOfYear();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::now()->endOfYear();

        // 2. Busca e Filtra Gigs no período (eager load para evitar N+1)
        $gigsInPeriod = $artist->gigs()
            ->with(['booker', 'gigCosts.costCenter', 'payments'])
            ->whereBetween('gig_date', [$startDate, $endDate])
            ->orderBy('gig_date', 'desc')
            ->get();

        // 3. Separa Gigs em Realizadas e Futuras
        $today = Carbon::today();
        $realizedGigs = $gigsInPeriod->where('gig_date', '<=', $today);
        $futureGigs = $gigsInPeriod->where('gig_date', '>', $today);

        // 4. Calcula Métricas Financeiras para o período
        $metrics = $financialsService->getFinancialMetrics($artist, $gigsInPeriod);

        // 5. Busca Cost Centers para o gerenciamento de despesas
        $costCenters = CostCenter::orderBy('name')->get();

        // 6. Retorna a view com os dados
        return view('artists.show', compact(
            'artist',
            'startDate',
            'endDate',
            'realizedGigs',
            'futureGigs',
            'metrics',
            'costCenters'
        ));
    }

    /**
     * Settle batch artist payments for multiple gigs at once.
     */
    public function settleBatchArtistPayments(Request $request, GigFinancialCalculatorService $calculator): RedirectResponse
    {
        $validated = $request->validate([
            'gig_ids' => 'required|array',
            'gig_ids.*' => 'integer|exists:gigs,id',
            'payment_date' => 'required|date|before_or_equal:today',
            'tab' => 'nullable|string',
        ]);

        $gigIds = $validated['gig_ids'];
        $paymentDate = Carbon::parse($validated['payment_date']);

        // Fetch the gigs with necessary relationships (eager load para evitar N+1)
        $gigs = Gig::with(['artist', 'gigCosts', 'payments'])
            ->whereIn('id', $gigIds)
            ->get();

        // Validate all gigs before processing
        $errors = [];
        foreach ($gigs as $gig) {
            // Business rule: Can only pay for realized events (past gig_date)
            if ($gig->gig_date->isFuture()) {
                $errors[] = "Gig #{$gig->id} ainda não aconteceu. Não é possível pagar antecipadamente.";
            }

            // Check if already paid
            if ($gig->artist_payment_status === 'pago') {
                $errors[] = "Gig #{$gig->id} já está marcado como pago.";
            }

            // Validate all costs are confirmed
            $pendingCosts = $gig->gigCosts->where('is_confirmed', false);
            if ($pendingCosts->count() > 0) {
                $errors[] = "Gig #{$gig->id} possui despesas não confirmadas. Confirme todas as despesas antes de pagar.";
            }
        }

        $tab = $validated['tab'] ?? 'overview';

        if (! empty($errors)) {
            return back()->withInput()->with('error', 'Erros encontrados: '.implode(' | ', $errors));
        }

        // Process batch payment in transaction
        DB::beginTransaction();
        try {
            $totalPaid = 0;
            $processedCount = 0;

            foreach ($gigs as $gig) {
                // Calculate artist net payout
                $artistNetPayout = $calculator->calculateArtistNetPayoutBrl($gig);

                // Create or update settlement record
                $settlement = Settlement::firstOrNew(['gig_id' => $gig->id]);
                $settlement->settlement_date = $paymentDate; // Required field
                $settlement->artist_payment_value = $artistNetPayout;
                $settlement->artist_payment_paid_at = $paymentDate;
                $settlement->save();

                // Update gig payment status
                $gig->artist_payment_status = 'pago';
                $gig->save();

                $totalPaid += $artistNetPayout;
                $processedCount++;
            }

            DB::commit();

            // Get the first gig to find the artist
            $firstGig = $gigs->first();

            return redirect()->route('artists.show', ['artist' => $firstGig->artist_id, 'tab' => $tab])
                ->with('success', "Pagamento em massa realizado com sucesso! {$processedCount} evento(s) processado(s). Total pago: R$ ".number_format($totalPaid, 2, ',', '.'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao processar pagamento em massa de artistas: '.$e->getMessage());

            return back()->with('error', 'Erro ao processar pagamento em massa. Tente novamente.');
        }
    }

    /**
     * Unsettle (reverse) batch artist payments for multiple gigs at once.
     */
    public function unsettleBatchArtistPayments(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gig_ids' => 'required|array',
            'gig_ids.*' => 'integer|exists:gigs,id',
            'tab' => 'nullable|string',
        ]);

        $gigIds = $validated['gig_ids'];
        $tab = $validated['tab'] ?? 'overview';

        // Fetch the gigs
        $gigs = Gig::whereIn('id', $gigIds)->get();

        // Validate all gigs before processing
        $errors = [];
        foreach ($gigs as $gig) {
            // Check if payment status is 'pago'
            if ($gig->artist_payment_status !== 'pago') {
                $errors[] = "Gig #{$gig->id} não está marcado como pago.";
            }
        }

        if (! empty($errors)) {
            return back()->withInput()->with('error', 'Erros encontrados: '.implode(' | ', $errors));
        }

        // Process batch unsettle in transaction
        DB::beginTransaction();
        try {
            $processedCount = 0;

            foreach ($gigs as $gig) {
                // Find settlement record and nullify artist payment fields
                $settlement = Settlement::where('gig_id', $gig->id)->first();
                if ($settlement) {
                    $settlement->artist_payment_value = null;
                    $settlement->artist_payment_paid_at = null;
                    $settlement->save();
                }

                // Update gig payment status to pending
                $gig->artist_payment_status = 'pendente';
                $gig->save();

                $processedCount++;
            }

            DB::commit();

            // Get the first gig to find the artist
            $firstGig = $gigs->first();

            return redirect()->route('artists.show', ['artist' => $firstGig->artist_id, 'tab' => $tab])
                ->with('success', "Pagamento desfeito com sucesso! {$processedCount} evento(s) marcado(s) como pendente.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao desfazer pagamento em massa de artistas: '.$e->getMessage());

            return back()->with('error', 'Erro ao desfazer pagamento em massa. Tente novamente.');
        }
    }
}
