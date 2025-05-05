<?php
namespace App\Listeners;

use App\Events\PaymentSaved;
use App\Models\Gig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateGigPaymentStatus // implements ShouldQueue
{
    public function handle(PaymentSaved $event): void
    {
        DB::transaction(function () use ($event) {
            $gigId = $event->gig->id; // Pega ID da gig do evento
            $gig = Gig::lockForUpdate()->find($gigId); // Busca e trava a gig

            if (!$gig) {
                 Log::warning("Listener UpdateGigPaymentStatus: Gig ID {$gigId} não encontrada.");
                 return;
            }

            Log::info("Listener UpdateGigPaymentStatus processando para Gig ID: {$gigId} após salvar/confirmar/deletar pagamento.");

            // 1. Buscar o valor total devido NA MOEDA ORIGINAL da Gig
            $totalDueOriginal = $gig->cache_value ?? 0;
            $gigCurrency = strtoupper($gig->currency ?? 'BRL'); // <-- REDEFINIR A VARIÁVEL AQUI

            // ... (check de cache <= 0) ...
            if ($totalDueOriginal <= 0) { /* ... marcar pago ... */ return; }

            // 2. Calcular o total CONFIRMADO na MOEDA ORIGINAL da Gig
            $totalReceivedOriginal = $gig->payments()
                                        ->whereNotNull('confirmed_at') // Só confirmados
                                        ->where('currency', $gigCurrency) // <-- Usa $gigCurrency
                                        ->sum('received_value_actual'); // Soma o valor REAL recebido
                                        // Considerar fallback para due_value se received_actual for null?
                                        // ->sum(DB::raw('COALESCE(received_value_actual, due_value, 0)'));

            $totalReceivedOriginal = round($totalReceivedOriginal, 2);

            // 3. Determinar o novo status geral de pagamento (Comparando na MOEDA ORIGINAL)
            $newPaymentStatus = 'a_vencer';
            $tolerance = 0.01;

            if ($totalReceivedOriginal >= ($totalDueOriginal - $tolerance)) {
                $newPaymentStatus = 'pago';
            } else {
                // Verifica se HÁ algum pagamento NÃO CONFIRMADO com data VENCIDA
                $isOverdue = $gig->payments()
                                 ->whereNull('confirmed_at')
                                 ->where('due_date', '<', today())
                                 ->exists();
                if ($isOverdue) {
                    $newPaymentStatus = 'vencido';
                } else {
                    $newPaymentStatus = 'a_vencer';
                }
            }

            // 4. Atualizar a Gig apenas se o status mudou
            if ($gig->payment_status !== $newPaymentStatus) {
                $originalStatus = $gig->payment_status;
                $gig->payment_status = $newPaymentStatus;
                $gig->saveQuietly();
                // Usa $gigCurrency no Log
                Log::info("Status de pagamento da Gig ID: {$gigId} atualizado de '{$originalStatus}' para '{$newPaymentStatus}'. Total Confirmado ({$gigCurrency}): {$totalReceivedOriginal}, Total Devido ({$gigCurrency}): {$totalDueOriginal}");
            } else {
                 // Usa $gigCurrency no Log
                 Log::info("Status de pagamento da Gig ID: {$gigId} permaneceu '{$newPaymentStatus}'. Total Confirmado ({$gigCurrency}): {$totalReceivedOriginal}, Total Devido ({$gigCurrency}): {$totalDueOriginal}");
            }
        });
    }
}