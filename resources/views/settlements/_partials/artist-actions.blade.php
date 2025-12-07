{{--
    Este parcial contém os botões de ação para o acerto do Artista.
    Recebe as props: $gig, $backUrlParams
    E a variável $calculatedArtistInvoiceValueBrl do escopo Alpine onde for incluído.
--}}
@props(['gig', 'backUrlParams'])

@if ($gig->artist_payment_status === 'pendente')
    @php
        $workflowStage = $gig->settlement?->settlement_stage ?? 'aguardando_conferencia';
    @endphp
    <div class="mt-4">
         <div class="flex flex-wrap gap-2">
             {{-- Botões de Ação do Workflow --}}
             @if($workflowStage === 'aguardando_conferencia')
                 <form action="{{ route('artists.settlements.send', $gig) }}" method="POST" class="inline">
                     @csrf
                     @method('PATCH')
                     <input type="hidden" name="redirect_to" value="show">
                     <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                         <i class="fas fa-paper-plane mr-1"></i> Enviar Fechamento
                     </button>
                 </form>
             @elseif($workflowStage === 'fechamento_enviado')
                 <span class="px-3 py-1.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-md">
                     <i class="fas fa-hourglass-half mr-1"></i> Aguardando NF/Recibo
                 </span>
             @elseif($workflowStage === 'documentacao_recebida')
                 <span class="px-3 py-1.5 text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded-md">
                     <i class="fas fa-check mr-1"></i> Pronto p/ Pagar
                 </span>
             @endif
             
             <a href="{{ route('gigs.request-nf', ['gig' => $gig] + ($backUrlParams ?? [])) }}"
                class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                <i class="fas fa-file-invoice mr-2"></i> Detalhes NF Artista
            </a>
         </div>
    </div>
@else {{-- Status é 'pago' --}}
    <div class="mt-3 text-xs">
        <div class="text-green-700 dark:text-green-300 mb-2">
            <p class="font-medium"><i class="fas fa-check-circle fa-fw"></i> Pagamento ao artista registrado como 'Pago'.</p>
            @if($gig->settlement?->artist_payment_value)
                <p>Valor Registrado: R$ {{ number_format($gig->settlement->artist_payment_value, 2, ',', '.') }}
                    @if($gig->settlement->artist_payment_paid_at)
                     em {{ $gig->settlement->artist_payment_paid_at?->isoFormat('L') }}
                    @endif
                </p>
            @endif
            @if($gig->settlement?->artist_payment_proof)
                <p class="mt-1">Comprovante:
                    <a href="{{ Storage::url($gig->settlement->artist_payment_proof) }}" target="_blank" class="text-blue-500 hover:underline ml-1">
                        <i class="fas fa-paperclip"></i> Visualizar
                    </a>
                </p>
            @endif
             <a href="{{ route('gigs.request-nf', ['gig' => $gig] + ($backUrlParams ?? [])) }}" class="mt-2 inline-flex items-center text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                <i class="fas fa-file-invoice mr-1"></i> Ver/Atualizar Detalhes NF
            </a>
        </div>
        <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST" onsubmit="return confirm('Reverter pagamento ao artista? O status voltará para Pronto p/ Pagar.');" class="inline">
            @csrf @method('PATCH')
            <input type="hidden" name="redirect_to" value="show">
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                <i class="fas fa-undo-alt mr-1"></i> Reverter Pagamento
            </button>
        </form>
    </div>
@endif