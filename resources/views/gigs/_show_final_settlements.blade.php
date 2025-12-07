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
    $workflowColorsGlobal = [
        'aguardando_conferencia' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'fechamento_enviado' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        'documentacao_recebida' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        'pago' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    ];
    $workflowIconsGlobal = [
        'aguardando_conferencia' => 'clipboard-check',
        'fechamento_enviado' => 'paper-plane',
        'documentacao_recebida' => 'file-invoice',
        'pago' => 'check-circle',
    ];
    $workflowLabelsGlobal = [
        'aguardando_conferencia' => 'Aguardando Conferência',
        'fechamento_enviado' => 'Ag. NF/Recibo',
        'documentacao_recebida' => 'Pronto p/ Pagar',
        'pago' => 'Pago',
    ];
@endphp

<div x-data="settlementCardActions()" 
     x-init="gigId = {{ $gig->id }}; artistName = '{{ addslashes($gig->artist->name ?? 'N/A') }}'; amountDue = {{ $calculatedArtistInvoiceValueBrl < 0 ? 0 : $calculatedArtistInvoiceValueBrl }}"
    @costs-updated.window="$el.closest('body').querySelector('form')?.submit() || window.location.reload()"
    class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Acertos Financeiros (Pagamentos Efetuados)</h3>
        {{-- Badge de Status do Workflow no Header --}}
        @if($gig->artist_id)
            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $workflowColorsGlobal[$workflowStageGlobal] }}" title="Status do fechamento com artista">
                <i class="fas fa-{{ $workflowIconsGlobal[$workflowStageGlobal] }} mr-1"></i>{{ $workflowLabelsGlobal[$workflowStageGlobal] }}
            </span>
        @endif
    </div>

    <div class="p-6 space-y-6 text-sm">

        {{-- ============================================= --}}
        {{-- Seção: Acerto com Artista                     --}}
        {{-- ============================================= --}}
        <div class="p-4 rounded-md {{ $gig->artist_payment_status === 'pago' ? 'bg-green-50 dark:bg-green-800/20 border border-green-200 dark:border-green-700/50' : 'bg-yellow-50 dark:bg-yellow-800/20 border border-yellow-200 dark:border-yellow-700/50' }}">
            <div class="flex flex-wrap justify-between items-center mb-3 gap-2">
                <h4 class="font-semibold text-gray-700 dark:text-gray-200">
                    Pagamento do Artista: <span class="text-primary-600 dark:text-primary-400">{{ $gig->artist->name ?? 'N/A' }}</span>
                </h4>
                <x-status-badge :status="$gig->artist_payment_status" type="payment-internal" />
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
            @if ($gig->artist_payment_status === 'pendente')
                @php
                    $workflowStage = $settlement->settlement_stage ?? 'aguardando_conferencia';
                    $workflowColors = [
                        'aguardando_conferencia' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        'fechamento_enviado' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'documentacao_recebida' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                        'pago' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                    ];
                    $workflowIcons = [
                        'aguardando_conferencia' => 'clipboard-check',
                        'fechamento_enviado' => 'paper-plane',
                        'documentacao_recebida' => 'file-invoice',
                        'pago' => 'check-circle',
                    ];
                    $workflowLabels = [
                        'aguardando_conferencia' => 'Conferir',
                        'fechamento_enviado' => 'Ag. NF',
                        'documentacao_recebida' => 'Pronto',
                        'pago' => 'Pago',
                    ];
                @endphp
                <div class="mt-4">
                     <div class="flex flex-wrap items-center gap-2">
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
                             <button type="button" @click="showDocModal = true" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                                 <i class="fas fa-file-upload mr-1"></i> Registrar NF/Recibo
                             </button>
                             <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST" class="inline" onsubmit="return confirm('Reverter envio do fechamento?')">
                                 @csrf
                                 @method('PATCH')
                                 <input type="hidden" name="redirect_to" value="show">
                                 <button type="submit" class="bg-gray-400 hover:bg-gray-500 text-white px-2 py-1.5 rounded-md text-xs" title="Reverter Envio">
                                     <i class="fas fa-undo"></i>
                                 </button>
                             </form>
                         @elseif($workflowStage === 'documentacao_recebida')
                             <button type="button" @click="showPayModal = true" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                                 <i class="fas fa-dollar-sign mr-1"></i> Registrar Pagamento
                             </button>
                             <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST" class="inline" onsubmit="return confirm('Reverter registro de documentação?')">
                                 @csrf
                                 @method('PATCH')
                                 <input type="hidden" name="redirect_to" value="show">
                                 <button type="submit" class="bg-gray-400 hover:bg-gray-500 text-white px-2 py-1.5 rounded-md text-xs" title="Reverter Documentação">
                                     <i class="fas fa-undo"></i>
                                 </button>
                             </form>
                         @endif
                         
                         <a href="{{ route('gigs.request-nf', ['gig' => $gig] + ($backUrlParams ?? [])) }}"
                            class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                            <i class="fas fa-file-invoice mr-1"></i> Detalhes NF
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
            @endif {{-- Fim do if ($gig->artist_payment_status === 'pendente') --}}
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

    {{-- Modal: Registrar Documentação --}}
    <div x-show="showDocModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showDocModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDocModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showDocModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('artists.settlements.receiveDocument', $gig) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="redirect_to" value="show">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-file-invoice text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Registrar NF/Recibo</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="documentation_type_card" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento *</label>
                                    <select name="documentation_type" id="documentation_type_card" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">Selecione...</option>
                                        <option value="nf">Nota Fiscal</option>
                                        <option value="recibo">Recibo</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="documentation_number_card" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento (opcional)</label>
                                    <input type="text" name="documentation_number" id="documentation_number_card" placeholder="Ex: NF-e 123456" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label for="documentation_file_card" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arquivo (opcional)</label>
                                    <input type="file" name="documentation_file" id="documentation_file_card" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-gray-700 dark:file:text-gray-200">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF, JPG ou PNG (máx. 5MB)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none sm:w-auto sm:text-sm">
                            <i class="fas fa-check mr-2"></i>Registrar
                        </button>
                        <button type="button" @click="showDocModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal: Registrar Pagamento --}}
    <div x-show="showPayModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showPayModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showPayModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showPayModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <form action="{{ route('artists.settlements.settle', $gig) }}" method="POST">
                    @csrf
                    <input type="hidden" name="redirect_to" value="show">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-dollar-sign text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Registrar Pagamento</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Confirme o pagamento ao artista.</p>
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-md">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Valor a Pagar:</span>
                                    <span class="font-bold text-lg text-primary-600 dark:text-primary-400">R$ {{ number_format($calculatedArtistInvoiceValueBrl, 2, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="payment_date_card" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento *</label>
                                <input type="date" name="payment_date" id="payment_date_card" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:w-auto sm:text-sm">
                            <i class="fas fa-check-circle mr-2"></i>Confirmar Pagamento
                        </button>
                        <button type="button" @click="showPayModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function settlementCardActions() {
        return {
            showDocModal: false,
            showPayModal: false,
            gigId: 0,
            artistName: '',
            amountDue: 0
        };
    }
</script>