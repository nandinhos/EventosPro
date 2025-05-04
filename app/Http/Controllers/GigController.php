<?php

namespace App\Http\Controllers;

use App\Models\Gig; // Importar o modelo Gig
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\StoreGigRequest;
use App\Http\Requests\UpdateGigRequest;
use App\Models\Contract; // Ainda pode ser útil para selects? (Não mais diretamente)
// Importar outros modelos se precisar para filtros avançados depois
use App\Models\Artist;
use App\Models\Booker;
use App\Models\Tag; // Importar Tag
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\CommissionCalculationService; // <-- Criaremos este serviço depois
use App\Models\ActivityLog; // Se for usar o modelo de log diretamente


class GigController extends Controller
{
    /**
     * Display a listing of the resource.
     * Mostra a lista de Gigs (Datas).
     */
    public function index(Request $request): View
    {
        // Query inicial com Eager Loading dos relacionamentos necessários para a tabela
        $query = Gig::with(['artist', 'booker']) // Carrega Artist e Booker associados
                    ->latest('gig_date'); // Ordena pela data do evento mais recente primeiro

        // --- Lógica de Filtros ---
        // Busca Livre (Número Contrato, Artista, Booker, Local)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('contract_number', 'like', "%{$searchTerm}%")
                  ->orWhere('location_event_details', 'like', "%{$searchTerm}%")
                  ->orWhereHas('artist', fn($aq) => $aq->where('name', 'like', "%{$searchTerm}%"))
                  ->orWhereHas('booker', fn($bq) => $bq->where('name', 'like', "%{$searchTerm}%"));
            });
        }

        // Filtro por Status de Pagamento Geral
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Filtro por Artista
        if ($request->filled('artist_id')) {
            $query->where('artist_id', $request->input('artist_id'));
        }

        // Filtro por Booker
        if ($request->filled('booker_id')) {
            // Se 'sem_booker' for selecionado, busca onde booker_id é NULL
            if ($request->input('booker_id') === 'sem_booker') {
                 $query->whereNull('booker_id');
            } else {
                 $query->where('booker_id', $request->input('booker_id'));
            }
        }

        // Filtro por Período (Data da Gig)
        if ($request->filled('start_date')) {
            $query->where('gig_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('gig_date', '<=', $request->input('end_date'));
        }
        // --- Fim dos Filtros ---


        // Paginar os resultados
        $gigs = $query->paginate(25)->withQueryString(); // 25 itens por página, mantém filtros na URL

        // Buscar dados para preencher os selects dos filtros
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');

        // Passa os dados para a view
        return view('gigs.index', compact('gigs', 'artists', 'bookers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // Buscar dados para preencher os selects no formulário
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $tags = Tag::orderBy('name')->get()->groupBy('type'); // Buscar e agrupar tags por tipo

        // Passa os dados para a view 'gigs.create'
        // Usamos um array vazio para $gig para que a view do form possa ser reutilizada para edit
        return view('gigs.create', compact('artists', 'bookers', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGigRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Iniciar transação
        DB::beginTransaction();
        try {
            // 1. Calcular valores dependentes (Câmbio, Comissões)
            $cacheValueBrl = $validated['cache_value'];
            if (strtoupper($validated['currency']) !== 'BRL' && !empty($validated['exchange_rate'])) {
                $cacheValueBrl = $validated['cache_value'] * $validated['exchange_rate'];
            }
             $validated['cache_value_brl'] = $cacheValueBrl; // Adiciona ao array validado

            // Calcular comissões (Idealmente usar um Service)
            // Exemplo simples (precisa refinar com Service depois):
             $baseCommission = $cacheValueBrl - ($validated['expenses_value_brl'] ?? 0);
             $agencyCommissionValue = null; // Assumindo que agência não tem comissão separada por enquanto
             $bookerCommissionValue = null;

             if ($validated['booker_commission_type'] === 'percent' && isset($validated['booker_commission_value'])) {
                 $bookerCommissionValue = $baseCommission * ($validated['booker_commission_value'] / 100);
             } elseif ($validated['booker_commission_type'] === 'fixed' && isset($validated['booker_commission_value'])) {
                 $bookerCommissionValue = $validated['booker_commission_value']; // Valor já é o fixo
             }
             $validated['booker_commission_value'] = $bookerCommissionValue; // Sobrescreve/adiciona o valor calculado/fixo
             // $validated['agency_commission_value'] = $agencyCommissionValue; // Adiciona se tiver

             // Calcular Comissão Líquida (Agência - Booker)
             // Neste modelo simplificado, comissão agência = comissão total (ex: 20%)
             // Vamos assumir 20% padrão da agência por enquanto
             $agencyRate = 20.00; // TODO: Tornar configurável
             $totalAgencyCommission = $baseCommission * ($agencyRate / 100);
             $validated['agency_commission_value'] = $totalAgencyCommission; // Salva comissão total da agência
             $validated['liquid_commission_value'] = $totalAgencyCommission - ($bookerCommissionValue ?? 0);


            // 2. Criar a Gig
            $gig = Gig::create($validated);

            // 3. Associar Tags (se enviadas)
            if ($request->filled('tags')) {
                $gig->tags()->sync($request->input('tags')); // sync() anexa apenas os IDs enviados
            }

            // 4. Commitar a transação
            DB::commit();

            // 5. Redirecionar com sucesso
            return redirect()->route('gigs.index')->with('success', 'Gig criada com sucesso!');

        } catch (\Exception $e) {
            // 6. Rollback em caso de erro
            DB::rollBack();
            Log::error('Erro ao criar Gig: ' . $e->getMessage(), ['exception' => $e]); // Log completo
            // 7. Redirecionar de volta com erro
            return back()->withInput()->with('error', 'Erro ao criar a Gig. Verifique os dados e tente novamente.');
        }
    }

    /**
     * Display the specified resource.
     * Mostra os detalhes de uma Gig específica.
     */
    public function show(Gig $gig): View // Usa Route Model Binding
    {
        // Carregar relacionamentos para exibir na view
        $gig->load([
            'artist', // Artista principal
            'booker', // Booker (pode ser null)
            'payments' => function ($query) { // Carrega pagamentos ordenados
                $query->orderBy('due_date', 'asc');
            },
            'settlement', // O acerto (pode ser null)
            'tags' => function ($query) { // Carrega tags ordenadas
                $query->orderBy('name', 'asc');
            },
            // 'activityLogs' // Se tiver o relacionamento definido no modelo Gig para a tabela de logs
        ]);

        // Buscar logs de atividade relacionados a esta Gig (exemplo básico se não usar pacote)
        // Substitua 'App\Models\Gig' pelo caminho correto se necessário
        $activityLogs = ActivityLog::where('subject_type', Gig::class) // Ou $gig->getMorphClass()
                                   ->where('subject_id', $gig->id)
                                   ->latest() // Mais recentes primeiro
                                   ->paginate(10, ['*'], 'logs_page'); // Paginar logs com nome diferente

        // Passar a gig e os logs para a view
        return view('gigs.show', compact('gig', 'activityLogs'));
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gig $gig): View // Usa Route Model Binding
    {
        // Buscar dados para os selects
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $tags = Tag::orderBy('name')->get()->groupBy('type');

        // Pegar os IDs das tags já associadas a esta Gig
        $selectedTags = $gig->tags()->pluck('id')->toArray();

        // Retorna a view de edição, passando a gig e os dados dos selects
        return view('gigs.edit', compact('gig', 'artists', 'bookers', 'tags', 'selectedTags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGigRequest $request, Gig $gig): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
             // 1. Recalcular valores dependentes (igual ao store)
            $cacheValueBrl = $validated['cache_value'];
            if (strtoupper($validated['currency']) !== 'BRL' && !empty($validated['exchange_rate'])) {
                $cacheValueBrl = $validated['cache_value'] * $validated['exchange_rate'];
            }
             $validated['cache_value_brl'] = $cacheValueBrl;

             $baseCommission = $cacheValueBrl - ($validated['expenses_value_brl'] ?? 0);
             $bookerCommissionValue = null;

             if ($validated['booker_commission_type'] === 'percent' && isset($validated['booker_commission_value'])) {
                 $bookerCommissionValue = $baseCommission * ($validated['booker_commission_value'] / 100);
             } elseif ($validated['booker_commission_type'] === 'fixed' && isset($validated['booker_commission_value'])) {
                 $bookerCommissionValue = $validated['booker_commission_value'];
             }
             $validated['booker_commission_value'] = $bookerCommissionValue;

             // Assumindo comissão agência 20% (simplificado)
             $agencyRate = 20.00; // TODO: Configurável
             $totalAgencyCommission = $baseCommission * ($agencyRate / 100);
             $validated['agency_commission_value'] = $totalAgencyCommission;
             $validated['liquid_commission_value'] = $totalAgencyCommission - ($bookerCommissionValue ?? 0);

            // 2. Atualizar a Gig com os dados validados e calculados
            $gig->update($validated);

            // 3. Sincronizar Tags (remove as não enviadas, adiciona as novas)
            $gig->tags()->sync($request->input('tags', [])); // Passa array vazio se 'tags' não for enviado

            // 4. Commitar
            DB::commit();

            // 5. Redirecionar para a lista ou para o show com sucesso
            return redirect()->route('gigs.index')->with('success', 'Gig atualizada com sucesso!');
            // Ou redirecionar para show: return redirect()->route('gigs.show', $gig)->with('success', ...);


        } catch (\Exception $e) {
             // 6. Rollback e Log
            DB::rollBack();
            Log::error('Erro ao atualizar Gig: ' . $e->getMessage(), ['exception' => $e, 'gig_id' => $gig->id]);
             // 7. Redirecionar de volta com erro
            return back()->withInput()->with('error', 'Erro ao atualizar a Gig. Verifique os dados e tente novamente.');
        }
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(Gig $gig): RedirectResponse // Usa Route Model Binding
    {
        try {
            // O SoftDelete apenas preenche a coluna 'deleted_at'
            $gig->delete();

            // Redireciona para a lista com mensagem de sucesso
            return redirect()->route('gigs.index')->with('success', 'Gig excluída com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao excluir Gig: ' . $e->getMessage(), ['exception' => $e, 'gig_id' => $gig->id]);
            // Redireciona de volta com erro
            return back()->with('error', 'Erro ao excluir a Gig.');
        }
    }

}