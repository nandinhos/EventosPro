<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Tag;
use App\Models\ActivityLog; // Importar se usar diretamente
use App\Http\Requests\StoreGigRequest;
use App\Http\Requests\UpdateGigRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema; // Para Schema::hasColumn
use Carbon\Carbon; // Para datas
use App\Models\CostCenter; // Importar o modelo CostCenter


class GigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $sortableColumns = [
            'gig_date', 'cache_value', 'currency', 'payment_status',
            'artist_payment_status', 'booker_payment_status', 'contract_status',
            'created_at', 'location_event_details', 'artist_name', 'booker_name'
        ];
        $sortBy = $request->input('sort_by', 'gig_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (!in_array($sortBy, $sortableColumns)) { $sortBy = 'gig_date'; }
        if (!in_array($sortDirection, ['asc', 'desc'])) { $sortDirection = 'desc'; }

        $query = Gig::query()
            ->select(['gigs.*', 'artists.name as artist_name', 'bookers.name as booker_name'])
            ->leftJoin('artists', 'gigs.artist_id', '=', 'artists.id')
            ->leftJoin('bookers', 'gigs.booker_id', '=', 'bookers.id');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('gigs.contract_number', 'like', "%{$searchTerm}%")
                  ->orWhere('gigs.location_event_details', 'like', "%{$searchTerm}%")
                  ->orWhere('artists.name', 'like', "%{$searchTerm}%")
                  ->orWhere('bookers.name', 'like', "%{$searchTerm}%");
            });
        }
        if ($request->filled('payment_status')) { $query->where('gigs.payment_status', $request->input('payment_status')); }
        if ($request->filled('artist_id')) { $query->where('gigs.artist_id', $request->input('artist_id')); }
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') { $query->whereNull('gigs.booker_id'); }
            else { $query->where('gigs.booker_id', $request->input('booker_id')); }
        }
        if ($request->filled('start_date')) { $query->where('gigs.gig_date', '>=', $request->input('start_date')); }
        if ($request->filled('end_date')) { $query->where('gigs.gig_date', '<=', $request->input('end_date')); }
        if ($request->filled('currency') && $request->input('currency') !== 'all') { $query->where('gigs.currency', $request->input('currency'));}

        $orderByColumn = match ($sortBy) {
            'artist_name' => 'artists.name', 'booker_name' => 'bookers.name',
            'gig_date', 'cache_value', 'currency', 'payment_status', 'artist_payment_status',
            'booker_payment_status', 'contract_status', 'created_at', 'location_event_details' => 'gigs.' . $sortBy,
            default => 'gigs.gig_date',
        };
        $query->orderBy($orderByColumn, $sortDirection);

        $gigs = $query->paginate(25)->withQueryString();
        $artistsData = Artist::orderBy('name')->pluck('name', 'id'); // Renomeado para não conflitar com $artists na view
        $bookersData = Booker::orderBy('name')->pluck('name', 'id'); // Renomeado
        $currencies = DB::table('gigs')->select('currency')->distinct()->orderBy('currency')->pluck('currency');

        return view('gigs.index', [
            'gigs' => $gigs,
            'artists' => $artistsData, // Passa como 'artists'
            'bookers' => $bookersData, // Passa como 'bookers'
            'currencies' => $currencies,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $tags = Tag::orderBy('name')->get()->groupBy('type');
        $costCenters = CostCenter::orderBy('name')->pluck('name', 'id'); // Adicionar esta linha
        $backUrlParams = $request->session()->get('gig_index_url_params', []);

        return view('gigs.create', compact('artists', 'bookers', 'tags', 'costCenters', 'backUrlParams')); // Adicionar 'costCenters'
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGigRequest $request): RedirectResponse
{
    DB::beginTransaction();
    try {
        // Cria a Gig
        $gig = Gig::create($request->validated());

        // Salva as despesas relacionadas
        if ($request->filled('cost_center_id')) {
            foreach ($request->input('cost_center_id') as $index => $costCenterId) {
                $gig->costs()->create([
                    'cost_center_id' => $costCenterId,
                    'description' => $request->input("description.$index"),
                    'value' => $request->input("value.$index"),
                    'currency' => $request->input('currency', 'BRL'),
                    'is_confirmed' => true,
                ]);
            }
        }

        DB::commit();

        return redirect()->route('gigs.index')->with('success', 'Gig criada com sucesso!');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erro ao salvar Gig: ' . $e->getMessage());
        return back()->withInput()->with('error', 'Erro ao criar gig.');
    }
}

    /**
     * Display the specified resource.
     */
    public function show(Gig $gig, Request $request): View
    {
        // Salvar parâmetros da URL da index na sessão para o botão "Voltar"
        if ($request->hasAny(['search', 'payment_status', 'artist_id', 'booker_id', 'start_date', 'end_date', 'currency', 'sort_by', 'sort_direction', 'page'])) {
            $request->session()->put('gig_index_url_params', $request->query());
        }

        $gig->load(['artist', 'booker', 'payments' => fn($q) => $q->orderBy('due_date', 'asc')->orderBy('id', 'asc'), 'settlement', 'tags', 'costs.costCenter', 'costs.confirmer']);
        $totalReceivedOriginalCurrency = $gig->payments->where('confirmed_at', '!=', null)->where('currency', $gig->currency)->sum('received_value_actual');
        $balanceOriginalCurrency = max(0, ($gig->cache_value ?? 0) - $totalReceivedOriginalCurrency);
        $activityLogs = ActivityLog::where('subject_type', Gig::class)->where('subject_id', $gig->id)->latest()->paginate(10, ['*'], 'logs_page')->withQueryString();
        $backUrlParams = $request->session()->get('gig_index_url_params', []);

        return view('gigs.show', compact(
            'gig', 'activityLogs', 'totalReceivedOriginalCurrency',
            'balanceOriginalCurrency', 'backUrlParams'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gig $gig, Request $request): View
    {
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $tags = Tag::orderBy('name')->get()->groupBy('type');
        $selectedTags = $gig->tags()->pluck('id')->toArray();
        $costCenters = CostCenter::orderBy('name')->pluck('name', 'id'); // Adicionar esta linha
        $backUrlParams = $request->session()->get('gig_index_url_params', []);

        return view('gigs.edit', compact('gig', 'artists', 'bookers', 'tags', 'selectedTags', 'costCenters', 'backUrlParams')); // Adicionar 'costCenters'
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGigRequest $request, Gig $gig): RedirectResponse
{
    DB::beginTransaction();
    try {
        // Atualiza os dados da Gig
        $gig->update($request->validated());

        // Atualiza ou cria despesas
        if ($request->has('cost_center_id')) {
            $gig->costs()->delete(); // Limpa anteriores

            foreach ($request->input('cost_center_id') as $index => $costCenterId) {
                $gig->costs()->create([
                    'cost_center_id' => $costCenterId,
                    'description' => $request->input("description.$index"),
                    'value' => $request->input("value.$index"),
                    'currency' => $request->input('currency', 'BRL'),
                    'is_confirmed' => true,
                ]);
            }
        }

        DB::commit();

        return redirect()->route('gigs.edit', $gig)->with('success', 'Gig atualizada com sucesso!');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erro ao atualizar Gig: ' . $e->getMessage());
        return back()->withInput()->with('error', 'Erro ao atualizar gig.');
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gig $gig, Request $request): RedirectResponse
    {
        try {
            $gig->delete();
            $backParams = $request->input('backParams', []);
            return redirect()->route('gigs.index', $backParams)->with('success', 'Gig excluída com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir Gig: ' . $e->getMessage(), ['exception' => $e, 'gig_id' => $gig->id]);
            return back()->with('error', 'Erro ao excluir a Gig.');
        }
    }

    /**
     * Prepara os dados das comissões ANTES de criar ou atualizar uma Gig.
     */
    private function prepareGigData(array $validatedData, ?Gig $existingGig = null): array
{
    Log::info('--- prepareGigData - Dados Recebidos ANTES da lógica ---', $validatedData);

    $preparedData = $validatedData;

    // --- Agency Commission ---
    // Se não vier tipo, assume 'percent' (ou o que estiver no $existingGig)
    $agencyCommissionType = $preparedData['agency_commission_type'] ?? ($existingGig?->agency_commission_type ?? 'percent');
    $preparedData['agency_commission_type'] = $agencyCommissionType; // Garante que seja setado

    // O campo 'agency_commission_value' do form é usado tanto para taxa (%) quanto para valor fixo.
    $agencyCommissionInputValue = $preparedData['agency_commission_value'] ?? null;

    if (strtoupper($agencyCommissionType) === 'PERCENT') {
        $preparedData['agency_commission_rate'] = $agencyCommissionInputValue ?? ($existingGig?->agency_commission_rate ?? 20.00); // Default 20% se valor nulo
        $preparedData['agency_commission_value'] = null; // Limpa o valor fixo
    } elseif (strtoupper($agencyCommissionType) === 'FIXED') {
        $preparedData['agency_commission_rate'] = null; // Limpa a taxa
        $preparedData['agency_commission_value'] = $agencyCommissionInputValue;
    } else {
        // Caso inválido ou não definido, poderia resetar para um default seguro ou manter existente
        $preparedData['agency_commission_type'] = $existingGig?->agency_commission_type ?? 'percent';
        $preparedData['agency_commission_rate'] = $existingGig?->agency_commission_rate ?? 20.00;
        $preparedData['agency_commission_value'] = $existingGig?->agency_commission_value;
    }
    Log::info('Agency Commission Processada:', [
        'type_input' => $validatedData['agency_commission_type'] ?? 'N/A',
        'value_input' => $validatedData['agency_commission_value'] ?? 'N/A',
        'type_final' => $preparedData['agency_commission_type'],
        'rate_final' => $preparedData['agency_commission_rate'],
        'value_final' => $preparedData['agency_commission_value']
    ]);

    // --- Booker Commission ---
    $bookerCommissionType = $preparedData['booker_commission_type'] ?? null; // Pode vir nulo se "Nenhuma"
    $preparedData['booker_commission_type'] = $bookerCommissionType;

    $bookerCommissionInputValue = $preparedData['booker_commission_value'] ?? null;

    if (strtoupper($bookerCommissionType ?? '') === 'PERCENT') {
        $preparedData['booker_commission_rate'] = $bookerCommissionInputValue ?? ($existingGig?->booker_commission_rate ?? ($preparedData['booker_id'] ? 5.00 : null)); // Default 5% se booker_id e valor nulo
        $preparedData['booker_commission_value'] = null;
    } elseif (strtoupper($bookerCommissionType ?? '') === 'FIXED') {
        $preparedData['booker_commission_rate'] = null;
        $preparedData['booker_commission_value'] = $bookerCommissionInputValue;
    } else { // Nenhuma ou tipo inválido
        $preparedData['booker_commission_type'] = null; // Garante que seja null
        $preparedData['booker_commission_rate'] = null;
        $preparedData['booker_commission_value'] = null;
    }
    Log::info('Booker Commission Processada:', [
        'type_input' => $validatedData['booker_commission_type'] ?? 'N/A',
        'value_input' => $validatedData['booker_commission_value'] ?? 'N/A',
        'type_final' => $preparedData['booker_commission_type'],
        'rate_final' => $preparedData['booker_commission_rate'],
        'value_final' => $preparedData['booker_commission_value']
    ]);

    // Liquid commission é sempre calculado pelo Observer/Accessor
    if (Schema::hasColumn('gigs', 'liquid_commission_value')) {
         $preparedData['liquid_commission_value'] = null; // Deixa o observer/accessor calcular
    }

    Log::info('Data preparada FINAL para salvar (prepareGigData):', $preparedData);
    return $preparedData;
}
    
    


     /**
     * Display the form/page for requesting the artist's NF.
     */
    public function showRequestNfForm(Gig $gig, Request $request): View
    {
        // Carregar relacionamentos necessários
        $gig->load(['artist', 'booker', 'costs.costCenter']);

        // --- Cálculos Financeiros (Reutilizando lógica similar à da gigs.show) ---
        $gigCacheValueBrl = $gig->cache_value_brl; // Via Accessor

        $confirmedExpensesGrouped = $gig->costs
            ->where('is_confirmed', true)
            ->groupBy(function ($cost) {
                return $cost->costCenter?->name ?? 'Outras Despesas';
            })
            ->map(function ($costsInGroup) {
                return $costsInGroup->sum('value');
            });
        $totalConfirmedExpensesBrl = $confirmedExpensesGrouped->sum();

        $agencyTotalCommissionOnGig = $gig->agency_commission_value ?? 0;

        // Valor Líquido para o Artista (NF)
        $netArtistCacheToReceive = $gigCacheValueBrl - $totalConfirmedExpensesBrl - $agencyTotalCommissionOnGig;
        // --- Fim Cálculos ---


        // Parâmetros para o botão "Voltar" na view de NF, se necessário
        $backUrlParams = $request->session()->get('gig_show_url_params', ['gig' => $gig->id]); // Tenta pegar da sessão
        if (empty(array_filter($backUrlParams, fn($k) => $k !== 'gig', ARRAY_FILTER_USE_KEY))) { // Se só tiver gig_id
            $backUrlParams = ['gig' => $gig->id]; // Default para voltar ao show
        }


        return view('gigs.request-nf', compact(
            'gig',
            'gigCacheValueBrl', // Passar o valor já convertido
            'confirmedExpensesGrouped',
            'totalConfirmedExpensesBrl',
            'agencyTotalCommissionOnGig',
            'netArtistCacheToReceive',
            'backUrlParams'
        ));
    }

}