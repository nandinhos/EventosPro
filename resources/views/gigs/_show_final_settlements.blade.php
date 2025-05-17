{{-- resources/views/gigs/_show_final_settlements.blade.php --}}
{{--
    Exibe e permite gerenciar os acertos finais (pagamentos efetuados ao Artista e Booker).
    Recebe:
    - $gig (O objeto Gig atual, com relacionamentos artist, booker, settlement carregados)
    - $settlement (O objeto Settlement associado à Gig, pode ser null se ainda não houver acerto)
--}}
@props(['gig', 'settlement', 'confirmedExpensesGrouped', 'totalConfirmedExpensesBrl', 'agencyTotalCommissionOnGig', 'netArtistCacheToReceive'])

<div
    x-data="{ reloadSettlements() { window.location.reload(); } }"
    @costs-updated.window="reloadSettlements()"
    class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Acertos Financeiros (Pagamentos Efetuados)</h3>
    </div>

    <div class="p-6 space-y-6 text-sm">
  
{{-- ============================================= --}}
{{-- Seção: Acerto com Artista (REFATORADA E COMPLETA) --}}
{{-- ============================================= --}}
<div class="p-4 rounded-md {{ $gig->artist_payment_status === 'pago' ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700/50' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700/50' }}">
    <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
        <h4 class="font-semibold text-gray-700 dark:text-gray-200">
            Pagamento do Artista: <span class="text-primary-600 dark:text-primary-400">{{ $gig->artist->name ?? 'N/A' }}</span>
        </h4>
        <x-status-badge :status="$gig->artist_payment_status" type="payment-internal" />
    </div>

    @php
        // 1. Cachê Bruto da Gig em BRL (usando o Accessor do modelo Gig)
        $gigCacheValueBrl = $gig->cache_value_brl;

        // 2. Total das Despesas Confirmadas em BRL (usando o Accessor do modelo Gig)
        $totalConfirmedExpensesBrl = $gig->confirmed_expenses_total_brl;

        // 3. Base para Comissões (Cachê BRL - Despesas Confirmadas) - (usando o Accessor do modelo Gig)
        $commissionBaseBrl = $gig->commission_base_brl;

        // 4. Comissão da Agência (usando o Accessor do modelo Gig, que calcula se for percentual)
        $agencyCommissionOnGig = $gig->agency_commission_value ?? 0;

        // 5. Valor Líquido Estimado a Pagar ao Artista (para a NF)
        // (Base de Comissão - Comissão da Agência)
        // Se a comissão da agência já foi deduzida para chegar na base, este cálculo muda.
        // Assumindo que a base é Cache - Despesas, e a comissão da agência sai dessa base.
        $netArtistCacheToReceive = $commissionBaseBrl - $agencyCommissionOnGig;
    @endphp

    <div class="space-y-2 text-sm">
        {{-- Valor do Contrato/Cachê --}}
        <div class="flex items-center justify-between py-1">
            <span class="text-gray-600 dark:text-gray-400">Valor Contrato ({{ $gig->currency }}):</span>
            <span class="font-semibold text-gray-800 dark:text-white">
                {{ $gig->currency }} {{ number_format($gig->cache_value ?? 0, 2, ',', '.') }}
            </span>
        </div>
        @if($gig->currency !== 'BRL')
            <div class="flex items-center justify-between py-1 text-xs -mt-1">
                <span class="text-gray-500 dark:text-gray-400 ml-4">(Equivalente em BRL para cálculo):</span>
                <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gigCacheValueBrl, 2, ',', '.') }}</span>
            </div>
        @endif

        {{-- Detalhamento das Despesas Confirmadas --}}
        @if($gig->costs->where('is_confirmed', true)->isNotEmpty())
            <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
                <span class="text-gray-600 dark:text-gray-400 block mb-1 font-medium">(-) Despesas Confirmadas (Dedutíveis):</span>
                <div class="pl-4 space-y-1">
                    @foreach($gig->costs->where('is_confirmed', true)->groupBy('costCenter.name') as $costCenterName => $costsInGroup)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">- {{ $costCenterName ?: 'Outras Despesas' }}:</span>
                            <span class="font-medium text-red-600 dark:text-red-400">R$ {{ number_format($costsInGroup->sum('value'), 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                        <span class="text-gray-500 dark:text-gray-400">Total Despesas Confirmadas:</span>
                        <span class="text-red-600 dark:text-red-400">R$ {{ number_format($totalConfirmedExpensesBrl, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
                <span class="text-gray-600 dark:text-gray-400">(-) Despesas Confirmadas:</span>
                <span class="font-semibold text-gray-800 dark:text-white ml-2">R$ 0,00</span>
            </div>
        @endif

        {{-- Cachê Base para Comissões --}}
        <div class="flex items-center justify-between py-1 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-2 -mx-2 rounded">
            <span class="text-gray-700 dark:text-gray-300">= Cachê Base para Comissões:</span>
            <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($commissionBaseBrl, 2, ',', '.') }}</span>
        </div>

        {{-- Comissão Agência --}}
        {{-- Só mostra se houver uma taxa ou um valor fixo definido --}}
        @if( ($gig->agency_commission_type === 'percent' && ($gig->agency_commission_rate ?? 0) > 0) || ($gig->agency_commission_type === 'fixed' && ($gig->agency_commission_value ?? 0) > 0) )
        <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
             <span class="text-gray-600 dark:text-gray-400 block mb-1 font-medium">(-) Comissão Agência:</span>
             <div class="pl-4 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">
                        @if($gig->agency_commission_type === 'percent' && $gig->agency_commission_rate)
                            {{ number_format($gig->agency_commission_rate, 1) }}% sobre R$ {{ number_format($commissionBaseBrl, 2, ',', '.') }}
                        @elseif($gig->agency_commission_type === 'fixed')
                            Valor Fixo
                        @else
                             Valor Definido (Verificar)
                        @endif
                    </span>
                    {{-- $gig->agency_commission_value AQUI JÁ É O VALOR CALCULADO PELO ACCESSOR --}}
                    <span class="font-medium text-red-600 dark:text-red-400">R$ {{ number_format($agencyCommissionOnGig, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        @else
            <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
                 <span class="text-gray-600 dark:text-gray-400 block font-medium">(-) Comissão Agência:</span>
                 <div class="pl-4 text-xs text-gray-500 dark:text-gray-400">Não definida ou R$ 0,00.</div>
            </div>
        @endif


        {{-- Valor Líquido Final para o Artista (NF) --}}
        <div class="flex items-center justify-between py-2 mt-2 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-2 -mx-2 rounded">
            <span class="font-medium text-gray-700 dark:text-gray-300">= Valor Líquido para Nota Fiscal do Artista:</span>
            <span class="font-bold text-lg text-primary-600 dark:text-primary-400">R$ {{ number_format($netArtistCacheToReceive, 2, ',', '.') }}</span>
        </div>
    </div>

    {{-- Botões de Ação --}}
    @if ($gig->artist_payment_status === 'pendente')
        <div class="mt-4">
             <div class="flex flex-wrap gap-2">
                 <button type="button"
                         @click="$dispatch('open-settle-artist-modal', {
                             gigId: {{ $gig->id }},
                             artistName: '{{ addslashes($gig->artist->name ?? 'N/A') }}',
                             amountDue: {{ $netArtistCacheToReceive < 0 ? 0 : $netArtistCacheToReceive }} {{-- Não pode ser negativo no modal --}}
                         })"
                         class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                    <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento ao Artista
                 </button>
                 <a href="{{ route('gigs.request-nf', $gig) }}"
                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                    <i class="fas fa-file-invoice mr-2"></i> Solicitar/Ver NF Artista
                 </a>
             </div>
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
                 <a href="{{ route('gigs.request-nf', $gig) }}" class="mt-2 inline-flex items-center text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                    <i class="fas fa-file-invoice mr-1"></i> Ver/Atualizar Dados NF
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
</div>
{{-- FIM Acerto com Artista --}}

{{-- ============================================= --}}
        {{-- Seção: Acerto com Booker (REVISADO E COMPLETO) --}}
        {{-- ============================================= --}}
        @if($gig->booker_id) {{-- Só processa se existe um booker_id na Gig --}}
            @php
                // Pega a base de comissão (Cachê BRL - Despesas Confirmadas) usando o accessor
                $commissionBaseForBooker = $gig->commission_base_brl;

                // Pega o valor da comissão do booker usando o accessor (que já calcula baseado no tipo e taxa/valor fixo)
                $bookerCommissionValueCalculated = $gig->booker_commission_value ?? 0;
            @endphp

            {{-- Mostra a seção se houver valor de comissão calculado OU se o status já for 'pago' (para permitir reversão) --}}
            @if( $bookerCommissionValueCalculated > 0.009 || $gig->booker_payment_status === 'pago' )
            <div class="p-4 rounded-md {{ $gig->booker_payment_status === 'pago' ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700/50' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700/50' }}">
                <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-200">
                        Comissão do Booker: <span class="text-primary-600 dark:text-primary-400">{{ $gig->booker->name ?? 'N/A' }}</span>
                    </h4>
                    <x-status-badge :status="$gig->booker_payment_status" type="payment-internal" />
                </div>

                <div class="space-y-1">
                    <div class="flex items-center justify-between py-1">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Base de Cálculo (Cachê Pós-Despesas):</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-white">R$ {{ number_format($commissionBaseForBooker, 2, ',', '.') }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            Comissão
                            @if($gig->booker_commission_type === 'percent' && $gig->booker_commission_rate)
                                ({{ number_format($gig->booker_commission_rate, 1) }}%):
                            @elseif($gig->booker_commission_type === 'fixed')
                                (Valor Fixo):
                            @else
                                Definida:
                            @endif
                        </span>
                        {{-- Exibe o valor da comissão calculado pelo accessor --}}
                        <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($bookerCommissionValueCalculated, 2, ',', '.') }}</span>
                    </div>
                </div>

                @if ($gig->booker_payment_status === 'pendente' && $bookerCommissionValueCalculated > 0.009)
                     <div class="mt-4">
                         <button type="button"
                                 @click="$dispatch('open-settle-booker-modal', {
                                     gigId: {{ $gig->id }},
                                     bookerName: '{{ addslashes($gig->booker->name ?? 'N/A') }}',
                                     commissionAmount: {{ $bookerCommissionValueCalculated }} {{-- Passa o valor CORRETO para o modal --}}
                                 })"
                                 class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                            <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento Comissão
                         </button>
                    </div>
                @elseif ($gig->booker_payment_status === 'pago')
                    <div class="mt-3 text-xs">
                        <div class="text-green-700 dark:text-green-300 mb-2">
                            <p class="font-medium"><i class="fas fa-check-circle fa-fw"></i> Comissão do booker registrada como 'Paga'.</p>
                             @if($settlement?->booker_commission_value_paid) {{-- Valor efetivamente registrado no acerto --}}
                                <p>Valor Registrado Pago: R$ {{ number_format($settlement->booker_commission_value_paid, 2, ',', '.') }}
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
                        <form action="{{ route('gigs.settlements.booker.unsettle', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja reverter o pagamento da comissão do booker para PENDENTE?');" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                                <i class="fas fa-undo-alt mr-1"></i> Reverter para Pendente
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            {{-- Se tem booker mas a comissão calculada é zero (e não está paga) --}}
            @elseif($gig->booker_id && $bookerCommissionValueCalculated <= 0.009 && $gig->booker_payment_status !== 'pago')
                <div class="p-4 rounded-md bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-200">Comissão do Booker: {{ $gig->booker->name ?? 'N/A' }}</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sem comissão definida ou valor zerado para este booker nesta gig.</p>
                </div>
            @endif
        @endif
        {{-- ============================================= --}}
        {{-- FIM Acerto com Booker                        --}}
        {{-- ============================================= --}}
    </div>
</div>