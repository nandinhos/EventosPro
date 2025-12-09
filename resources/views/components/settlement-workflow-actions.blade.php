{{--
    Componente: x-settlement-workflow-actions
    Exibe timeline do workflow de fechamento com botões de ação para avançar estágios.
    
    Props:
    - $gig: Modelo Gig
    - $stage: Estágio atual do settlement (opcional, usa settlement->settlement_stage se não fornecido)
    - $compact: Se true, mostra versão compacta (default: false)
--}}
@props([
    'gig',
    'stage' => null,
    'compact' => false
])

@php
    $currentStage = $stage ?? ($gig->settlement?->settlement_stage ?? 'aguardando_conferencia');
    $stageOrder = ['aguardando_conferencia', 'fechamento_enviado', 'documentacao_recebida', 'pago'];
    $currentIndex = array_search($currentStage, $stageOrder);
    
    $stageConfig = [
        'aguardando_conferencia' => [
            'label' => 'Conferência',
            'icon' => 'clipboard-check',
            'action_label' => 'Enviar Fechamento',
            'action_icon' => 'paper-plane',
            'action_color' => 'blue',
        ],
        'fechamento_enviado' => [
            'label' => 'Aguardando NF/Recibo',
            'icon' => 'paper-plane',
            'action_label' => 'Registrar NF/Recibo',
            'action_icon' => 'file-upload',
            'action_color' => 'yellow',
        ],
        'documentacao_recebida' => [
            'label' => 'Pronto para Pagar',
            'icon' => 'file-invoice',
            'action_label' => 'Confirmar Pagamento',
            'action_icon' => 'check-circle',
            'action_color' => 'green',
        ],
        'pago' => [
            'label' => 'Pago',
            'icon' => 'check-circle',
            'action_label' => null,
            'action_icon' => null,
            'action_color' => null,
        ],
    ];
@endphp

<div x-data="settlementWorkflowActions({{ $gig->id }}, '{{ $currentStage }}')" 
     {{ $attributes->merge(['class' => 'settlement-workflow-actions']) }}>
    
    @if(!$compact)
        {{-- Timeline completa --}}
        <div class="space-y-3">
            @foreach($stageConfig as $s => $config)
                @php
                    $sIndex = array_search($s, $stageOrder);
                    $isCompleted = $sIndex < $currentIndex;
                    $isCurrent = $s === $currentStage;
                @endphp
                <div class="flex items-center gap-3 text-xs">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $isCompleted ? 'bg-green-500 text-white' : ($isCurrent ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-400') }}">
                        <i class="fas fa-{{ $isCompleted ? 'check' : $config['icon'] }} text-xs"></i>
                    </div>
                    <span class="flex-1 {{ $isCurrent ? 'font-bold text-gray-800 dark:text-white' : ($isCompleted ? 'text-green-600 dark:text-green-400' : 'text-gray-400') }}">
                        {{ $config['label'] }}
                        @if($isCurrent) <span class="text-blue-500">(atual)</span> @endif
                        {{-- Mostrar número da NF/Recibo quando o estágio de documentação estiver concluído --}}
                        @if($s === 'fechamento_enviado' && $isCompleted && $gig->settlement?->documentation_number)
                            <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-1">
                                ({{ $gig->settlement->documentation_number }})
                            </span>
                        @endif
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Botões de Ação --}}
    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 space-y-2">
        @if($currentStage === 'aguardando_conferencia')
            <button @click="openSendModal()"
                    class="w-full px-3 py-2 text-xs rounded-md bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-paper-plane"></i>Enviar Fechamento
            </button>
        @elseif($currentStage === 'fechamento_enviado')
            <button @click="openReceiveDocModal()"
                    class="w-full px-3 py-2 text-xs rounded-md bg-yellow-500 hover:bg-yellow-600 text-white flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-file-upload"></i>Registrar NF/Recibo
            </button>
            <button @click="revertStage('aguardando_conferencia')"
                    class="w-full px-3 py-2 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-undo"></i>Reverter p/ Conferência
            </button>
        @elseif($currentStage === 'documentacao_recebida')
            <button @click="confirmPayment()"
                    class="w-full px-3 py-2 text-xs rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-check-circle"></i>Confirmar Pagamento
            </button>
            <button @click="revertStage('fechamento_enviado')"
                    class="w-full px-3 py-2 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-undo"></i>Voltar p/ Ag. NF/Recibo
            </button>
        @elseif($currentStage === 'pago')
            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-2 justify-center">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="font-medium">Fechamento Concluído ✓</span>
            </div>
            <button @click="revertStage('documentacao_recebida')"
                    class="w-full px-3 py-2 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-undo"></i>Reverter Pagamento
            </button>
        @endif
    </div>

    {{-- Modal Enviar Fechamento (inline) --}}
    <div x-show="showSendModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showSendModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showSendModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showSendModal = false">
            <form @submit.prevent="submitSendSettlement()">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-paper-plane mr-2 text-blue-500"></i>Enviar Fechamento
                    </h3>
                    <button type="button" @click="showSendModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações (opcional)</label>
                        <textarea x-model="sendData.notes" rows="3" 
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                  placeholder="Observações sobre o fechamento..."></textarea>
                    </div>
                </div>
                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex gap-2 justify-end">
                    <button type="button" @click="showSendModal = false" 
                            class="px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 text-gray-700 dark:text-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="loading"
                            class="px-4 py-2 text-sm rounded-md bg-blue-500 hover:bg-blue-600 text-white flex items-center gap-2">
                        <i class="fas fa-paper-plane" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                        Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Receber Documentação (inline) --}}
    <div x-show="showReceiveDocModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showReceiveDocModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showReceiveDocModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showReceiveDocModal = false">
            <form @submit.prevent="submitReceiveDoc()">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-file-invoice mr-2 text-yellow-500"></i>Registrar NF/Recibo
                    </h3>
                    <button type="button" @click="showReceiveDocModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento *</label>
                        <select x-model="receiveDocData.documentation_type" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Selecione...</option>
                            <option value="nf">Nota Fiscal</option>
                            <option value="recibo">Recibo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento</label>
                        <input type="text" x-model="receiveDocData.documentation_number"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                               placeholder="Ex: NF-e 123456">
                    </div>
                </div>
                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex gap-2 justify-end">
                    <button type="button" @click="showReceiveDocModal = false" 
                            class="px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 text-gray-700 dark:text-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="loading"
                            class="px-4 py-2 text-sm rounded-md bg-yellow-500 hover:bg-yellow-600 text-white flex items-center gap-2">
                        <i class="fas fa-check" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                        Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Confirmar Pagamento (inline) --}}
    <div x-show="showPaymentModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showPaymentModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showPaymentModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showPaymentModal = false">
            <form @submit.prevent="submitPayment()">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-check-circle mr-2 text-green-500"></i>Confirmar Pagamento
                    </h3>
                    <button type="button" @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento *</label>
                        <input type="date" x-model="paymentData.payment_date" required
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observações</label>
                        <textarea x-model="paymentData.notes" rows="2"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                  placeholder="Observações..."></textarea>
                    </div>
                </div>
                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex gap-2 justify-end">
                    <button type="button" @click="showPaymentModal = false" 
                            class="px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 text-gray-700 dark:text-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="loading"
                            class="px-4 py-2 text-sm rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center gap-2">
                        <i class="fas fa-check" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
function settlementWorkflowActions(gigId, currentStage) {
    return {
        gigId: gigId,
        currentStage: currentStage,
        loading: false,
        showSendModal: false,
        showReceiveDocModal: false,
        showPaymentModal: false,
        sendData: { notes: '' },
        receiveDocData: { documentation_type: '', documentation_number: '' },
        paymentData: { payment_date: new Date().toISOString().split('T')[0], notes: '' },

        init() {
            console.log('[settlementWorkflowActions] Initialized with gigId:', this.gigId, 'stage:', this.currentStage);
        },

        openSendModal() {
            this.sendData = { notes: '' };
            this.showSendModal = true;
        },

        openReceiveDocModal() {
            this.receiveDocData = { documentation_type: '', documentation_number: '' };
            this.showReceiveDocModal = true;
        },

        confirmPayment() {
            this.paymentData = { payment_date: new Date().toISOString().split('T')[0], notes: '' };
            this.showPaymentModal = true;
        },

        async submitSendSettlement() {
            if (!this.gigId) {
                console.error('[settlementWorkflowActions] gigId is undefined or null');
                alert('Erro: ID da gig não encontrado. Recarregue a página.');
                return;
            }
            this.loading = true;
            try {
                const url = `/artists-settlements/${this.gigId}/send`;
                console.log('[settlementWorkflowActions] Calling:', url);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.sendData)
                });
                
                if (response.ok) {
                    this.showSendModal = false;
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao enviar fechamento');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao enviar fechamento');
            } finally {
                this.loading = false;
            }
        },

        async submitReceiveDoc() {
            if (!this.receiveDocData.documentation_type) {
                alert('Selecione o tipo de documento');
                return;
            }
            this.loading = true;
            try {
                if (!this.gigId) {
                    console.error('[settlementWorkflowActions] gigId is undefined or null');
                    alert('Erro: ID da gig não encontrado. Recarregue a página.');
                    return;
                }
                const url = `/artists-settlements/${this.gigId}/receive-document`;
                console.log('[settlementWorkflowActions] Calling:', url);
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.receiveDocData)
                });
                
                if (response.ok) {
                    this.showReceiveDocModal = false;
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao registrar documento');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao registrar documento');
            } finally {
                this.loading = false;
            }
        },

        async submitPayment() {
            if (!this.gigId) {
                console.error('[settlementWorkflowActions] gigId is undefined or null');
                alert('Erro: ID da gig não encontrado. Recarregue a página.');
                return;
            }
            this.loading = true;
            try {
                const url = `/artists-settlements/${this.gigId}/pay`;
                console.log('[settlementWorkflowActions] Calling:', url);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.paymentData)
                });
                
                if (response.ok) {
                    this.showPaymentModal = false;
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao confirmar pagamento');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao confirmar pagamento');
            } finally {
                this.loading = false;
            }
        },

        async revertStage(targetStage) {
            if (!confirm('Tem certeza que deseja reverter o estágio?')) return;
            
            this.loading = true;
            try {
                if (!this.gigId) {
                    console.error('[settlementWorkflowActions] gigId is undefined or null');
                    alert('Erro: ID da gig não encontrado. Recarregue a página.');
                    return;
                }
                const url = `/artists-settlements/${this.gigId}/revert`;
                console.log('[settlementWorkflowActions] Calling:', url);
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ target_stage: targetStage })
                });
                
                if (response.ok) {
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao reverter estágio');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao reverter estágio');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endPushOnce
