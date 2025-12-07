<?php

namespace App\Http\Controllers;

use App\Models\GigCost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseReimbursementController extends Controller
{
    /**
     * Lista despesas reembolsáveis com métricas por estágio.
     */
    public function index(Request $request): View
    {
        // Métricas por estágio
        $stageCounts = GigCost::reimbursable()
            ->whereNotNull('reimbursement_stage')
            ->select('reimbursement_stage', DB::raw('COUNT(*) as count'), DB::raw('SUM(value) as total_value'))
            ->groupBy('reimbursement_stage')
            ->get()
            ->keyBy('reimbursement_stage');

        $metrics = [
            'aguardando_comprovante' => [
                'count' => $stageCounts->get('aguardando_comprovante')?->count ?? 0,
                'value' => $stageCounts->get('aguardando_comprovante')?->total_value ?? 0,
            ],
            'comprovante_recebido' => [
                'count' => $stageCounts->get('comprovante_recebido')?->count ?? 0,
                'value' => $stageCounts->get('comprovante_recebido')?->total_value ?? 0,
            ],
            'conferido' => [
                'count' => $stageCounts->get('conferido')?->count ?? 0,
                'value' => $stageCounts->get('conferido')?->total_value ?? 0,
            ],
            'reembolsado' => [
                'count' => $stageCounts->get('reembolsado')?->count ?? 0,
                'value' => $stageCounts->get('reembolsado')?->total_value ?? 0,
            ],
        ];

        // Query base de despesas reembolsáveis
        $query = GigCost::reimbursable()
            ->with(['gig.artist', 'costCenter'])
            ->whereNotNull('reimbursement_stage');

        // Filtro por estágio
        if ($request->filled('stage') && $request->stage !== 'all') {
            $query->reimbursementStage($request->stage);
        }

        // Filtro por artista
        if ($request->filled('artist_id')) {
            $query->whereHas('gig', fn($q) => $q->where('artist_id', $request->artist_id));
        }

        // Filtro por período
        if ($request->filled('period')) {
            [$start, $end] = $this->parsePeriod($request->period);
            $query->whereBetween('expense_date', [$start, $end]);
        }

        // Busca textual
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('gig', fn($gq) => $gq->where('location_event_details', 'like', "%{$search}%"))
                  ->orWhereHas('costCenter', fn($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(20);

        // Lista de artistas para filtro
        $artists = \App\Models\Artist::orderBy('name')->pluck('name', 'id');

        return view('expenses.reimbursements.index', compact('expenses', 'metrics', 'artists'));
    }

    /**
     * Marca comprovante como recebido e faz upload do arquivo.
     */
    public function receiveProof(Request $request, GigCost $cost): RedirectResponse
    {
        // Validar que é uma despesa reembolsável no estágio correto
        if (!$cost->is_invoice) {
            return back()->with('error', 'Esta despesa não é reembolsável.');
        }

        if ($cost->reimbursement_stage !== 'aguardando_comprovante') {
            return back()->with('error', 'Esta despesa não está aguardando comprovante.');
        }

        $validated = $request->validate([
            'proof_type' => ['required', Rule::in(array_keys(GigCost::REIMBURSEMENT_PROOF_TYPES))],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store(
                "reimbursements/{$cost->gig_id}",
                'public'
            );
        }

        $cost->update([
            'reimbursement_stage' => 'comprovante_recebido',
            'reimbursement_proof_type' => $validated['proof_type'],
            'reimbursement_proof_file' => $proofPath,
            'reimbursement_proof_received_at' => now(),
            'reimbursement_notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Comprovante registrado com sucesso!');
    }

    /**
     * Confirma o valor da despesa reembolsável.
     */
    public function confirmReimbursement(Request $request, GigCost $cost): RedirectResponse
    {
        if ($cost->reimbursement_stage !== 'comprovante_recebido') {
            return back()->with('error', 'Esta despesa não está pronta para conferência.');
        }

        $validated = $request->validate([
            'confirmed_value' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $cost->update([
            'reimbursement_stage' => 'conferido',
            'reimbursement_value_confirmed' => $validated['confirmed_value'],
            'reimbursement_confirmed_at' => now(),
            'reimbursement_confirmed_by' => auth()->id(),
            'reimbursement_notes' => $validated['notes'] ?? $cost->reimbursement_notes,
        ]);

        return back()->with('success', 'Valor conferido com sucesso!');
    }

    /**
     * Marca a despesa como reembolsada.
     */
    public function markReimbursed(Request $request, GigCost $cost): RedirectResponse
    {
        if (!in_array($cost->reimbursement_stage, ['conferido', 'comprovante_recebido'])) {
            return back()->with('error', 'Esta despesa não está pronta para ser marcada como reembolsada.');
        }

        // Se estiver em comprovante_recebido mas sem conferência, usa o valor original
        $confirmedValue = $cost->reimbursement_value_confirmed ?? $cost->value;

        $cost->update([
            'reimbursement_stage' => 'reembolsado',
            'reimbursement_value_confirmed' => $confirmedValue,
            'reimbursement_confirmed_at' => $cost->reimbursement_confirmed_at ?? now(),
            'reimbursement_confirmed_by' => $cost->reimbursement_confirmed_by ?? auth()->id(),
        ]);

        return back()->with('success', 'Despesa marcada como reembolsada!');
    }

    /**
     * Reverte o estágio da despesa (volta para o anterior).
     */
    public function revertStage(Request $request, GigCost $cost): RedirectResponse
    {
        $revertMap = [
            'comprovante_recebido' => [
                'target' => 'aguardando_comprovante',
                'clear' => ['reimbursement_proof_type', 'reimbursement_proof_file', 'reimbursement_proof_received_at'],
            ],
            'conferido' => [
                'target' => 'comprovante_recebido',
                'clear' => ['reimbursement_value_confirmed', 'reimbursement_confirmed_at', 'reimbursement_confirmed_by'],
            ],
            'reembolsado' => [
                'target' => 'conferido',
                'clear' => [],
            ],
        ];

        if (!isset($revertMap[$cost->reimbursement_stage])) {
            return back()->with('error', 'Não é possível reverter este estágio.');
        }

        $action = $revertMap[$cost->reimbursement_stage];
        $updates = ['reimbursement_stage' => $action['target']];
        
        foreach ($action['clear'] as $field) {
            $updates[$field] = null;
        }

        // Se tem arquivo e está revertendo de comprovante_recebido, deleta o arquivo
        if ($cost->reimbursement_stage === 'comprovante_recebido' && $cost->reimbursement_proof_file) {
            Storage::disk('public')->delete($cost->reimbursement_proof_file);
        }

        $cost->update($updates);

        return back()->with('success', 'Estágio revertido com sucesso!');
    }

    /**
     * Parse período para datas.
     */
    private function parsePeriod(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->subMonths(3), now()],
        };
    }
}
