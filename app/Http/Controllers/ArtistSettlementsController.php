<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Gig;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            ->with(['artist', 'booker', 'settlement'])
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

        // Filtro por estágio do workflow (novo)
        if ($request->filled('stage')) {
            $stage = $request->input('stage');
            $query->whereHas('settlement', fn ($q) => $q->where('settlement_stage', $stage));
        }

        // Filtro legado por status (mantido para compatibilidade)
        if ($request->filled('status')) {
            $query->where('artist_payment_status', $request->input('status'));
        }

        // Métricas por estágio para os cards
        $stageMetrics = $this->calculateStageMetrics();

        // Valores totais pendentes (todos que não estão pagos)
        $pendingTotal = Gig::query()
            ->whereNotNull('artist_id')
            ->where('gig_date', '<=', now())
            ->where('artist_payment_status', 'pendente')
            ->get()
            ->sum('calculated_artist_net_payout_brl');

        // Ordenação e paginação
        $gigs = $query->orderBy('gig_date', 'desc')->paginate(25)->withQueryString();

        return view('artists.settlements.index', compact(
            'gigs',
            'artists',
            'stageMetrics',
            'pendingTotal'
        ));
    }

    /**
     * Calcula métricas por estágio do workflow.
     */
    private function calculateStageMetrics(): array
    {
        $baseQuery = Gig::query()
            ->whereNotNull('artist_id')
            ->where('gig_date', '<=', now());

        // Contar gigs sem settlement (aguardando conferência)
        $withoutSettlement = (clone $baseQuery)
            ->whereDoesntHave('settlement')
            ->count();

        // Contar por estágio das que têm settlement
        $stageCounts = Settlement::query()
            ->whereHas('gig', fn ($q) => $q->where('gig_date', '<=', now()))
            ->selectRaw('settlement_stage, COUNT(*) as count')
            ->groupBy('settlement_stage')
            ->pluck('count', 'settlement_stage')
            ->toArray();

        return [
            Settlement::STAGE_AGUARDANDO_CONFERENCIA => 
                $withoutSettlement + ($stageCounts[Settlement::STAGE_AGUARDANDO_CONFERENCIA] ?? 0),
            Settlement::STAGE_FECHAMENTO_ENVIADO => 
                $stageCounts[Settlement::STAGE_FECHAMENTO_ENVIADO] ?? 0,
            Settlement::STAGE_DOCUMENTACAO_RECEBIDA => 
                $stageCounts[Settlement::STAGE_DOCUMENTACAO_RECEBIDA] ?? 0,
            Settlement::STAGE_PAGO => 
                $stageCounts[Settlement::STAGE_PAGO] ?? 0,
        ];
    }

    /**
     * Envia o fechamento para o artista (aguardando → enviado).
     */
    public function sendSettlement(Request $request, Gig $gig)
    {
        $request->validate([
            'communication_notes' => 'nullable|string|max:1000',
            'redirect_to' => 'nullable|string|in:gig,index,show',
        ]);

        DB::transaction(function () use ($request, $gig) {
            // Cria ou atualiza o settlement
            $settlement = $gig->settlement()->firstOrCreate(
                ['gig_id' => $gig->id],
                [
                    'settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
                    'settlement_date' => now()->toDateString(),
                ]
            );

            // Verifica se pode avançar
            if (! $settlement->canSendSettlement()) {
                abort(422, 'Este fechamento não pode ser enviado no estágio atual.');
            }

            // Avança o estágio
            $settlement->update([
                'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
                'settlement_sent_at' => now(),
                'communication_notes' => $request->input('communication_notes'),
            ]);
        });

        // Redireciona de volta para a página de origem
        $redirectTo = $request->input('redirect_to');
        
        if ($redirectTo === 'gig') {
            return redirect()
                ->route('gigs.request-nf', $gig)
                ->with('success', 'Fechamento enviado com sucesso!');
        }

        if ($redirectTo === 'show') {
            return redirect()
                ->route('gigs.show', $gig)
                ->with('success', 'Fechamento enviado com sucesso!');
        }

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', 'Fechamento enviado com sucesso!');
    }

    /**
     * Registra recebimento de documentação (enviado → documentação_recebida).
     */
    public function markDocumentationReceived(Request $request, Gig $gig)
    {
        $request->validate([
            'documentation_type' => 'required|in:nf,recibo',
            'documentation_number' => 'nullable|string|max:100',
            'documentation_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'communication_notes' => 'nullable|string|max:1000',
            'redirect_to' => 'nullable|string|in:gig,index,show',
        ]);

        $settlement = $gig->settlement;

        if (! $settlement || ! $settlement->canReceiveDocumentation()) {
            $redirectTo = $request->input('redirect_to');
            $errorRedirect = match($redirectTo) {
                'gig' => redirect()->route('gigs.request-nf', $gig),
                'show' => redirect()->route('gigs.show', $gig),
                default => redirect()->route('artists.settlements.index', $request->query()),
            };
            
            return $errorRedirect->with('error', 'Este fechamento não está no estágio correto para registrar documentação.');
        }

        DB::transaction(function () use ($request, $settlement) {
            $filePath = $settlement->documentation_file_path; // Manter arquivo existente se não enviar novo

            // Upload do arquivo se enviado
            if ($request->hasFile('documentation_file')) {
                $filePath = $request->file('documentation_file')->store(
                    'settlements/documents',
                    'public'
                );
            }

            // Atualiza o settlement
            $settlement->update([
                'settlement_stage' => Settlement::STAGE_DOCUMENTACAO_RECEBIDA,
                'documentation_received_at' => now(),
                'documentation_type' => $request->input('documentation_type'),
                'documentation_number' => $request->input('documentation_number'),
                'documentation_file_path' => $filePath,
                'communication_notes' => $request->input('communication_notes') ?: $settlement->communication_notes,
            ]);
        });

        // Redireciona de volta para a página de origem
        $redirectTo = $request->input('redirect_to');
        
        if ($redirectTo === 'gig') {
            return redirect()
                ->route('gigs.request-nf', $gig)
                ->with('success', 'Documentação registrada com sucesso!');
        }

        if ($redirectTo === 'show') {
            return redirect()
                ->route('gigs.show', $gig)
                ->with('success', 'Documentação registrada com sucesso!');
        }

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', 'Documentação registrada com sucesso!');
    }

    /**
     * Marca o pagamento do artista como pago (apenas de documentacao_recebida para pago).
     */
    public function settleArtist(Request $request, Gig $gig)
    {
        $request->validate([
            'payment_date' => 'required|date|before_or_equal:today',
            'redirect_to' => 'nullable|string|in:gig,index,show',
        ]);

        // Validar estágio - só permite pagar se estiver em documentacao_recebida
        $settlement = $gig->settlement;
        $currentStage = $settlement?->settlement_stage ?? Settlement::STAGE_AGUARDANDO_CONFERENCIA;

        if ($currentStage !== Settlement::STAGE_DOCUMENTACAO_RECEBIDA) {
            $stageLabels = [
                Settlement::STAGE_AGUARDANDO_CONFERENCIA => 'Aguardando Conferência',
                Settlement::STAGE_FECHAMENTO_ENVIADO => 'Ag. NF/Recibo',
                Settlement::STAGE_DOCUMENTACAO_RECEBIDA => 'Pronto p/ Pagar',
                Settlement::STAGE_PAGO => 'Pago',
            ];
            $currentLabel = $stageLabels[$currentStage] ?? $currentStage;

            return $this->redirectWithMessage(
                $request,
                $gig,
                'error',
                "Não é possível registrar pagamento. O fechamento está no estágio '{$currentLabel}'. É necessário seguir o fluxo: Enviar Fechamento → Registrar NF/Recibo → Registrar Pagamento."
            );
        }

        $paymentDate = $request->input('payment_date', now()->toDateString());

        DB::transaction(function () use ($gig, $settlement, $paymentDate) {
            $gig->update(['artist_payment_status' => 'pago']);

            // Atualiza o settlement com estágio 'pago'
            $settlement->update([
                'settlement_stage' => Settlement::STAGE_PAGO,
                'artist_payment_value' => $gig->calculated_artist_invoice_value_brl,
                'artist_payment_paid_at' => $paymentDate,
            ]);
        });

        // Redireciona de volta para a página de origem
        $redirectTo = $request->input('redirect_to');
        
        if ($redirectTo === 'gig') {
            return redirect()
                ->route('gigs.request-nf', $gig)
                ->with('success', 'Pagamento registrado com sucesso!');
        }

        if ($redirectTo === 'show') {
            return redirect()
                ->route('gigs.show', $gig)
                ->with('success', 'Pagamento registrado com sucesso!');
        }

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', 'Pagamento registrado com sucesso!');
    }

    /**
     * Reverte o estágio do settlement para o anterior.
     */
    public function revertStage(Request $request, Gig $gig)
    {
        $request->validate([
            'redirect_to' => 'nullable|string|in:gig,index,show',
        ]);

        $settlement = $gig->settlement;

        if (! $settlement) {
            return $this->redirectWithMessage($request, $gig, 'error', 'Nenhum fechamento encontrado para esta gig.');
        }

        $currentStage = $settlement->settlement_stage;

        // Define os estágios anteriores e o que deve ser limpo
        $revertMap = [
            Settlement::STAGE_FECHAMENTO_ENVIADO => [
                'new_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
                'clear' => ['settlement_sent_at', 'communication_notes'],
                'message' => 'Envio do fechamento revertido.',
            ],
            Settlement::STAGE_DOCUMENTACAO_RECEBIDA => [
                'new_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
                'clear' => ['documentation_received_at', 'documentation_type', 'documentation_number', 'documentation_file_path'],
                'message' => 'Recebimento de documentação revertido.',
            ],
            Settlement::STAGE_PAGO => [
                'new_stage' => Settlement::STAGE_DOCUMENTACAO_RECEBIDA,
                'clear' => ['artist_payment_paid_at', 'artist_payment_value'],
                'message' => 'Pagamento revertido.',
                'update_gig' => ['artist_payment_status' => 'pendente'],
            ],
        ];

        if (! isset($revertMap[$currentStage])) {
            return $this->redirectWithMessage($request, $gig, 'error', 'Este estágio não pode ser revertido.');
        }

        $revertInfo = $revertMap[$currentStage];

        DB::transaction(function () use ($settlement, $gig, $revertInfo) {
            // Limpa os campos do estágio atual
            $clearData = array_fill_keys($revertInfo['clear'], null);
            $clearData['settlement_stage'] = $revertInfo['new_stage'];

            $settlement->update($clearData);

            // Atualiza a gig se necessário (ex: reverter pagamento)
            if (isset($revertInfo['update_gig'])) {
                $gig->update($revertInfo['update_gig']);
            }
        });

        return $this->redirectWithMessage($request, $gig, 'success', $revertInfo['message']);
    }

    /**
     * Helper para redirecionar com mensagem.
     */
    private function redirectWithMessage(Request $request, Gig $gig, string $type, string $message)
    {
        $redirectTo = $request->input('redirect_to');

        if ($redirectTo === 'gig') {
            return redirect()
                ->route('gigs.request-nf', $gig)
                ->with($type, $message);
        }

        if ($redirectTo === 'show') {
            return redirect()
                ->route('gigs.show', $gig)
                ->with($type, $message);
        }

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with($type, $message);
    }

    /**
     * Processa o pagamento em massa de cachês de artistas.
     * Só permite pagar gigs que já passaram pelo envio de conferência.
     */
    public function settleBatch(Request $request)
    {
        $request->validate([
            'gig_ids' => 'required|array|min:1',
            'gig_ids.*' => 'exists:gigs,id',
            'payment_date' => 'nullable|date|before_or_equal:today',
        ]);

        $paymentDate = $request->input('payment_date', now()->toDateString());
        $settledCount = 0;
        $skippedCount = 0;
        $skippedGigs = [];

        DB::transaction(function () use ($request, $paymentDate, &$settledCount, &$skippedCount, &$skippedGigs) {
            $gigs = Gig::whereIn('id', $request->input('gig_ids'))
                ->with('settlement')
                ->where('artist_payment_status', '!=', 'pago')
                ->get();

            foreach ($gigs as $gig) {
                $currentStage = $gig->settlement?->settlement_stage ?? Settlement::STAGE_AGUARDANDO_CONFERENCIA;

                // Só permite pagar se já foi enviado para conferência
                // Pode estar em fechamento_enviado (pula NF) ou documentacao_recebida
                if ($currentStage === Settlement::STAGE_AGUARDANDO_CONFERENCIA) {
                    $skippedCount++;
                    $skippedGigs[] = $gig->artist?->name ?? "Gig #{$gig->id}";
                    continue;
                }

                $gig->update(['artist_payment_status' => 'pago']);

                // Atualiza o settlement com estágio 'pago'
                $gig->settlement()->updateOrCreate(
                    ['gig_id' => $gig->id],
                    [
                        'settlement_date' => $paymentDate,
                        'settlement_stage' => Settlement::STAGE_PAGO,
                        'artist_payment_value' => $gig->calculated_artist_invoice_value_brl,
                        'artist_payment_paid_at' => $paymentDate,
                    ]
                );

                $settledCount++;
            }
        });

        $message = "{$settledCount} fechamento(s) marcado(s) como pago(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} gig(s) ignorada(s) por ainda estarem aguardando conferência.";
        }

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with($settledCount > 0 ? 'success' : 'warning', $message);
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
                ->with('settlement')
                ->where('artist_payment_status', 'pago')
                ->get();

            foreach ($gigs as $gig) {
                $gig->update(['artist_payment_status' => 'pendente']);

                // Reverte o settlement para documentação recebida (se tiver doc) ou aguardando conferência
                if ($gig->settlement) {
                    $currentStage = $gig->settlement->settlement_stage;
                    
                    // Se está no estágio PAGO, reverte para documentacao_recebida
                    // Se não tem estágio definido (legado), verifica se tem documentação
                    if ($currentStage === Settlement::STAGE_PAGO) {
                        $previousStage = Settlement::STAGE_DOCUMENTACAO_RECEBIDA;
                    } elseif ($gig->settlement->documentation_received_at) {
                        $previousStage = Settlement::STAGE_DOCUMENTACAO_RECEBIDA;
                    } else {
                        $previousStage = Settlement::STAGE_AGUARDANDO_CONFERENCIA;
                    }

                    $gig->settlement->update([
                        'settlement_stage' => $previousStage,
                        'artist_payment_value' => null,
                        'artist_payment_paid_at' => null,
                    ]);
                }

                $unsettledCount++;
            }
        });

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', "{$unsettledCount} fechamento(s) revertido(s) para pendente.");
    }

    /**
     * Envia fechamentos em massa (aguardando → enviado).
     */
    public function sendBatch(Request $request)
    {
        $request->validate([
            'gig_ids' => 'required|array|min:1',
            'gig_ids.*' => 'exists:gigs,id',
        ]);

        $sentCount = 0;

        DB::transaction(function () use ($request, &$sentCount) {
            $gigs = Gig::whereIn('id', $request->input('gig_ids'))
                ->with('settlement')
                ->get();

            foreach ($gigs as $gig) {
                $settlement = $gig->settlement()->firstOrCreate(
                    ['gig_id' => $gig->id],
                    [
                        'settlement_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
                        'settlement_date' => now()->toDateString(),
                    ]
                );

                if ($settlement->canSendSettlement()) {
                    $settlement->update([
                        'settlement_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
                        'settlement_sent_at' => now(),
                    ]);
                    $sentCount++;
                }
            }
        });

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with('success', "{$sentCount} fechamento(s) enviado(s).");
    }

    /**
     * Reverte estágios em massa de forma inteligente.
     * Cada gig é revertida para o estágio anterior conforme sua posição atual.
     */
    public function revertBatch(Request $request)
    {
        $request->validate([
            'gig_ids' => 'required|array|min:1',
            'gig_ids.*' => 'exists:gigs,id',
        ]);

        $revertedCount = 0;
        $skippedCount = 0;

        // Define os estágios anteriores e o que deve ser limpo
        $revertMap = [
            Settlement::STAGE_FECHAMENTO_ENVIADO => [
                'new_stage' => Settlement::STAGE_AGUARDANDO_CONFERENCIA,
                'clear' => ['settlement_sent_at', 'communication_notes'],
            ],
            Settlement::STAGE_DOCUMENTACAO_RECEBIDA => [
                'new_stage' => Settlement::STAGE_FECHAMENTO_ENVIADO,
                'clear' => ['documentation_received_at', 'documentation_type', 'documentation_number', 'documentation_file_path'],
            ],
            Settlement::STAGE_PAGO => [
                'new_stage' => Settlement::STAGE_DOCUMENTACAO_RECEBIDA,
                'clear' => ['artist_payment_paid_at', 'artist_payment_value'],
                'update_gig' => ['artist_payment_status' => 'pendente'],
            ],
        ];

        DB::transaction(function () use ($request, $revertMap, &$revertedCount, &$skippedCount) {
            $gigs = Gig::whereIn('id', $request->input('gig_ids'))
                ->with('settlement')
                ->get();

            foreach ($gigs as $gig) {
                $settlement = $gig->settlement;

                if (! $settlement) {
                    $skippedCount++;
                    continue;
                }

                $currentStage = $settlement->settlement_stage;

                if (! isset($revertMap[$currentStage])) {
                    // Não tem estágio anterior (aguardando_conferencia)
                    $skippedCount++;
                    continue;
                }

                $revertInfo = $revertMap[$currentStage];

                // Limpa os campos do estágio atual
                $clearData = array_fill_keys($revertInfo['clear'], null);
                $clearData['settlement_stage'] = $revertInfo['new_stage'];

                $settlement->update($clearData);

                // Atualiza a gig se necessário
                if (isset($revertInfo['update_gig'])) {
                    $gig->update($revertInfo['update_gig']);
                }

                $revertedCount++;
            }
        });

        $message = "{$revertedCount} fechamento(s) revertido(s) para o estágio anterior.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} gig(s) ignorada(s) (sem fechamento ou já no estágio inicial).";
        }

        return redirect()
            ->route('artists.settlements.index', $request->query())
            ->with($revertedCount > 0 ? 'success' : 'warning', $message);
    }
}
