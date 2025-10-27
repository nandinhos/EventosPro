{{--
    Este parcial contém os botões de ação para o acerto do Booker.
    Recebe as props: $gig
    E a variável $calculatedBookerCommissionBrl do escopo Alpine onde for incluído.
--}}
@props(['gig'])

@if ($gig->booker_payment_status === 'pendente' && ($financials['calculatedBookerCommissionBrl'] ?? 0) > 0.009)
     <div class="mt-4">
         <button type="button"
                 @click="$dispatch('open-settle-booker-modal', {
                     gigId: {{ $gig->id }},
                     bookerName: '{{ addslashes($gig->booker->name ?? 'N/A') }}',
                     commissionAmount: financials.calculatedBookerCommissionBrl
                 })"
                 class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
            <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento Comissão
         </button>
     </div>
@elseif ($gig->booker_payment_status === 'pago')
    <div class="mt-3 text-xs">
        <div class="text-green-700 dark:text-green-300 mb-2">
            <p class="font-medium"><i class="fas fa-check-circle fa-fw"></i> Comissão do booker registrada como 'Paga'.</p>
             @if($gig->settlement?->booker_commission_value_paid)
                <p>Valor Registrado Pago: R$ {{ number_format($gig->settlement->booker_commission_value_paid, 2, ',', '.') }}
                    @if($gig->settlement->booker_commission_paid_at)
                     em {{ $gig->settlement->booker_commission_paid_at?->isoFormat('L') }}
                    @endif
                </p>
            @endif
            @if($gig->settlement?->booker_commission_proof)
                <p class="mt-1">Comprovante:
                    <a href="{{ Storage::url($gig->settlement->booker_commission_proof) }}" target="_blank" class="text-blue-500 hover:underline ml-1">
                        <i class="fas fa-paperclip"></i> Visualizar
                    </a>
                </p>
            @endif
        </div>
        <form action="{{ route('gigs.settlements.booker.unsettle', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja reverter o pagamento da comissão do booker para PENDENTE?');" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                <i class="fas fa-undo-alt mr-1"></i> Reverter para Pendente
            </button>
        </form>
    </div>
@endif