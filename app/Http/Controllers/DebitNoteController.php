<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use App\Models\Gig;
use App\Models\Settlement;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
    /**
     * Generate a new debit note for a gig.
     * This creates the note with auto-numbering and redirects to view.
     */
    public function generate(Gig $gig)
    {
        // Validate service taker
        if (! $gig->serviceTaker) {
            return back()->withErrors([
                'error' => 'Esta gig não possui um tomador de serviço vinculado.',
            ]);
        }

        // Validate stage = pago
        $settlement = $gig->settlement;
        $currentStage = $settlement?->settlement_stage ?? Settlement::STAGE_AGUARDANDO_CONFERENCIA;

        if ($currentStage !== Settlement::STAGE_PAGO) {
            return back()->withErrors([
                'error' => 'A nota de débito só pode ser gerada após o pagamento.',
            ]);
        }

        // Create note (cancels any existing active note)
        $debitNote = DebitNote::createForGig($gig);

        return redirect()->route('debit-notes.show', $gig)
            ->with('success', "Nota de Débito {$debitNote->number} gerada com sucesso.");
    }

    /**
     * Display the debit note for a gig (using saved data).
     */
    public function show(Gig $gig)
    {
        // Must have a generated note
        $debitNote = $gig->debitNote;

        if (! $debitNote) {
            return back()->withErrors([
                'error' => 'Nota de débito não encontrada. Gere a nota primeiro.',
            ]);
        }

        // Load relationships for template
        $gig->load([
            'serviceTaker',
            'artist',
            'debitNote',
            'gigCosts' => function ($query) {
                $query->where('is_confirmed', true)->with('costCenter');
            },
        ]);

        return view('debit-notes.show', [
            'gig' => $gig,
            'debitNote' => $debitNote,
            'serviceTaker' => $gig->serviceTaker,
            'honorarios' => $debitNote->honorarios,
            'despesas' => $debitNote->despesas,
            'despesasItens' => $gig->gigCosts->where('is_invoice', true),
            'settlement' => $gig->settlement,
        ]);
    }

    /**
     * Cancel a debit note with justification.
     */
    public function cancel(Gig $gig, Request $request)
    {
        $request->validate([
            'cancel_reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancel_reason.required' => 'A justificativa do cancelamento é obrigatória.',
            'cancel_reason.min' => 'A justificativa deve ter pelo menos 5 caracteres.',
        ]);

        $debitNote = $gig->debitNote;

        if (! $debitNote) {
            return back()->withErrors([
                'error' => 'Nenhuma nota de débito ativa encontrada.',
            ]);
        }

        $debitNote->cancel($request->input('cancel_reason'));

        return back()->with('success', "Nota {$debitNote->number} cancelada com sucesso.");
    }

    /**
     * Activate a cancelled debit note (from history).
     */
    public function activate(DebitNote $debitNote)
    {
        if ($debitNote->isActive()) {
            return back()->withErrors([
                'error' => 'Esta nota já está ativa.',
            ]);
        }

        $debitNote->activate();

        return back()->with('success', "Nota {$debitNote->number} ativada com sucesso.");
    }

    /**
     * Get debit note history for a gig (JSON for modal).
     */
    public function history(Gig $gig)
    {
        $notes = $gig->debitNotes()->get()->map(function ($note) {
            return [
                'id' => $note->id,
                'number' => $note->number,
                'issued_at' => $note->issued_at->format('d/m/Y H:i'),
                'total' => number_format($note->total, 2, ',', '.'),
                'is_active' => $note->isActive(),
                'cancelled_at' => $note->cancelled_at?->format('d/m/Y H:i'),
                'cancel_reason' => $note->cancel_reason,
            ];
        });

        return response()->json([
            'notes' => $notes,
            'has_active' => $notes->contains('is_active', true),
        ]);
    }
}
