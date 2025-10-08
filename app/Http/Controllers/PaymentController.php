<?php

namespace App\Http\Controllers;

use App\Events\PaymentSaved;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Gig;
use App\Models\Payment;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <-- Importar o Evento
use Illuminate\Support\Facades\Log; // Importar Rule para validação
use Illuminate\Support\Facades\Validator; // Importar Validator para validação
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException; // Importar o novo Form Request

class PaymentController extends Controller
{
    /**
     * Store a newly created PREDICTED payment resource in storage.
     */
    public function store(Request $request, Gig $gig): RedirectResponse // Usar Request ou criar StorePaymentRequest
    {
        Log::info('Dados recebidos no Payment Store (Parcela Prevista):', $request->all());

        // Validação para os campos da PARCELA PREVISTA
        $validated = $request->validate([
            'due_value' => ['required', 'numeric', 'min:0.01'], // Valor DEVIDO
            'due_date' => ['required', 'date'],                // Vencimento
            'currency' => ['required', 'string', 'max:10'],
            'exchange_rate' => [
                'nullable',
                Rule::requiredIf(fn () => strtoupper($request->input('currency', 'BRL')) !== 'BRL'),
                'numeric', 'min:0',
            ],
            'description' => ['nullable', 'string', 'max:255'], // Adicionar description se tiver no form
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);
        Log::info('Dados validados para Parcela Prevista:', $validated);

        DB::beginTransaction();
        try {
            Log::info("Iniciando criação da Parcela Prevista para Gig ID: {$gig->id}");

            // Adiciona gig_id e preenche campos de confirmação como NULL
            $validated['gig_id'] = $gig->id;
            $validated['received_value_actual'] = null;
            $validated['received_date_actual'] = null;
            $validated['confirmed_at'] = null;
            $validated['confirmed_by'] = null;
            // 'status' não existe mais
            // 'paid_at' não existe mais

            // Garante que exchange_rate seja null se moeda for BRL
            if (strtoupper($validated['currency']) === 'BRL') {
                $validated['exchange_rate'] = null;
            }
            // Adiciona description se não veio do form
            $validated['description'] = $validated['description'] ?? 'Parcela Prevista';

            Log::info('Dados para criar Payment (Previsto):', $validated);
            $payment = Payment::create($validated);
            Log::info("Parcela Prevista criada com ID: {$payment->id}");

            // Disparar o evento para recalcular o status da Gig (A VENCER/VENCIDO)
            Log::info('Disparando evento PaymentSaved (para recalcular status da Gig)...');
            event(new PaymentSaved($payment));
            Log::info('Evento PaymentSaved disparado.');

            Log::info('Commitando transação...');
            DB::commit();
            Log::info('Transação commitada.');

            return redirect()->route('gigs.show', $gig)->with('success', 'Parcela prevista registrada com sucesso!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Erro CRÍTICO ao registrar Parcela Prevista para Gig ID {$gig->id}: ".$e->getMessage()."\n".$e->getTraceAsString());

            // Usar error bag para diferenciar do erro de confirmação/update
            return back()->withInput()->withErrors($e->getMessage(), 'paymentStore'); // Ou uma msg genérica
        }
    }

    /**
     * Remove the specified payment from storage (Soft Delete).
     */
    public function destroy(Gig $gig, Payment $payment): RedirectResponse
    {
        // Opcional: Adicionar verificação se o pagamento realmente pertence à gig (Policy)
        if ($payment->gig_id !== $gig->id) {
            abort(403, 'Acesso não autorizado.');
        }

        DB::beginTransaction();
        try {
            Log::info("Excluindo Pagamento ID: {$payment->id} para Gig ID: {$gig->id}");

            $payment->delete(); // Executa Soft Delete se configurado no Modelo Payment

            Log::info("Pagamento ID: {$payment->id} marcado como excluído.");

            // Dispara o mesmo evento 'PaymentSaved' passando a GIG
            // O listener vai recalcular o status baseado nos pagamentos restantes.
            // Precisamos garantir que o listener busque os pagamentos *não deletados*.
            Log::info("Disparando evento para atualizar status da Gig ID: {$gig->id} após exclusão de pagamento.");
            event(new PaymentSaved($payment)); // Passa o pagamento (que contém a gig_id)
            Log::info('Evento disparado após exclusão.');

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Registro de pagamento excluído com sucesso!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir pagamento: '.$e->getMessage(), ['exception' => $e, 'payment_id' => $payment->id]);

            return back()->with('error', 'Erro ao excluir o registro de pagamento.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Gig $gig, Payment $payment): RedirectResponse
    {
        Log::info('--- Iniciando Payment Update ---');
        Log::info("Gig ID: {$gig->id}, Payment ID: {$payment->id}");
        Log::info('Dados Recebidos:', $request->all()); // Log dos dados brutos

        // Validação feita pelo UpdatePaymentRequest
        $validated = $request->validated();
        Log::info('Dados Validados:', $validated);

        // Opcional: Verificar se o pagamento pertence à Gig
        if ($payment->gig_id !== $gig->id) {
            Log::error("Tentativa de atualizar pagamento ({$payment->id}) que não pertence à Gig ({$gig->id}).");
            abort(403);
        }

        DB::beginTransaction();
        try {
            Log::info("Atualizando Pagamento ID: {$payment->id} com dados validados.");

            // Lógica para 'paid_at' e 'status' se forem editáveis (removido por enquanto)
            // $validated['paid_at'] = ...;
            // $validated['status'] = ...;

            // Garante que exchange_rate seja null se moeda for BRL
            if (strtoupper($validated['currency']) === 'BRL') {
                $validated['exchange_rate'] = null;
            }

            $updated = $payment->update($validated); // update() retorna true ou false
            Log::info('Resultado do payment->update(): '.($updated ? 'Sucesso' : 'Falha'));

            if ($updated) {
                Log::info("Pagamento ID: {$payment->id} atualizado no DB.");
                // Disparar evento APENAS se o update foi bem-sucedido
                event(new PaymentSaved($payment));
                Log::info('Evento PaymentSaved disparado após atualização.');
            } else {
                Log::warning("Falha ao atualizar Pagamento ID: {$payment->id}. Update retornou false.");
            }

            Log::info('Commitando transação...');
            DB::commit();
            Log::info('Transação commitada.');

            return redirect()->route('gigs.show', $gig)->with('success', 'Registro de pagamento atualizado!');

        } catch (ValidationException $e) { // Captura erro de validação explicitamente
            DB::rollBack();
            Log::error('Erro de Validação ao atualizar pagamento: ', $e->errors());

            return back()->withErrors($e->validator, 'paymentUpdate')->withInput()->with('error_payment_id', $payment->id); // Usa error bag e passa ID de volta
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro CRÍTICO ao atualizar pagamento: '.$e->getMessage()."\n".$e->getTraceAsString());

            return back()->withInput()->with('error', 'Erro ao atualizar o registro de pagamento.');
        }
    }

    /**
     * Mark a payment as confirmed.
     */
    // Usa o novo Form Request para validação
    public function confirm(ConfirmPaymentRequest $request, Gig $gig, Payment $payment): RedirectResponse
    {
        $validated = $request->validated();

        if ($payment->gig_id !== $gig->id || $payment->confirmed_at) {
            return back()->with('error', 'Este pagamento não pode ser confirmado ou já está confirmado.');
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'received_date_actual' => $validated['received_date_actual'],
                'received_value_actual' => $validated['received_value_actual'],
                'currency' => $validated['currency_received_actual'], // Salva a moeda do recebimento
                'exchange_rate' => $validated['exchange_rate_received_actual'], // Salva o câmbio do recebimento
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
                // 'notes' => $payment->notes . "\n" . ($validated['notes'] ?? ''), // Concatena notas
                'notes' => $request->filled('notes') ? $validated['notes'] : $payment->notes,
            ];
            // Remove exchange_rate se a moeda for BRL (o prepareForValidation já deveria ter feito isso)
            if (strtoupper($updateData['currency']) === 'BRL') {
                $updateData['exchange_rate'] = null;
            }

            $payment->update($updateData);
            Log::info("Pagamento ID: {$payment->id} confirmado por User ID: ".auth()->id());

            event(new PaymentSaved($payment)); // Dispara evento para recalcular status da Gig
            Log::info('Evento PaymentSaved disparado após confirmação.');

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Pagamento confirmado!');

        } catch (ValidationException $e) { // Captura erro de validação explicitamente
            DB::rollBack();
            Log::error('Erro de Validação ao confirmar pagamento: ', $e->errors());

            // Redireciona com o error bag específico e o ID do pagamento que deu erro
            return back()->withErrors($e->validator, 'paymentConfirm'.$payment->id)->withInput()->with('error_payment_id', $payment->id);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro CRÍTICO ao confirmar pagamento: '.$e->getMessage(), ['exception' => $e]);

            return back()->withInput()->with('error', 'Erro ao confirmar o pagamento.');
        }
    }

    /**
     * Revert a payment confirmation.
     */
    public function unconfirm(Request $request, Gig $gig, Payment $payment): RedirectResponse // Usar Request padrão, sem validação extra
    {
        // Opcional: Verificar se o pagamento pertence à Gig e SE ESTÁ confirmado
        if ($payment->gig_id !== $gig->id || ! $payment->confirmed_at) {
            return back()->with('error', 'Este pagamento não pode ser desconfirmado.');
        }

        // TODO: Adicionar Policy para verificar se o usuário logado PODE desconfirmar

        DB::beginTransaction();
        try {
            Log::info("Desconfirmando Pagamento ID: {$payment->id} para Gig ID: {$gig->id}");

            // Limpa os campos relacionados à confirmação
            $payment->update([
                'received_value_actual' => null,
                'received_date_actual' => null,
                'confirmed_at' => null,
                'confirmed_by' => null,
                // Mantém currency e exchange_rate originais da parcela prevista? Ou limpa?
                // Vamos limpar por segurança, podem ser reinseridos na próxima confirmação.
                'exchange_rate' => null, // Descomente se quiser limpar
                'currency' => $gig->currency, // <<-- BÔNUS: Restaura a moeda para a original da Gig

            ]);

            Log::info("Pagamento ID: {$payment->id} desconfirmado.");

            // Dispara evento para recalcular status da Gig
            event(new PaymentSaved($payment));
            Log::info('Evento disparado após desconfirmação do pagamento.');

            DB::commit();

            return redirect()->route('gigs.show', $gig)->with('success', 'Confirmação do pagamento revertida!');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erro ao desconfirmar pagamento: '.$e->getMessage(), ['exception' => $e, 'payment_id' => $payment->id]);

            return back()->with('error', 'Erro ao reverter a confirmação do pagamento.');
        }
    }
}
