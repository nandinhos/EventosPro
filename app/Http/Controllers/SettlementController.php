<?php

namespace App\Http\Controllers;

use Exception;
use App\Services\CommissionPaymentValidationService;
use App\Models\Gig;
use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Para lidar com uploads
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; // Para validação manual no controller

class SettlementController extends Controller
{
    /**
     * Registra o pagamento do cachê ao artista e atualiza/cria o acerto.
     */
    public function settleArtistPayment(Request $request, Gig $gig): RedirectResponse
    {
        // Validação dos dados do formulário do modal
        $validator = Validator::make($request->all(), [
            'artist_payment_date' => ['required', 'date', 'before_or_equal:today'],
            'artist_payment_value_paid' => ['required', 'numeric', 'min:0'], // Valor efetivamente pago
            'artist_payment_notes' => ['nullable', 'string', 'max:65535'],
            'artist_payment_proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'], // 2MB Max
        ], [
            'artist_payment_date.required' => 'A data do pagamento ao artista é obrigatória.',
            'artist_payment_value_paid.required' => 'O valor pago ao artista é obrigatório.',
            // Adicione outras mensagens personalizadas
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'settleArtist')->withInput()->with('open_modal', 'settleArtist');
        }
        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            // Cria ou atualiza o registro de Settlement
            $settlement = Settlement::firstOrNew(['gig_id' => $gig->id]);

            $settlement->settlement_date = $settlement->settlement_date ?? $validated['artist_payment_date']; // Usa data do 1º acerto
            $settlement->artist_payment_value = $validated['artist_payment_value_paid']; // NOVO CAMPO (precisa de migration)
            $settlement->artist_payment_paid_at = $validated['artist_payment_date']; // NOVO CAMPO (precisa de migration)

            if ($request->hasFile('artist_payment_proof_file')) {
                // Deleta o comprovante antigo se existir
                if ($settlement->artist_payment_proof) {
                    Storage::disk('public')->delete($settlement->artist_payment_proof);
                }
                $settlement->artist_payment_proof = $request->file('artist_payment_proof_file')->store('settlements/artist_proofs', 'public');
            }
            $settlement->notes = trim(($settlement->notes ?? '')."\n[Artista ".now()->format('d/m/y H:i').']: '.($validated['artist_payment_notes'] ?? 'Pagamento registrado.'));
            $settlement->save();

            // Atualiza o status da Gig
            $gig->update(['artist_payment_status' => 'pago']);
            $gig->save();

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Pagamento ao artista registrado com sucesso!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erro ao registrar pagamento ao artista para Gig {$gig->id}: ".$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Erro ao registrar pagamento ao artista.')->with('open_modal', 'settleArtist');
        }
    }

    /**
     * Registra o pagamento da comissão ao booker e atualiza/cria o acerto.
     */
    public function settleBookerCommission(Request $request, Gig $gig): RedirectResponse
    {
        if (! $gig->booker_id) { // Segurança extra
            return back()->with('error', 'Esta Gig não possui um booker associado.');
        }

        // Validar regra de negócio: não permitir pagamento para eventos futuros
        $validationService = app(CommissionPaymentValidationService::class);
        $validation = $validationService->validateBookerCommissionPayment($gig, false);

        if (! $validation['valid']) {
            return back()->with('error', $validation['message']);
        }

        $validator = Validator::make($request->all(), [
            'booker_commission_date' => ['required', 'date', 'before_or_equal:today'],
            'booker_commission_value_paid' => ['required', 'numeric', 'min:0'], // Valor efetivamente pago
            'booker_commission_notes' => ['nullable', 'string', 'max:65535'],
            'booker_commission_proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [/* ... mensagens ... */]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'settleBooker')->withInput()->with('open_modal', 'settleBooker');
        }
        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $settlement = Settlement::firstOrNew(['gig_id' => $gig->id]);

            $settlement->settlement_date = $settlement->settlement_date ?? $validated['booker_commission_date'];
            $settlement->booker_commission_value_paid = $validated['booker_commission_value_paid']; // NOVO CAMPO
            $settlement->booker_commission_paid_at = $validated['booker_commission_date']; // NOVO CAMPO

            if ($request->hasFile('booker_commission_proof_file')) {
                if ($settlement->booker_commission_proof) {
                    Storage::disk('public')->delete($settlement->booker_commission_proof);
                }
                $settlement->booker_commission_proof = $request->file('booker_commission_proof_file')->store('settlements/booker_proofs', 'public');
            }
            $settlement->notes = $settlement->notes."\n[Booker ".now()->format('d/m/y H:i').']: '.($validated['booker_commission_notes'] ?? '');
            $settlement->save();

            $gig->update(['booker_payment_status' => 'pago']);
            $gig->save();

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Pagamento da comissão do booker registrado!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erro ao registrar comissão do booker para Gig {$gig->id}: ".$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Erro ao registrar comissão do booker.')->with('open_modal', 'settleBooker');
        }
    }

    /**
     * Reverte o registro de pagamento do cachê ao artista.
     */
    public function unsettleArtistPayment(Request $request, Gig $gig): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $settlement = $gig->settlement; // Pega o acerto da Gig

            if ($settlement) {
                // Limpa os campos relacionados ao pagamento do artista no acerto
                $settlement->artist_payment_value = null;
                $settlement->artist_payment_paid_at = null;
                // Opcional: decidir se o comprovante deve ser deletado ou mantido
                // if ($settlement->artist_payment_proof) {
                //     Storage::disk('public')->delete($settlement->artist_payment_proof);
                //     $settlement->artist_payment_proof = null;
                // }
                // Limpa notas específicas do artista se for o caso, ou mantém notas gerais
                // $settlement->notes = preg_replace('/\[Artista.*?\]:.*?\n?/s', '', $settlement->notes); // Remove notas do artista
                $settlement->save();
            }

            // Atualiza o status da Gig para pendente
            $gig->update(['artist_payment_status' => 'pendente']);
            $gig->save();

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Pagamento ao artista revertido para pendente!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erro ao reverter pagamento ao artista para Gig {$gig->id}: ".$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Erro ao reverter pagamento ao artista.');
        }
    }

    /**
     * Reverte o registro de pagamento da comissão ao booker.
     */
    public function unsettleBookerCommission(Request $request, Gig $gig): RedirectResponse
    {
        if (! $gig->booker_id) {
            return back()->with('error', 'Esta Gig não possui um booker associado para reverter comissão.');
        }

        DB::beginTransaction();
        try {
            $settlement = $gig->settlement;

            if ($settlement) {
                $settlement->booker_commission_value_paid = null;
                $settlement->booker_commission_paid_at = null;
                // Opcional: deletar comprovante booker
                // if ($settlement->booker_commission_proof) {
                //     Storage::disk('public')->delete($settlement->booker_commission_proof);
                //     $settlement->booker_commission_proof = null;
                // }
                // $settlement->notes = preg_replace('/\[Booker.*?\]:.*?\n?/s', '', $settlement->notes); // Remove notas do booker
                $settlement->save();
            }

            $gig->booker_payment_status = 'pendente';
            $gig->save();

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Pagamento da comissão do booker revertido para pendente!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erro ao reverter comissão do booker para Gig {$gig->id}: ".$e->getMessage(), ['exception' => $e]);

            return back()->with('error', 'Erro ao reverter comissão do booker.');
        }
    }
}
