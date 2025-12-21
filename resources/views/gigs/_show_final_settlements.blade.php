@props([
    'gig',
    'settlement',
    'calculatedGrossCashBrl',
    'calculatedAgencyGrossCommissionBrl',
    'calculatedArtistNetPayoutBrl',
    'calculatedBookerCommissionBrl',
    'calculatedArtistInvoiceValueBrl',
    'backUrlParams',
    'calculatedTotalConfirmedExpensesBrl' // Adicionado conforme nossa última discussão
])
{{--
    Exibe e permite gerenciar os acertos finais (pagamentos efetuados ao Artista e Booker).
--}}

@php
    // Definir dados do workflow logo no início para uso no cabeçalho
    $workflowStageGlobal = $settlement->settlement_stage ?? 'aguardando_conferencia';
    $requiresNdGlobal = $settlement->requires_debit_note ?? false;
    $hasNdGlobal = $gig->hasDebitNote();
    
    // Override para Ag. ND
    $displayStageGlobal = $workflowStageGlobal;
    if ($workflowStageGlobal === 'pago' && $requiresNdGlobal && !$hasNdGlobal) {
        $displayStageGlobal = 'aguardando_nd';
    }
    
    $workflowColorsGlobal = [
        'aguardando_conferencia' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'fechamento_enviado' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        'documentacao_recebida' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        'pago' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        'aguardando_nd' => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    ];
    $workflowIconsGlobal = [
        'aguardando_conferencia' => 'clipboard-check',
        'fechamento_enviado' => 'paper-plane',
        'documentacao_recebida' => 'file-invoice',
        'pago' => 'check-circle',
        'aguardando_nd' => 'file-invoice-dollar',
    ];
    $workflowLabelsGlobal = [
        'aguardando_conferencia' => 'Aguardando Conferência',
        'fechamento_enviado' => 'Ag. NF/Recibo',
        'documentacao_recebida' => 'Pronto p/ Pagar',
        'pago' => 'Pago',
        'aguardando_nd' => 'Ag. ND',
    ];
@endphp

<div @costs-updated.window="window.location.reload()"
    class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Acertos Financeiros (Pagamentos Efetuados)</h3>
        <div class="flex items-center gap-2">
            {{-- Botão Prévia de Fechamento --}}
            <a href="{{ route('gigs.request-nf', ['gig' => $gig] + ($backUrlParams ?? [])) }}"
               class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors flex items-center gap-1">
                <i class="fas fa-file-invoice"></i> Prévia de Fechamento
            </a>
            {{-- Badge de Status do Workflow no Header --}}
            @if($gig->artist_id)
                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $workflowColorsGlobal[$displayStageGlobal] }}" title="Status do fechamento com artista">
                    <i class="fas fa-{{ $workflowIconsGlobal[$displayStageGlobal] }} mr-1"></i>{{ $workflowLabelsGlobal[$displayStageGlobal] }}
                </span>
            @endif
        </div>
    </div>

    <div class="p-6 space-y-6 text-sm">

        {{-- ============================================= --}}
        {{-- Seção: Acerto com Artista                     --}}
        {{-- ============================================= --}}
        @php
            $workflowStage = $gig->settlement?->settlement_stage ?? 'aguardando_conferencia';
            $isPago = $workflowStage === 'pago';
        @endphp
        <div class="p-4 rounded-md {{ $isPago ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700/50' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700/50' }}">
            <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200">
                    Pagamento do Artista: <span class="text-primary-600 dark:text-primary-400">{{ $gig->artist->name ?? 'N/A' }}</span>
                </h4>
                <x-workflow-badge :gig="$gig" />
            </div>

            <div class="space-y-2 text-sm">
                {{-- Valor do Contrato (Original BRL) --}}
                <div class="flex items-center justify-between py-1">
                    <span class="text-gray-600 dark:text-gray-400">Valor Contrato ({{ $gig->currency }}):</span>
                    <span class="font-semibold text-gray-800 dark:text-white">
                        {{ $gig->currency }} {{ number_format($gig->cache_value ?? 0, 2, ',', '.') }}
                        @if($gig->currency !== 'BRL' && $gig->cache_value_brl) {{-- Adicionado verificação se cache_value_brl existe --}}
                            (aprox. R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }})
                        @endif
                    </span>
                </div>

                {{-- Total de TODAS as Despesas Confirmadas --}}
                <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
                    <span class="text-gray-600 dark:text-gray-400 block mb-1 font-medium">(-) Total Despesas Confirmadas (Dedutíveis da Base):</span>
                    @if($calculatedTotalConfirmedExpensesBrl > 0)
                        <div class="pl-4 space-y-1">
                            @php
                                // Carregar os custos apenas uma vez se ainda não estiverem carregados e ordenados
                                $confirmedCostsGrouped = $gig->gigCosts()
                                    ->where('is_confirmed', true)
                                    ->with('costCenter') // Eager load para evitar N+1
                                    ->get()
                                    ->groupBy(function($cost) {
                                        return $cost->costCenter->name ?? 'Outras Despesas';
                                    });
                            @endphp
                            @forelse($confirmedCostsGrouped as $costCenterName => $costsInGroup)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $costCenterName }}:</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">R$ {{ number_format($costsInGroup->sum('value'), 2, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500 dark:text-gray-400">Nenhuma despesa confirmada individualmente listada.</p>
                            @endforelse
                            <div class="flex items-center justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Total Geral Despesas Confirmadas:</span>
                                <span class="text-red-600 dark:text-red-400">R$ {{ number_format($calculatedTotalConfirmedExpensesBrl, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @else
                        <span class="font-semibold text-gray-800 dark:text-white ml-2">R$ 0,00</span>
                    @endif
                </div>

                {{-- Cachê Bruto (Base para Comissões) --}}
                <div class="flex items-center justify-between py-1 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-2 -mx-2 rounded">
                    <span class="text-gray-700 dark:text-gray-300">= Cachê Bruto (Base para Comissões):</span>
                    <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($calculatedGrossCashBrl, 2, ',', '.') }}</span>
                </div>

                {{-- Comissão Agência (calculada sobre o Cachê Bruto acima) --}}
                <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
                     <span class="text-gray-600 dark:text-gray-400 block mb-1 font-medium">(-) Comissão Bruta da Agência:</span>
                     <div class="pl-4 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">
                                @if(strtoupper($gig->agency_commission_type ?? '') === 'PERCENT' && isset($gig->agency_commission_rate))
                                    {{ number_format($gig->agency_commission_rate, 1) }}% sobre R$ {{ number_format($calculatedGrossCashBrl, 2, ',', '.') }}
                                @elseif(strtoupper($gig->agency_commission_type ?? '') === 'FIXED')
                                    Valor Fixo
                                @else
                                     {{ $gig->agency_commission_type ?? 'Não definido' }}
                                @endif
                            </span>
                            <span class="font-medium text-red-600 dark:text-red-400">R$ {{ number_format($calculatedAgencyGrossCommissionBrl, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Cachê Líquido do Artista (ANTES de reembolsos) --}}
                 <div class="flex items-center justify-between py-1 border-t border-gray-100 dark:border-gray-700/50">
                    <span class="text-gray-600 dark:text-gray-400">= Cachê Líquido do Artista (Base NF):</span>
                    <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($calculatedArtistNetPayoutBrl, 2, ',', '.') }}</span>
                </div>

                {{-- Despesas Pagas pelo Artista (Reembolsáveis, is_invoice = true) --}}
                @php
                    $despesasReembolsaveisAoArtista = $gig->gigCosts->where('is_confirmed', true)->where('is_invoice', true);
                    $totalDespesasReembolsaveisAoArtista = $despesasReembolsaveisAoArtista->sum('value');
                @endphp
                @if($despesasReembolsaveisAoArtista->isNotEmpty())
                    <div class="py-2 border-t border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-400 block mb-1 font-medium">(+) Reembolso Despesas Pagas pelo Artista:</span>
                        <div class="pl-4 space-y-1">
                            @foreach($despesasReembolsaveisAoArtista->groupBy('costCenter.name') as $costCenterName => $costsInGroup)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $costCenterName ?: 'Outras Despesas' }}:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($costsInGroup->sum('value'), 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                             <div class="flex items-center justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Total Reembolsável ao Artista:</span>
                                <span class="text-green-600 dark:text-green-400">R$ {{ number_format($totalDespesasReembolsaveisAoArtista, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif {{-- Fim do if($despesasReembolsaveisAoArtista->isNotEmpty()) --}}

                {{-- Valor Líquido Final para Nota Fiscal do Artista --}}
                <div class="flex items-center justify-between py-2 mt-2 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-2 -mx-2 rounded">
                    <span class="font-medium text-gray-700 dark:text-gray-300">= Valor Final Artista:</span>
                    <span class="font-bold text-lg text-primary-600 dark:text-primary-400">R$ {{ number_format($calculatedArtistInvoiceValueBrl, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Botões de Ação para pagamento ao artista --}}
            {{-- Botões de Ação do Workflow - Usando Componente Centralizado --}}
            <div class="mt-4">
                <x-settlement-workflow-actions :gig="$gig" />
            </div>

            {{-- Informações de pagamento quando pago --}}
            @if ($workflowStage === 'pago')
                @php
                    $requiresNdPago = $settlement?->requires_debit_note ?? false;
                    $hasNdPago = $gig->hasDebitNote();
                    $isCompletePago = !$requiresNdPago || $hasNdPago;
                @endphp
                <div class="mt-3 text-xs">
                    @if($isCompletePago)
                        <div class="text-green-700 dark:text-green-300">
                            @if($settlement?->artist_payment_value)
                                <p><i class="fas fa-check-circle fa-fw"></i> Valor Registrado: R$ {{ number_format($settlement->artist_payment_value, 2, ',', '.') }}
                                    @if($settlement->artist_payment_paid_at)
                                     em {{ $settlement->artist_payment_paid_at?->isoFormat('L') }}
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
                    @else
                        <div class="text-orange-700 dark:text-orange-300">
                            <p class="text-gray-600 dark:text-gray-400">Pagamento realizado, mas a Nota de Débito ainda não foi gerada.</p>
                            @if($settlement?->artist_payment_value)
                                <p class="mt-1">Valor Pago: R$ {{ number_format($settlement->artist_payment_value, 2, ',', '.') }}
                                    @if($settlement->artist_payment_paid_at)
                                     em {{ $settlement->artist_payment_paid_at?->isoFormat('L') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
        {{-- FIM Acerto com Artista --}}


        {{-- ============================================= --}}
        {{-- Seção: Acerto com Booker                     --}}
        {{-- ============================================= --}}
        @if($gig->booker_id)
            @if( $calculatedBookerCommissionBrl > 0.009 || $gig->booker_payment_status === 'pago' )
            <div class="p-4 rounded-md {{ $gig->booker_payment_status === 'pago' ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700/50' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700/50' }}">
                <div class="flex flex-wrap justify-between items-center mb-2 gap-2">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-200">
                        Comissão do Booker: <span class="text-primary-600 dark:text-primary-400">{{ $gig->booker->name ?? 'N/A' }}</span>
                    </h4>
                    <x-status-badge :status="$gig->booker_payment_status" type="payment-internal" />
                </div>
                <div class="space-y-1">
                    <div class="flex items-center justify-between py-1">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Base de Cálculo (Cachê Bruto BRL):</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-white">R$ {{ number_format($calculatedGrossCashBrl, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            Comissão
                            @if(strtoupper($gig->booker_commission_type ?? '') === 'PERCENT' && isset($gig->booker_commission_rate))
                                ({{ number_format($gig->booker_commission_rate, 1) }}%):
                            @elseif(strtoupper($gig->booker_commission_type ?? '') === 'FIXED')
                                (Valor Fixo):
                            @else
                                 {{ $gig->booker_commission_type ?? 'Não definido' }}:
                            @endif
                        </span>
                        <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($calculatedBookerCommissionBrl, 2, ',', '.') }}</span>
                    </div>
                </div>
                {{-- Botões para Booker --}}
                @if ($gig->booker_payment_status === 'pendente' && $calculatedBookerCommissionBrl > 0.009)
                     <div class="mt-4">
                         <button type="button"
                                 @click="$dispatch('open-settle-booker-modal', {
                                     gigId: {{ $gig->id }},
                                     bookerName: '{{ addslashes($gig->booker->name ?? 'N/A') }}',
                                     commissionAmount: {{ $calculatedBookerCommissionBrl }}
                                 })"
                                 class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                            <i class="fas fa-hand-holding-usd mr-2"></i> Registrar Pagamento Comissão
                         </button>
                     </div>
                @elseif ($gig->booker_payment_status === 'pago')
                    <div class="mt-3 text-xs">
                        <div class="text-green-700 dark:text-green-300 mb-2">
                             <p class="font-medium"><i class="fas fa-check-circle fa-fw"></i> Comissão do booker registrada como 'Paga'.</p>
                             @if($settlement?->booker_commission_value_paid)
                                <p>Valor Registrado Pago: R$ {{ number_format($settlement->booker_commission_value_paid, 2, ',', '.') }}
                                    @if($settlement->booker_commission_paid_at)
                                     em {{ $settlement->booker_commission_paid_at?->isoFormat('L') }}
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
                @endif {{-- Fim do if ($gig->booker_payment_status === 'pendente' ...) --}}
            </div>
            @elseif($gig->booker_id && $calculatedBookerCommissionBrl <= 0.009 && $gig->booker_payment_status !== 'pago')
                <div class="p-4 rounded-md bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-700">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-200">Comissão do Booker: {{ $gig->booker->name ?? 'N/A' }}</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sem comissão definida ou valor zerado para este booker nesta gig.</p>
                </div>
            @endif {{-- Fim do if( $calculatedBookerCommissionBrl > 0.009 ... ) --}}
        @endif {{-- Fim do if($gig->booker_id) --}}
        {{-- FIM Acerto com Booker --}}
    </div>
</div>