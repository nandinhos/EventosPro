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

    public function store(StoreGigRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            // Prepara dados adicionais antes de criar
            $preparedData = $this->prepareGigData($validated);

            $gig = Gig::create($preparedData);
            if ($request->filled('tags')) {
                $gig->tags()->sync($request->input('tags'));
            }
            DB::commit();
            return redirect()->route('gigs.index')->with('success', 'Gig criada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar Gig: ' . $e->getMessage(), ['exception' => $e]);
            return back()->withInput()->with('error', 'Erro ao criar a Gig.');
        }
    }

    public function edit(Gig $gig): View
    {
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $tags = Tag::orderBy('name')->get()->groupBy('type');
        $selectedTags = $gig->tags()->pluck('id')->toArray();
        return view('gigs.edit', compact('gig', 'artists', 'bookers', 'tags', 'selectedTags'));
    }

    public function update(UpdateGigRequest $request, Gig $gig): RedirectResponse
    {
        $validated = $request->validated();
        DB::beginTransaction();
        try {
            // Prepara dados adicionais antes de atualizar
            $preparedData = $this->prepareGigData($validated);

            $gig->update($preparedData);
            $gig->tags()->sync($request->input('tags', []));
            DB::commit();
            return redirect()->route('gigs.index')->with('success', 'Gig atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar Gig: ' . $e->getMessage(), ['exception' => $e, 'gig_id' => $gig->id]);
            return back()->withInput()->with('error', 'Erro ao atualizar a Gig.');
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

    /**
     * Método auxiliar para calcular e preparar os dados da Gig antes de salvar/atualizar.
     */
    private function prepareGigData(array $validatedData): array
    {
        // 1. Calcular cache_value_brl (sem alterações)
        $cacheValueBrl = $validatedData['cache_value'];
        if (strtoupper($validatedData['currency']) !== 'BRL' && !empty($validatedData['exchange_rate'])) {
            $cacheValueBrl = $validatedData['cache_value'] * $validatedData['exchange_rate'];
        }
        $validatedData['cache_value_brl'] = $cacheValueBrl;

        // 2. Calcular Comissões
        $baseCommission = $cacheValueBrl - ($validatedData['expenses_value_brl'] ?? 0);

        // Booker Commission (sem alterações)
        $bookerRate = null;
        $bookerValue = null;
        // Acessar com null coalescing ou isset para segurança extra
        $bookerCommissionType = $validatedData['booker_commission_type'] ?? null;
        $bookerCommissionInputValue = $validatedData['booker_commission_value'] ?? null;

        if ($bookerCommissionType === 'percent' && $bookerCommissionInputValue !== null) {
            $bookerRate = $bookerCommissionInputValue;
            $bookerValue = $baseCommission * ($bookerRate / 100);
        } elseif ($bookerCommissionType === 'fixed' && $bookerCommissionInputValue !== null) {
            $bookerRate = null;
            $bookerValue = $bookerCommissionInputValue;
        }
        $validatedData['booker_commission_rate'] = $bookerRate;
        $validatedData['booker_commission_value'] = $bookerValue;

        // --- Lógica da Agência SIMPLIFICADA ---
        // Define valores padrão ou nulos, já que não vêm do form
        $agencyRate = 20.00; // <- DEFINIR AQUI a taxa padrão da agência (ou null)
        $agencyValue = $baseCommission * ($agencyRate / 100); // Calcula o valor baseado na taxa padrão
        // Ou deixe nulo se não for calcular/salvar agora:
        // $agencyRate = null;
        // $agencyValue = null;

        $validatedData['agency_commission_rate'] = $agencyRate;
        $validatedData['agency_commission_value'] = $agencyValue;
        // Removemos a tentativa de ler agency_commission_type/value do $validatedData
        // --- Fim da Simplificação ---

        // 3. Calcular Comissão Líquida
        $validatedData['liquid_commission_value'] = ($agencyValue ?? 0) - ($bookerValue ?? 0);

        // Remover campos que não são colunas diretas, se houver (não temos neste caso)

        return $validatedData;
    }


}