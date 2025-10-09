<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGigRequest;
use App\Http\Requests\UpdateGigRequest;
use App\Models\ActivityLog;
use App\Models\Artist;
use App\Models\Booker;
use App\Models\CostCenter;
use App\Models\Gig; // Adicionado para manipulação direta
use App\Models\GigCost;
use App\Models\Tag;
use App\Services\GigFinancialCalculatorService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Adicionado para usar o App::make()
// O GigFinancialCalculatorService não é chamado diretamente pelo controller para salvar,
// pois o GigObserver já faz esse papel. Mas pode ser usado para buscar dados para a view, se necessário.
use Illuminate\View\View;

class GigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        // Lógica do Index (já revisada anteriormente e parece OK)
        // (mantém a lógica de ordenação e filtros)
        $sortableColumns = [
            'gig_date', 'cache_value', 'currency', 'payment_status',
            'artist_payment_status', 'booker_payment_status', 'contract_status',
            'created_at', 'location_event_details', 'artist_name', 'booker_name',
        ];
        $sortBy = $request->input('sort_by', 'gig_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (! in_array($sortBy, $sortableColumns)) {
            $sortBy = 'gig_date';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

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
        // ... (outros filtros como payment_status, artist_id, booker_id, datas, currency) ...
        if ($request->filled('payment_status')) {
            $query->where('gigs.payment_status', $request->input('payment_status'));
        }
        if ($request->filled('artist_id')) {
            $query->where('gigs.artist_id', $request->input('artist_id'));
        }
        if ($request->filled('booker_id')) {
            if ($request->input('booker_id') === 'sem_booker') {
                $query->whereNull('gigs.booker_id');
            } else {
                $query->where('gigs.booker_id', $request->input('booker_id'));
            }
        }
        if ($request->filled('start_date')) {
            $query->where('gigs.gig_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('gigs.gig_date', '<=', $request->input('end_date'));
        }
        if ($request->filled('currency') && $request->input('currency') !== 'all') {
            $query->where('gigs.currency', $request->input('currency'));
        }
        if ($request->filled('artist_payment_status')) {
            $query->where('gigs.artist_payment_status', $request->input('artist_payment_status'));
        }

        if ($request->filled('booker_payment_status')) {
            $query->where('gigs.booker_payment_status', $request->input('booker_payment_status'));
        }

        $orderByColumn = match ($sortBy) {
            'artist_name' => 'artists.name',
            'booker_name' => 'bookers.name',
            default => 'gigs.'.$sortBy,
        };
        $query->orderBy($orderByColumn, $sortDirection);

        $gigs = $query->paginate(25)->withQueryString();
        $artistsData = Artist::orderBy('name')->pluck('name', 'id');
        $bookersData = Booker::orderBy('name')->pluck('name', 'id');
        $currencies = DB::table('gigs')->select('currency')->distinct()->orderBy('currency')->pluck('currency');

        return view('gigs.index', [
            'gigs' => $gigs,
            'artists' => $artistsData,
            'bookers' => $bookersData,
            'currencies' => $currencies,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGigRequest $request): RedirectResponse
    {
        $validatedGigData = $request->safe()->except('expenses', 'tags', 'backParams'); // Pega dados validados da Gig
        $expensesData = $request->validated('expenses', []); // Pega dados validados das despesas
        $tagsIds = $request->validated('tags', []); // Pega IDs das tags
        $backParams = $request->input('backParams', []); // Pega os parâmetros de volta

        DB::beginTransaction();
        try {
            // O GigObserver (via método saving) cuidará de calcular e setar
            // agency_commission_value, booker_commission_value, liquid_commission_value
            // com base nos tipos e taxas/valores informados no $validatedGigData.
            $gig = Gig::create($validatedGigData);
            Log::info("[GigController@store] Gig ID: {$gig->id} criada. Processando despesas e tags...");

            // Sincronizar Tags
            if (! empty($tagsIds)) {
                $gig->tags()->sync($tagsIds);
                Log::info("[GigController@store] Tags sincronizadas para Gig ID: {$gig->id}.");
            }

            // Criar Despesas (GigCosts)
            if (! empty($expensesData)) {
                foreach ($expensesData as $expenseItem) {
                    if (isset($expenseItem['_deleted']) && $expenseItem['_deleted']) {
                        continue; // Pular despesas marcadas para exclusão no formulário de criação (não deveria acontecer)
                    }
                    $gig->gigCosts()()->create([
                        'cost_center_id' => $expenseItem['cost_center_id'],
                        'description' => $expenseItem['description'] ?? null,
                        'value' => $expenseItem['value'],
                        'currency' => $expenseItem['currency'] ?? 'BRL',
                        'expense_date' => $expenseItem['expense_date'] ?? null,
                        'notes' => $expenseItem['notes'] ?? null,
                        'is_confirmed' => $expenseItem['is_confirmed'] ?? false, // Default false
                        'is_invoice' => $expenseItem['is_invoice'] ?? false,   // Default false
                    ]);
                }
                Log::info("[GigController@store] Despesas processadas para Gig ID: {$gig->id}.");
            }

            DB::commit();
            Log::info("[GigController@store] Transação commitada para Gig ID: {$gig->id}.");

            // Se a Gig foi salva e teve despesas, o GigCostObserver pode ter chamado $gig->save() novamente,
            // o que é ok, pois o GigObserver::saving() é idempotente no sentido de recálculo.
            return redirect()->route('gigs.show', ['gig' => $gig] + $backParams)->with('success', '🎉 Gig criada com sucesso!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[GigController@store] Erro ao salvar Gig: '.$e->getMessage(), ['exception' => $e, 'data' => $request->all()]);

            return back()->withInput()->with('error', '❌ Ops! Erro ao criar a gig. Verifique os dados e tente novamente.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Gig $gig, Request $request): View
    {
        // Salva parâmetros da URL da index na sessão para o botão "Voltar"
        if ($request->hasAny(['search', 'payment_status', 'artist_id', 'booker_id', 'start_date', 'end_date', 'currency', 'sort_by', 'sort_direction', 'page'])) {
            $request->session()->put('gig_index_url_params', $request->query());
        }
        $backUrlParams = $request->session()->get('gig_index_url_params', []);

        // Carrega todos os relacionamentos necessários para a view de uma vez (Eager Loading)
        $gig->loadMissing(['artist', 'booker', 'payments' => fn ($q) => $q->orderBy('due_date', 'asc')->orderBy('id', 'asc'), 'settlement', 'tags', 'gigCosts.costCenter', 'gigCosts.confirmer']);

        // Instancia o service para fazer TODOS os cálculos
        $financialCalculator = App::make(GigFinancialCalculatorService::class);

        // Prepara um array com todos os dados financeiros necessários para a view
        $financialData = [
            // Dados do Resumo Financeiro (a refatoração principal)
            'totalReceivedInOriginalCurrency' => $financialCalculator->calculateTotalReceivedInOriginalCurrency($gig),
            'pendingBalanceInOriginalCurrency' => $financialCalculator->calculatePendingBalanceInOriginalCurrency($gig),

            // Dados para a seção de Acertos e NF (já calculados pelo service)
            'calculatedGrossCashBrl' => $financialCalculator->calculateGrossCashBrl($gig),
            'calculatedAgencyGrossCommissionBrl' => $financialCalculator->calculateAgencyGrossCommissionBrl($gig),
            'calculatedArtistNetPayoutBrl' => $financialCalculator->calculateArtistNetPayoutBrl($gig),
            'calculatedBookerCommissionBrl' => $financialCalculator->calculateBookerCommissionBrl($gig),
            'calculatedAgencyNetCommissionBrl' => $financialCalculator->calculateAgencyNetCommissionBrl($gig),
            'calculatedTotalConfirmedExpensesBrl' => $financialCalculator->calculateTotalConfirmedExpensesBrl($gig),
            'calculatedTotalReimbursableExpensesBrl' => $financialCalculator->calculateTotalReimbursableExpensesBrl($gig),
            'calculatedArtistInvoiceValueBrl' => $financialCalculator->calculateArtistInvoiceValueBrl($gig),
        ];

        // Logs de Atividade
        $activityLogs = ActivityLog::where('subject_type', Gig::class)
            ->where('subject_id', $gig->id)
            ->latest()
            ->paginate(10, ['*'], 'logs_page')
            ->withQueryString();

        // $costCenters = CostCenter::orderBy('name')->get();
        $costCenters = CostCenter::orderBy('name')->get()->mapWithKeys(function ($center) {
            // A chave é o ID do centro, e o valor é o nome traduzido.
            return [$center->id => __('cost_centers.'.$center->name)];
        });

        return view('gigs.show', [
            'gig' => $gig,
            'financialData' => $financialData, // Passa o array com todos os dados financeiros
            'activityLogs' => $activityLogs,
            'backUrlParams' => $backUrlParams,
            'costCenters' => $costCenters,
        ]);
    }

    public function create(Request $request): View
    {
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->select('id', 'name', 'default_commission_rate')->get();
        $bookersForSelect = $bookers->pluck('name', 'id');
        $bookersData = $bookers->keyBy('id')->toArray();
        $tags = Tag::orderBy('type')->orderBy('name')->get()->groupBy('type');
        $costCenters = CostCenter::orderBy('name')->pluck('name', 'id');
        $backUrlParams = $request->session()->get('gig_index_url_params', []);
        $gig = new Gig; // Para o formulário

        $expensesDataForView = old('expenses', []);

        // Valores iniciais para comissões (para um novo Gig)
        $initialCommissionData = [
            'agency_type' => old('agency_commission_type', 'percent'),
            'agency_input_value' => old('agency_commission_value', 20.00), // Default taxa 20%
            'booker_type' => old('booker_commission_type', 'percent'),
            'booker_input_value' => old('booker_commission_value', 5.00),   // Default taxa 5%
            'cache_value' => old('cache_value', 0),
        ];

        return view('gigs.create', compact(
            'gig',
            'artists',
            'bookers',
            'bookersForSelect',
            'bookersData',
            'tags',
            'costCenters',
            'expensesDataForView',
            'initialCommissionData', // <<-- ADICIONADO
            'backUrlParams'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gig $gig, Request $request): View
    {
        $artists = Artist::orderBy('name')->pluck('name', 'id');
        $bookers = Booker::orderBy('name')->select('id', 'name', 'default_commission_rate')->get();
        $bookersForSelect = $bookers->pluck('name', 'id');
        $bookersData = $bookers->keyBy('id')->toArray();
        $tags = Tag::orderBy('type')->orderBy('name')->get()->groupBy('type');
        $selectedTags = $gig->tags()->pluck('id')->toArray();
        $costCenters = CostCenter::orderBy('name')->pluck('name', 'id')->map(function ($name, $id) {
            return __('cost_centers.'.$name);
        });
        $backUrlParams = $request->session()->get('gig_index_url_params', []);

        $gig->load('gigCosts');

        // Preservar dados originais das despesas em caso de erro de validação
        $oldExpenses = old('expenses');

        // Priorizar dados old() se existirem e tiverem conteúdo válido, caso contrário usar dados do banco
        if ($oldExpenses !== null && is_array($oldExpenses) && count($oldExpenses) > 0) {
            // Usar dados old() quando disponíveis (erro de validação com dados preenchidos)
            $expensesDataForView = $oldExpenses;
        } elseif ($gig->exists && $gig->gigCosts()->isNotEmpty()) {
            // Usar dados originais do banco quando old() está vazio ou não existe
            $expensesDataForView = $gig->gigCosts()->map(function ($cost) {
                return [
                    'id' => $cost->id,
                    'cost_center_id' => (string) ($cost->cost_center_id ?? ''),
                    'description' => $cost->description ?? '',
                    'value' => $cost->value ?? '',
                    'currency' => $cost->currency ?: 'BRL',
                    'expense_date' => optional($cost->expense_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
                    'notes' => $cost->notes ?? '',
                    'is_confirmed' => (bool) ($cost->is_confirmed ?? false),
                    'is_invoice' => (bool) ($cost->is_invoice ?? false),
                    '_deleted' => false,
                ];
            })->toArray();
        } else {
            // Fallback para array vazio quando não há dados old() nem despesas no banco
            $expensesDataForView = [];
        }

        // --- CORREÇÃO AQUI para initialCommissionData ---
        $agency_input_value_for_form = null;
        if (strtoupper($gig->agency_commission_type ?? '') === 'PERCENT') {
            // Se o tipo é PERCENT, o campo do formulário (agencyDisplayValue) deve ser preenchido com a TAXA
            $agency_input_value_for_form = $gig->agency_commission_rate;
        } else { // FIXED ou não definido
            // Se o tipo é FIXED, o campo do formulário deve ser preenchido com o VALOR FIXO BRL
            $agency_input_value_for_form = $gig->agency_commission_value;
        }

        $booker_input_value_for_form = null;
        if ($gig->booker_id) {
            if (strtoupper($gig->booker_commission_type ?? '') === 'PERCENT') {
                $booker_input_value_for_form = $gig->booker_commission_rate;
            } else { // FIXED ou não definido
                $booker_input_value_for_form = $gig->booker_commission_value;
            }
        }

        $initialCommissionData = [
            'agency_type' => old('agency_commission_type', $gig->agency_commission_type ?? 'percent'),
            'agency_input_value' => old('agency_commission_value', $agency_input_value_for_form),
            'booker_type' => old('booker_commission_type', $gig->booker_commission_type ?? ($gig->booker_id ? 'percent' : '')), // Default para string vazia se sem booker e tipo
            'booker_input_value' => old('booker_commission_value', $booker_input_value_for_form),
            'cache_value' => old('cache_value', $gig->cache_value ?? 0),
        ];
        // --- FIM DA CORREÇÃO ---

        Log::debug("[GigController@edit] InitialCommissionData para Gig ID {$gig->id}:", $initialCommissionData);

        return view('gigs.edit', compact(
            'gig',
            'artists',
            'bookers',
            'bookersForSelect',
            'bookersData',
            'tags',
            'selectedTags',
            'costCenters',
            'expensesDataForView',
            'initialCommissionData', // <<-- ADICIONADO
            'backUrlParams'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGigRequest $request, Gig $gig): RedirectResponse
    {
        $validatedGigData = $request->safe()->except('expenses', 'tags', 'backParams');
        $expensesData = $request->validated('expenses', []);
        $tagsIds = $request->validated('tags', []);
        $backParams = $request->input('backParams', []);

        DB::beginTransaction();
        try {
            // O GigObserver (via método saving) cuidará de recalcular e setar
            // agency_commission_value, booker_commission_value, liquid_commission_value
            $gig->update($validatedGigData);
            Log::info("[GigController@update] Dados da Gig ID: {$gig->id} atualizados. Processando despesas e tags...");

            // Sincronizar Tags
            $gig->tags()->sync($tagsIds); // sync([]) remove todas as tags se $tagsIds for vazio
            Log::info("[GigController@update] Tags sincronizadas para Gig ID: {$gig->id}.");

            // Sincronizar Despesas (GigCosts) - Lógica mais robusta
            $existingCostIds = $gig->gigCosts()()->pluck('id')->all();
            $formCostIds = [];

            if (! empty($expensesData)) {
                foreach ($expensesData as $expenseItem) {
                    $isDeleted = isset($expenseItem['_deleted']) && $expenseItem['_deleted'] === true; // Verifica se está marcado para exclusão
                    $costId = $expenseItem['id'] ?? null;

                    // Se a despesa está marcada como deletada e tem ID, deletá-la imediatamente
                    if ($isDeleted && $costId) {
                        $cost = GigCost::find($costId);
                        if ($cost && $cost->gig_id === $gig->id) { // Segurança
                            $cost->delete(); // Soft delete
                            Log::info("[GigController@update] Despesa ID: {$costId} marcada como deletada e removida da Gig ID: {$gig->id}.");
                        }

                        continue; // Pula para a próxima despesa, não adiciona ao $formCostIds
                    }

                    $dataToUpsert = [
                        'cost_center_id' => $expenseItem['cost_center_id'],
                        'description' => $expenseItem['description'] ?? null,
                        'value' => $expenseItem['value'],
                        'currency' => $expenseItem['currency'] ?? 'BRL',
                        'expense_date' => $expenseItem['expense_date'] ?? null,
                        'notes' => $expenseItem['notes'] ?? null,
                        'is_confirmed' => $expenseItem['is_confirmed'] ?? false,
                        'is_invoice' => $expenseItem['is_invoice'] ?? false,
                    ];

                    if ($costId && ! $isDeleted) { // Atualizar existente
                        $cost = GigCost::find($costId);
                        if ($cost && $cost->gig_id === $gig->id) { // Segurança
                            $cost->update($dataToUpsert);
                            $formCostIds[] = $costId;
                        }
                    } elseif (! $costId && ! $isDeleted) { // Criar novo
                        $newCost = $gig->gigCosts()()->create($dataToUpsert);
                        $formCostIds[] = $newCost->id;
                    }
                }
            }

            // REMOVIDO: Lógica de soft delete automático de despesas existentes
            // As despesas existentes são preservadas e só são removidas quando explicitamente marcadas como _deleted
            Log::info("[GigController@update] Despesas existentes preservadas. Apenas despesas marcadas como _deleted foram removidas para Gig ID: {$gig->id}.");
            Log::info("[GigController@update] Despesas sincronizadas para Gig ID: {$gig->id}.");

            DB::commit();
            Log::info("[GigController@update] Transação commitada para Gig ID: {$gig->id}.");

            return redirect()->route('gigs.show', ['gig' => $gig] + $backParams)->with('success', '🎉 Gig atualizada com sucesso!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[GigController@update] Erro ao atualizar Gig ID {$gig->id}: ".$e->getMessage(), ['exception' => $e, 'data' => $request->all()]);

            return back()->withInput()->with('error', '❌ Ops! Erro ao atualizar a gig. Verifique os dados e tente novamente.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gig $gig, Request $request): RedirectResponse
    {
        // (lógica de exclusão e redirect com backParams)
        try {
            DB::transaction(function () use ($gig) {
                // Soft delete pagamentos e custos relacionados PRIMEIRO se houver restrições de FK
                // ou se desejar manter a integridade para restauração.
                // Se cascadeOnDelete estiver configurado nas migrations, isso pode ser automático.
                // $gig->payments()->delete(); // Se usar softDeletes no Payment
                // $gig->gigCosts()()->delete();    // Se usar softDeletes no GigCost
                $gig->delete(); // Soft delete da Gig
            });

            $backParams = $request->input('backParams', []); // Recupera dos inputs hidden

            return redirect()->route('gigs.index', $backParams)->with('success', '🗑️ Gig excluída com sucesso!');
        } catch (Exception $e) {
            Log::error('Erro ao excluir Gig: '.$e->getMessage(), ['exception' => $e, 'gig_id' => $gig->id]);

            return back()->with('error', '❌ Erro ao excluir a Gig.');
        }
    }

    /**
     * Display the form/page for requesting the artist's NF.
     */
    public function showRequestNfForm(Gig $gig, Request $request): View
    {
        $gig->loadMissing(['artist', 'gigCosts.costCenter']);

        // Instanciar o service para fazer os cálculos
        $financialCalculator = App::make(GigFinancialCalculatorService::class);

        // Parâmetros para o botão "Voltar"
        $backUrlParams = $request->session()->get('gig_show_url_params', []);
        if (empty(array_filter($backUrlParams, fn ($k) => $k !== 'gig', ARRAY_FILTER_USE_KEY))) {
            $backUrlParams = ['gig' => $gig->id] + $request->session()->get('gig_index_url_params', []);
        }

        // Preparar todas as variáveis necessárias para a view
        $gigCacheValueBrl = $gig->cache_value_brl; // Valor original do contrato em BRL
        $totalConfirmedExpensesBrl = $financialCalculator->calculateTotalConfirmedExpensesBrl($gig);
        $calculatedGrossCashBrl = $financialCalculator->calculateGrossCashBrl($gig); // <<-- ESTA É A CHAVE
        $calculatedAgencyGrossCommissionBrl = $financialCalculator->calculateAgencyGrossCommissionBrl($gig);
        $artistNetPayoutBeforeReimbursement = $financialCalculator->calculateArtistNetPayoutBrl($gig);
        $totalReimbursableExpensesBrl = $financialCalculator->calculateTotalReimbursableExpensesBrl($gig);
        $finalArtistInvoiceValueBrl = $financialCalculator->calculateArtistInvoiceValueBrl($gig);

        return view('gigs.request-nf', compact(
            'gig',
            'gigCacheValueBrl',
            'totalConfirmedExpensesBrl',
            'calculatedGrossCashBrl', // <<-- Garantir que está aqui
            'calculatedAgencyGrossCommissionBrl',
            'artistNetPayoutBeforeReimbursement',
            'totalReimbursableExpensesBrl',
            'finalArtistInvoiceValueBrl',
            'backUrlParams'
        ));
    }

    /**
     * Exibe uma página de depuração com todos os cálculos financeiros para uma Gig.
     */
    public function debugFinancials(Gig $gig): View
    {
        $gig->loadMissing(['gigCosts.costCenter', 'artist', 'booker', 'payments']);
        $calculator = App::make(GigFinancialCalculatorService::class);
        $cacheBrlDetails = $gig->cacheValueBrlDetails; // Chama o accessor do modelo Gig

        // Array de cálculos agora inclui os de pagamento
        $calculations = [
            // Cálculos de Pagamento
            'calculateTotalReceivedInOriginalCurrency' => $calculator->calculateTotalReceivedInOriginalCurrency($gig),
            'calculateTotalReceivableInOriginalCurrency' => $calculator->calculateTotalReceivableInOriginalCurrency($gig),
            'calculatePendingBalanceInOriginalCurrency' => $calculator->calculatePendingBalanceInOriginalCurrency($gig),
            'divider_1' => null, // Apenas para criar um separador visual na tabela
            // Cálculos de Despesas e Base de Comissão
            'calculateTotalConfirmedExpensesBrl' => $calculator->calculateTotalConfirmedExpensesBrl($gig),
            'calculateTotalReimbursableExpensesBrl' => $calculator->calculateTotalReimbursableExpensesBrl($gig),
            'calculateGrossCashBrl' => $calculator->calculateGrossCashBrl($gig),
            'divider_2' => null, // Outro separador
            // Cálculos de Comissões e Acertos
            'calculateAgencyGrossCommissionBrl' => $calculator->calculateAgencyGrossCommissionBrl($gig),
            'calculateBookerCommissionBrl' => $calculator->calculateBookerCommissionBrl($gig),
            'calculateAgencyNetCommissionBrl' => $calculator->calculateAgencyNetCommissionBrl($gig),
            'calculateArtistNetPayoutBrl' => $calculator->calculateArtistNetPayoutBrl($gig),
            'calculateArtistInvoiceValueBrl' => $calculator->calculateArtistInvoiceValueBrl($gig),
            // Outros detalhes
            'calculateTotalReceivedInOriginalCurrency' => $calculator->calculateTotalReceivedInOriginalCurrency($gig),
            'calculateTotalReceivableInOriginalCurrency' => $calculator->calculateTotalReceivableInOriginalCurrency($gig),

        ];

        return view('gigs.debug.financials', [
            'gig' => $gig,
            'calculations' => $calculations,
            'cacheBrlDetails' => $cacheBrlDetails,
        ]);
    }
}
