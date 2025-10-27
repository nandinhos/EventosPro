{{--
    Este parcial contém os botões de ação para o acerto do Artista.
    Recebe as props: $gig, $backUrlParams
    E a variável $calculatedArtistInvoiceValueBrl do escopo Alpine onde for incluído.
--}}
@props(['gig', 'backUrlParams'])

@if ($gig->artist_payment_status === 'pendente')
    <div class="mt-4">
         <div class="flex flex-wrap gap-2">
             <button type="button"
                     @click="$dispatch('open-settle-artist-modal', {
                         gigId: {{ $gig->id }},
                         artistName: '{{ addslashes($gig->artist->name ?? 'N/A') }}',
                         amountDue: financials.calculatedArtistInvoiceValueBrl < 0 ? 0 : financials.calculatedArtistInvoiceValueBrl
                     })"
                     class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento ao Artista
            </button>
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
        <form action="{{ route('gigs.settlements.artist.unsettle', $gig) }}" method="POST" onsubmit="return confirm('Reverter pagamento ao artista para PENDENTE?');" class="inline">
            @csrf @method('PATCH')
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                <i class="fas fa-undo-alt mr-1"></i> Reverter para Pendente
            </button>
        </form>
    </div>
@endif