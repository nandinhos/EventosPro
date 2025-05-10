{{-- resources/views/gigs/_show_final_settlements.blade.php --}}
{{--
    Exibe e permite gerenciar os acertos finais (pagamentos efetuados ao Artista e Booker).
    Recebe:
    - $gig (O objeto Gig atual, com relacionamentos artist, booker, settlement carregados)
    - $settlement (O objeto Settlement associado à Gig, pode ser null se ainda não houver acerto)
--}}
@props(['gig', 'settlement'])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Acertos Financeiros (Pagamentos Efetuados)</h3>
    </div>

    <div class="p-6 space-y-6 text-sm">
        {{-- Seção: Acerto com Artista --}}
        <div class="p-4 rounded-md {{ $gig->artist_payment_status === 'pago' ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700/50' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700/50' }}">
            <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200">
                    Pagamento do Artista: <span class="text-primary-600 dark:text-primary-400">{{ $gig->artist->name ?? 'N/A' }}</span>
                </h4>
                <x-status-badge :status="$gig->artist_payment_status" type="payment-internal" />
            </div>

            @php
                // Este cálculo do valor líquido a pagar ao artista pode ser mais complexo
                // e idealmente viria de um Service ou do próprio objeto $settlement se já preenchido
                $baseArtistCache = $gig->cache_value_brl; // O valor já convertido para BRL que seria a base
                $confirmedExpensesTotal = $gig->confirmed_expenses_total_brl; // Do accessor
                $agencyCommissionOnGig = $gig->agency_commission_value ?? 0; // Comissão da agência salva na gig

                // Valor líquido estimado que o artista deveria receber
                $netArtistCacheToReceive = $baseArtistCache - $confirmedExpensesTotal - $agencyCommissionOnGig;
            @endphp

            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                Valor líquido estimado a pagar:
                <strong class="text-gray-800 dark:text-white">R$ {{ number_format($netArtistCacheToReceive, 2, ',', '.') }}</strong>
            </p>
            <p class="text-xxs text-gray-500 dark:text-gray-500 italic leading-tight">
                 (Baseado em Cachê BRL: R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}
                 <br>- Despesas Confirmadas: R$ {{ number_format($confirmedExpensesTotal, 2, ',', '.') }}
                 <br>- Comissão Agência: R$ {{ number_format($agencyCommissionOnGig, 2, ',', '.') }})
            </p>

            @if ($gig->artist_payment_status === 'pendente')
                <div class="mt-4">
                     <button type="button"
                             @click="$dispatch('open-settle-artist-modal', {
                                 gigId: {{ $gig->id }},
                                 artistName: '{{ addslashes($gig->artist->name ?? 'N/A') }}',
                                 amountDue: {{ $netArtistCacheToReceive }}
                             })"
                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                        <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento ao Artista
                     </button>
                </div>
            @else {{-- Status é 'pago' --}}
                <div class="mt-3 text-xs">
                    <div class="text-green-700 dark:text-green-300 mb-2">
                        <p class="font-medium"><i class="fas fa-check-circle fa-fw"></i> Pagamento ao artista registrado como 'Pago'.</p>
                        @if($settlement?->artist_payment_value)
                            <p>Valor Registrado: R$ {{ number_format($settlement->artist_payment_value, 2, ',', '.') }}
                                @if($settlement->artist_payment_paid_at)
                                 em {{ $settlement->artist_payment_paid_at?->format('d/m/Y') }}
                                @endif
                            </p>
                        @endif
                        @if($settlement?->artist_payment_proof)
                            <p class="mt-1">Comprovante:
                                <a href="{{ Storage::url($settlement->artist_payment_proof) }}" target="_blank" class="text-blue-500 hover:underline ml-1">
                                    <i class="fas fa-paperclip"></i> Visualizar
                                </a>
                            </p>
                        @endif
                    </div>
                    {{-- Botão para Reverter --}}
                    <form action="{{ route('gigs.settlements.artist.unsettle', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja reverter o pagamento ao artista para PENDENTE? Isso limpará os dados de pagamento do artista neste acerto.');" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                            <i class="fas fa-undo-alt mr-1"></i> Reverter para Pendente
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Seção: Acerto com Booker --}}
        @if($gig->booker_id && ($gig->booker_commission_value ?? 0) > 0.009) {{-- Só mostra se houver booker E comissão > 0 (para evitar float issues) --}}
        <div class="p-4 rounded-md {{ $gig->booker_payment_status === 'pago' ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700' }}">
            <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200">
                    Comissão do Booker: <span class="text-primary-600 dark:text-primary-400">{{ $gig->booker->name ?? 'N/A' }}</span>
                </h4>
                <x-status-badge :status="$gig->booker_payment_status" type="payment-internal" />
            </div>
            <p class="text-xs text-gray-600 dark:text-gray-400">
                Valor da comissão:
                <strong class="text-gray-800 dark:text-white">R$ {{ number_format($gig->booker_commission_value ?? 0, 2, ',', '.') }}</strong>
            </p>

            @if ($gig->booker_payment_status === 'pendente')
                 <div class="mt-4">
                     <button type="button"
                             @click="$dispatch('open-settle-booker-modal', {
                                 gigId: {{ $gig->id }},
                                 bookerName: '{{ addslashes($gig->booker->name ?? 'N/A') }}',
                                 commissionAmount: {{ $gig->booker_commission_value ?? 0 }}
                             })"
                             class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                        <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento Comissão
                     </button>
                </div>
            @else {{-- Status é 'pago' --}}
                <div class="mt-3 text-xs">
                    <div class="text-green-700 dark:text-green-300 mb-2">
                        <p class="font-medium"><i class="fas fa-check-circle fa-fw"></i> Comissão do booker registrada como 'Paga'.</p>
                         @if($settlement?->booker_commission_value_paid)
                            <p>Valor Pago: R$ {{ number_format($settlement->booker_commission_value_paid, 2, ',', '.') }}
                                @if($settlement->booker_commission_paid_at)
                                 em {{ $settlement->booker_commission_paid_at?->format('d/m/Y') }}
                                @endif
                            </p>
                        @endif
                        @if($settlement?->booker_commission_proof)
                            <p class="mt-1">Comprovante:
                                <a href="{{ Storage::url($settlement->booker_commission_proof) }}" target="_blank" class="text-blue-500 hover:underline ml-1">
                                    <i class="fas fa-paperclip"></i> Visualizar
                                </a>
                            </p>
                        @endif
                    </div>
                     {{-- Botão para Reverter --}}
                    <form action="{{ route('gigs.settlements.booker.unsettle', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja reverter o pagamento da comissão do booker para PENDENTE? Isso limpará os dados de pagamento da comissão neste acerto.');" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                            <i class="fas fa-undo-alt mr-1"></i> Reverter para Pendente
                        </button>
                    </form>
                </div>
            @endif
        </div>
        @elseif($gig->booker_id && ($gig->booker_commission_value ?? 0) <= 0.009)
            <div class="p-4 rounded-md bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-700">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200">Comissão do Booker: {{ $gig->booker->name ?? 'N/A' }}</h4>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sem comissão para este booker nesta gig.</p>
            </div>
        @endif
    </div>
</div>