{{-- Recebe $settlement (pode ser null) e $gig --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Acerto Final</h3>
         {{-- Mostra botão para registrar se ainda não houver acerto --}}
         @if(!$settlement)
            {{-- TODO: Link/Botão para a rota de registrar acerto --}}
            {{-- <a href="{{ route('gigs.settlements.create', $gig) }}" class="text-primary-600 text-sm hover:underline">Registrar Acerto</a> --}}
            <span class="text-xs text-yellow-600 dark:text-yellow-400">Pendente</span>
         @endif
    </div>
    <div class="p-6 text-sm">
         @if($settlement)
            <div class="space-y-3">
                <p><strong class="text-gray-500 dark:text-gray-400">Data do Acerto:</strong> {{ $settlement->settlement_date->format('d/m/Y') }}</p>

                {{-- Link para comprovante do artista --}}
                @if($settlement->artist_payment_proof)
                <p>
                    <strong class="text-gray-500 dark:text-gray-400">Comp. Artista:</strong>
                    {{-- Assumindo que file_path é acessível via storage link --}}
                    <a href="{{ Storage::url($settlement->artist_payment_proof) }}" target="_blank" class="text-blue-500 hover:underline ml-1">
                        <i class="fas fa-paperclip mr-1"></i> Visualizar
                    </a>
                </p>
                @else
                 <p><strong class="text-gray-500 dark:text-gray-400">Comp. Artista:</strong> Não informado</p>
                @endif

                {{-- Link para comprovante do booker --}}
                 @if($settlement->booker_commission_proof)
                <p>
                    <strong class="text-gray-500 dark:text-gray-400">Comp. Booker:</strong>
                    <a href="{{ Storage::url($settlement->booker_commission_proof) }}" target="_blank" class="text-blue-500 hover:underline ml-1">
                         <i class="fas fa-paperclip mr-1"></i> Visualizar
                    </a>
                </p>
                 @else
                  <p><strong class="text-gray-500 dark:text-gray-400">Comp. Booker:</strong> Não informado</p>
                 @endif

                 @if($settlement->notes)
                    <p><strong class="text-gray-500 dark:text-gray-400">Notas do Acerto:</strong><br><span class="whitespace-pre-wrap">{{ $settlement->notes }}</span></p>
                 @endif
                  <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                        {{-- TODO: Botão para editar ou excluir acerto --}}
                  </div>
            </div>
         @else
             <p class="text-gray-500 dark:text-gray-400">Nenhum acerto final registrado para esta Gig.</p>
         @endif
    </div>
</div>