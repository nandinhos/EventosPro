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
use Carbon\Carbon;


class GigController extends Controller
{
    /**
     * Display a listing of the resource.
     * Mostra a lista de Gigs (Datas).
     */
    public function index(Request $request): View
    {
        // --- Ordenação ---
        // 1. Colunas permitidas para ordenação (colunas da tabela 'gigs')
        $sortableColumns = [
            'gig_date', 'cache_value', 'currency', 'payment_status',
            'artist_payment_status', 'booker_payment_status', 'contract_status',
            'created_at' // Adicionar outras colunas da 'gigs' se desejar
            // Ordenar por colunas relacionadas (artist.name, booker.name) requer JOINs ou abordagens mais complexas.
            // Vamos manter simples por enquanto, ordenando apenas por colunas de 'gigs'.
        ];

        // 2. Pegar parâmetros da URL ou usar default
        $sortBy = $request->input('sort_by', 'gig_date'); // Default: ordenar por data da gig
        $sortDirection = $request->input('sort_direction', 'desc'); // Default: descendente (mais recentes primeiro)

        // 3. Validar parâmetros
        if (!in_array($sortBy, $sortableColumns)) {
            $sortBy = 'gig_date'; // Volta pro default se inválido
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc'; // Volta pro default se inválido
        }
        // --- Fim Ordenação ---


        $query = Gig::with(['artist', 'booker']); // Eager loading

        // --- Filtros ---
        // Busca livre (pesquisa em múltiplos campos)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('artist', function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('booker', function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                })
                ->orWhere('location_event_details', 'like', "%{$searchTerm}%")
                ->orWhere('contract_number', 'like', "%{$searchTerm}%");
            });
        }

        // Filtro por status de pagamento
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Filtro por artista
        if ($request->filled('artist_id')) {
            $query->where('artist_id', $request->input('artist_id'));
        }
        // Filtro por booker
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') {
                $query->whereNull('booker_id');
            } else {
                $query->where('booker_id', $request->input('booker_id'));
            }
        }

        // Filtro por data inicial
        if ($request->filled('start_date')) {
            $query->whereDate('gig_date', '>=', $request->input('start_date'));
        }

        // Filtro por data final
        if ($request->filled('end_date')) {
            $query->whereDate('gig_date', '<=', $request->input('end_date'));
        }

        // Filtro por moeda
        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            $query->where('currency', $request->input('currency'));
        }
        // --- Fim Filtros ---

        // Aplicar ordenação ANTES de paginar
        $query->orderBy($sortBy, $sortDirection);

        $gigs = $query->paginate(25)->withQueryString();

        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = Gig::select('currency')->distinct()->orderBy('currency')->pluck('currency');

        // Passar variáveis de ordenação para a view
        return view('gigs.index', compact(
            'gigs',
            'artists',
            'bookers',
            'currencies',
            'sortBy',          // <-- Passar para view
            'sortDirection'    // <-- Passar para view
        ));
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
        $backUrlParams = request()->query(); // Pega os parâmetros da URL atual para voltar depois

        // Passa os dados para a view 'gigs.create'
        // Usamos um array vazio para $gig para que a view do form possa ser reutilizada para edit
        return view('gigs.create', compact('artists', 'bookers', 'tags', 'backUrlParams'));
    }

    /**
     * Store a newly created resource in storage.
     * Armazena uma nova Gig.
     */

     public function store(StoreGigRequest $request): RedirectResponse
     {
         $validated = $request->validated();
         // Pega os parâmetros de volta que foram enviados como hidden input 'backParams'
         $backParams = $request->input('backParams', []); // Pega o array 'backParams', default vazio
 
         DB::beginTransaction();
         try {
             $preparedData = $this->prepareGigData($validated);
             $gig = Gig::create($preparedData);
             if ($request->filled('tags')) {
                 $gig->tags()->sync($request->input('tags'));
             }
             DB::commit();
 
             // --- CORREÇÃO AQUI: Usa $backParams no redirect ---
             return redirect()->route('gigs.index', $backParams)->with('success', 'Gig criada com sucesso!');
 
         } catch (\Exception $e) {
             DB::rollBack();
             Log::error('Erro ao criar Gig: ' . $e->getMessage(), ['exception' => $e]);
             // Ao voltar com erro, também passa os backParams para o form ser repopulado
             return back()->withInput()->withErrors(['error' => 'Erro ao criar a Gig. Verifique os dados e tente novamente.'])->with('backUrlParams', $backParams); // Passa de volta para o form
         }
     }


    // --- MÉTODO UPDATE ---
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGigRequest $request, Gig $gig): RedirectResponse
    {
        $validated = $request->validated();
        // Pega os parâmetros de volta que foram enviados como hidden input 'backParams'
        $backParams = $request->input('backParams', []); // Pega o array 'backParams', default vazio

        DB::beginTransaction();
        try {
            $preparedData = $this->prepareGigData($validated);
            $gig->update($preparedData);
            $gig->tags()->sync($request->input('tags', []));
            DB::commit();

            // --- CORREÇÃO AQUI: Usa $backParams no redirect ---
            return redirect()->route('gigs.index', $backParams)->with('success', 'Gig atualizada com sucesso!');
            // Se preferir voltar para o show com filtros:
            // return redirect()->route('gigs.show', ['gig' => $gig] + $backParams)->with('success', '...');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar Gig: ' . $e->getMessage(), ['exception' => $e, 'gig_id' => $gig->id]);
             // Ao voltar com erro, também passa os backParams para o form ser repopulado
            return back()->withInput()->withErrors(['error' => 'Erro ao atualizar a Gig.'])->with('backUrlParams', $backParams); // Passa de volta para o form
        }
    }
    // --- FIM MÉTODO UPDATE ---

    /**
     * Display the specified resource.
     */
    public function show(Gig $gig): View
    {
        $gig->load([
            'artist', 'booker',
            'payments' => fn($q)=>$q->orderBy('due_date', 'asc'),
            'settlement', 'tags' => fn($q)=>$q->orderBy('name','asc'),
            'settlement',
            'tags',
            'costs.costCenter', // <-- Carrega os custos e seus centros de custo
            'costs.confirmer'   // <-- Carrega quem confirmou o custo
        ]);

        // --- Calcular Resumo Financeiro ATUALIZADO v4 ---
        $totalDueOriginal = $gig->cache_value ?? 0;
        $gigCurrency = strtoupper($gig->currency ?? 'BRL'); // <-- DEFINIR $gigCurrency AQUI TAMBÉM

        // Calcular total RECEBIDO e CONFIRMADO na moeda original da Gig
        $totalReceivedOriginalCurrency = $gig->payments // Pega a coleção já carregada
                                            ->where('currency', $gigCurrency) // <-- USA $gigCurrency
                                            ->whereNotNull('confirmed_at')
                                            ->sum(function($payment) {
                                                // Soma o valor real ou o devido como fallback
                                                return $payment->received_value_actual ?? $payment->due_value ?? 0;
                                            });
        $totalReceivedOriginalCurrency = round($totalReceivedOriginalCurrency, 2);

        // Calcular Saldo na Moeda Original
        $balanceOriginalCurrency = $totalDueOriginal - $totalReceivedOriginalCurrency;
        // --- Fim do Cálculo Atualizado ---


        // ... (busca de logs) ...
         $activityLogs = ActivityLog::where('subject_type', Gig::class)
                                   ->where('subject_id', $gig->id)
                                   ->latest()
                                   ->paginate(10, ['*'], 'logs_page');

        $backUrlParams = request()->query(); // Pega os parâmetros da URL atual para voltar depois
        // Passa os dados para a view


        // Passa os dados atualizados para a view
        return view('gigs.show', compact(
            'gig',
            'activityLogs',
            'totalDueOriginal', // Valor original
            'gigCurrency', // <-- Passa a moeda original para a view (útil)
            'totalReceivedOriginalCurrency', // Recebido na moeda original (confirmado)
            'balanceOriginalCurrency', // Saldo na moeda original
            'backUrlParams' // Parâmetros da URL atual para voltar depois
        ));
    }


    // --- MÉTODO EDIT ---
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gig $gig): View // Usa Route Model Binding
    {
        // Buscar dados para os selects
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->pluck('name', 'id');
        $tags = Tag::orderBy('name')->get()->groupBy('type'); // Agrupado por tipo

        // Pegar os IDs das tags já associadas a esta Gig para pré-selecionar
        $selectedTags = $gig->tags()->pluck('tags.id')->toArray(); // 'tags.id' para evitar ambiguidade

        $backUrlParams = request()->query(); // Pega os parâmetros da URL atual para voltar depois

        // Retorna a view de edição, passando a gig e os dados dos selects/tags
        return view('gigs.edit', compact('gig', 'artists', 'bookers', 'tags', 'selectedTags', 'backUrlParams'));
    }
    // --- FIM MÉTODO EDIT ---

    
    
    // --- MÉTODO DESTROY ---
    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(Request $request, Gig $gig): RedirectResponse // Usar Request normal para pegar filtros
{
    // Pega os filtros dos campos hidden
    $backUrlParams = $request->except(['_token', '_method']); // Pega tudo exceto token/method

    try {
        $gig->delete();
        return redirect()->route('gigs.index', $backUrlParams)->with('success', 'Gig excluída com sucesso!');
    } catch (\Exception $e) {
         Log::error(/*...*/);
        return redirect()->route('gigs.index', $backUrlParams)->with('error', 'Erro ao excluir a Gig.'); // Volta para index com filtros e erro
    }
}
     // --- FIM MÉTODO DESTROY ---

    /**
     * Método auxiliar para calcular e preparar os dados da Gig antes de salvar/atualizar.
     */
    private function prepareGigData(array $validatedData): array
{
    // 1. Remover cálculo de cache_value_brl e exchange_rate daqui
    $cacheValue = $validatedData['cache_value'] ?? 0; // Pega o valor bruto original
    $expensesValueBrl = $validatedData['expenses_value_brl'] ?? 0;

    // Base da comissão é o valor bruto menos despesas
    // ATENÇÃO: Se o cache_value não for BRL, este cálculo base está incorreto!
    // Precisamos decidir como calcular comissão % em moeda estrangeira.
    // Opção 1: Calcular comissão sobre valor BRL apenas no momento do *pagamento* dela.
    // Opção 2: Calcular comissão sobre valor original e salvar na moeda original?
    // Opção 3: Exigir uma taxa de câmbio de REFERÊNCIA no form da Gig para este cálculo?

    // --- VAMOS SIMPLIFICAR POR ENQUANTO: Comissão calculada apenas se moeda for BRL ---
    $baseCommission = 0;
    if (strtoupper($validatedData['currency'] ?? 'BRL') === 'BRL') {
         $baseCommission = max(0, $cacheValue - $expensesValueBrl);
    } else {
        // Se não for BRL, não calculamos a comissão automaticamente aqui
         Log::warning("Cálculo de comissão não aplicado para Gig com moeda {$validatedData['currency']}. Será calculado no acerto/pagamento.");
    }

    // 2. Calcular Comissões (Só aplicável se base > 0)
    $bookerRate = null; $bookerValue = null;
    $agencyRate = null; $agencyValue = null; // Removendo default 20% daqui
    $liquidCommissionValue = null;

    if ($baseCommission > 0) { // Só calcula se a base for BRL e positiva
        // Booker Commission
        $bookerCommissionType = $validatedData['booker_commission_type'] ?? null;
        $bookerCommissionInputValue = $validatedData['booker_commission_value'] ?? null;
        if ($bookerCommissionType === 'percent' && $bookerCommissionInputValue !== null) {
            $bookerRate = $bookerCommissionInputValue; $bookerValue = $baseCommission * ($bookerRate / 100);
        } elseif ($bookerCommissionType === 'fixed' && $bookerCommissionInputValue !== null) {
            $bookerRate = null; $bookerValue = $bookerCommissionInputValue;
        }

        // Agency Commission (se houver campos no form)
        $agencyCommissionType = $validatedData['agency_commission_type'] ?? null;
        $agencyCommissionInputValue = $validatedData['agency_commission_value'] ?? null;
         if ($agencyCommissionType === 'percent' && $agencyCommissionInputValue !== null) {
             $agencyRate = $agencyCommissionInputValue; $agencyValue = $baseCommission * ($agencyRate / 100);
         } elseif ($agencyCommissionType === 'fixed' && $agencyCommissionInputValue !== null) {
             $agencyRate = null; $agencyValue = $agencyCommissionInputValue;
         }

        // Calcular Comissão Líquida
         $liquidCommissionValue = ($agencyValue ?? 0) - ($bookerValue ?? 0);
    }

    // Atribui os valores calculados (ou nulos) ao array validado
    $validatedData['booker_commission_rate'] = $bookerRate;
    $validatedData['booker_commission_value'] = $bookerValue;
    $validatedData['agency_commission_rate'] = $agencyRate;
    $validatedData['agency_commission_value'] = $agencyValue;
    $validatedData['liquid_commission_value'] = $liquidCommissionValue;

    return $validatedData;
}
     // --- FIM MÉTODO PREPARE ---


}