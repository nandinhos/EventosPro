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
    
    // Check if ND is required
    $requiresNd = $gig->settlement?->requires_debit_note ?? false;
    
    // Workflow is fully completed if: pago AND (ND not required OR has ND)
    $isFullyCompleted = ($currentStage === 'pago' && (!$requiresNd || $gig->hasDebitNote()));
    
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
    
    {{-- Card container com fundo consistente --}}
    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
            <i class="fas fa-tasks mr-1"></i>Histórico do Fechamento
        </h4>
        
    @if(!$compact)
        {{-- Timeline completa --}}
        <div class="space-y-3">
            @foreach($stageConfig as $s => $config)
                @php
                    $sIndex = array_search($s, $stageOrder);
                    // Mark pago as completed when debit note exists
                    $isCompleted = $sIndex < $currentIndex || ($s === 'pago' && $isFullyCompleted);
                    // Only mark as current if not fully completed
                    $isCurrent = $s === $currentStage && !$isFullyCompleted;
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
            @if($gig->hasDebitNote())
                {{-- Note generated - Fechamento Concluído --}}
                <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-2 justify-center">
                    <i class="fas fa-check-circle text-lg"></i>
                    <span class="font-medium">Fechamento Concluído ✓</span>
                </div>
                <a href="{{ route('debit-notes.show', $gig) }}" target="_blank"
                   class="w-full px-3 py-2 text-xs rounded-md bg-indigo-500 hover:bg-indigo-600 text-white flex items-center justify-center gap-2 transition-colors">
                    <i class="fas fa-file-invoice-dollar"></i>Reimprimir Nota de Débito
                    <span class="text-[10px] opacity-80">({{ $gig->debitNote->number }})</span>
                </a>
                @if($gig->hasAnyDebitNotes() && $gig->debitNotes->count() > 1)
                    <button @click="openHistoryModal()"
                            class="w-full px-3 py-2 text-xs rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors">
                        <i class="fas fa-history"></i>Histórico de Notas
                    </button>
                @endif
            @elseif(!$requiresNd)
                {{-- ND dispensada - Fechamento Concluído --}}
                <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-2 justify-center">
                    <i class="fas fa-check-circle text-lg"></i>
                    <span class="font-medium">Fechamento Concluído ✓</span>
                </div>
                <button type="button" @click="toggleRequiresNd()"
                        :disabled="loading"
                        class="w-full px-3 py-2 text-xs rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors disabled:opacity-50">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-undo'"></i>Exigir Nota de Débito
                </button>
            @else
                {{-- Requires ND but doesn't have one --}}
                <div class="flex items-center gap-2 text-orange-600 dark:text-orange-400 py-2 justify-center">
                    <i class="fas fa-file-invoice-dollar text-lg"></i>
                    <span class="font-medium">Aguardando Nota de Débito</span>
                </div>
                @if($gig->serviceTaker)
                    <form action="{{ route('debit-notes.generate', $gig) }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" 
                                class="w-full px-3 py-2 text-xs rounded-md bg-indigo-500 hover:bg-indigo-600 text-white flex items-center justify-center gap-2 transition-colors">
                            <i class="fas fa-file-invoice-dollar"></i>Gerar Nota de Débito
                        </button>
                    </form>
                @else
                    <button type="button" @click="openServiceTakerModal()"
                       class="w-full px-3 py-2 text-xs rounded-md bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 hover:bg-yellow-200 dark:hover:bg-yellow-900/50 flex items-center justify-center gap-2 transition-colors">
                        <i class="fas fa-link"></i>Vincular Tomador
                    </button>
                @endif
                <button type="button" @click="toggleRequiresNd()"
                        :disabled="loading"
                        class="w-full px-3 py-2 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors disabled:opacity-50">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-ban'"></i>Dispensar ND
                </button>
                @if($gig->hasAnyDebitNotes())
                    <button @click="openHistoryModal()"
                            class="w-full px-3 py-2 text-xs rounded-md bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center justify-center gap-2 transition-colors">
                        <i class="fas fa-history"></i>Histórico de Notas
                    </button>
                @endif
            @endif
            <button @click="openCancelModal()"
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
                               :max="new Date().toISOString().split('T')[0]"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Data deve ser hoje ou anterior</p>
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

    {{-- Modal Cancelar Nota de Débito --}}
    <div x-show="showCancelModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showCancelModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showCancelModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showCancelModal = false">
            <form @submit.prevent="submitCancel()">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                        <i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i>Reverter Pagamento
                    </h3>
                    <button type="button" @click="showCancelModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-5 space-y-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>Ao reverter o pagamento, a Nota de Débito ativa será <strong>cancelada</strong>.</p>
                        <p class="mt-2">Uma justificativa é obrigatória para registro.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Justificativa *</label>
                        <textarea x-model="cancelData.cancel_reason" rows="3" required minlength="5"
                                  class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                  placeholder="Informe o motivo da reversão..."></textarea>
                    </div>
                </div>
                <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex gap-2 justify-end">
                    <button type="button" @click="showCancelModal = false" 
                            class="px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 text-gray-700 dark:text-gray-200">
                        Voltar
                    </button>
                    <button type="submit" :disabled="loading || cancelData.cancel_reason.length < 5"
                            class="px-4 py-2 text-sm rounded-md bg-yellow-500 hover:bg-yellow-600 text-white flex items-center gap-2 disabled:opacity-50">
                        <i class="fas fa-undo" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                        Reverter e Cancelar Nota
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Histórico de Notas de Débito --}}
    <div x-show="showHistoryModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showHistoryModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showHistoryModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full mx-4 shadow-xl" @click.away="showHistoryModal = false">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    <i class="fas fa-history mr-2 text-indigo-500"></i>Histórico de Notas de Débito
                </h3>
                <button type="button" @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-5">
                <div x-show="loadingHistory" class="text-center py-8">
                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                </div>
                <div x-show="!loadingHistory" class="space-y-2 max-h-96 overflow-y-auto">
                    <template x-for="note in historyNotes" :key="note.id">
                        <div class="p-3 rounded-md border" 
                             :class="note.is_active ? 'border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-700' : 'border-gray-200 dark:border-gray-600'">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-800 dark:text-white" x-text="note.number"></span>
                                    <span x-show="note.is_active" class="ml-2 text-xs text-green-600 dark:text-green-400 font-medium">
                                        <i class="fas fa-check-circle"></i> Ativa
                                    </span>
                                    <span x-show="!note.is_active" class="ml-2 text-xs text-red-600 dark:text-red-400 font-medium">
                                        <i class="fas fa-times-circle"></i> Cancelada
                                    </span>
                                </div>
                                <span class="text-xs text-gray-500" x-text="'R$ ' + note.total"></span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span x-text="'Emitida: ' + note.issued_at"></span>
                                <template x-if="note.cancelled_at">
                                    <span class="block text-red-500" x-text="'Cancelada: ' + note.cancelled_at + ' - ' + note.cancel_reason"></span>
                                </template>
                            </div>
                            <div class="mt-2" x-show="!note.is_active">
                                <button @click="activateNote(note.id)" :disabled="loading"
                                        class="text-xs px-3 py-1 rounded bg-indigo-500 hover:bg-indigo-600 text-white">
                                    <i class="fas fa-check mr-1"></i>Ativar esta nota
                                </button>
                            </div>
                        </div>
                    </template>
                    <div x-show="historyNotes.length === 0 && !loadingHistory" class="text-center text-gray-500 py-4">
                        Nenhuma nota de débito encontrada.
                    </div>
                </div>
            </div>
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button type="button" @click="showHistoryModal = false" 
                        class="px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 text-gray-700 dark:text-gray-200">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: Selecionar Tomador de Serviço --}}
    <div x-show="showServiceTakerModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showServiceTakerModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showServiceTakerModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showServiceTakerModal = false">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    <i class="fas fa-building mr-2 text-yellow-500"></i>Vincular Tomador de Serviço
                </h3>
                <button @click="showServiceTakerModal = false" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-5 py-4 space-y-4">
                {{-- Campo de Busca --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar Tomador</label>
                    <input type="text" 
                           x-model="serviceTakerSearch"
                           @input.debounce.300ms="searchServiceTakers()"
                           placeholder="Digite para buscar..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                
                {{-- Lista de Tomadores --}}
                <div class="max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                    <div x-show="serviceTakersList.length === 0 && !serviceTakerSearch" class="p-4 text-center text-gray-500 text-sm">
                        <i class="fas fa-search mr-1"></i>Faça a busca acima para encontrar um tomador...
                    </div>
                    <template x-for="st in serviceTakersList" :key="st.id">
                        <button type="button"
                                @click="selectServiceTaker(st)"
                                :class="{'bg-primary-50 dark:bg-primary-900/30 border-l-4 border-primary-500': selectedServiceTaker?.id === st.id}"
                                class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <div class="font-medium text-gray-800 dark:text-white text-sm" x-text="st.text"></div>
                        </button>
                    </template>
                    <div x-show="!loadingServiceTakers && serviceTakersList.length === 0 && serviceTakerSearch" class="p-4 text-center text-gray-500 text-sm">
                        Nenhum tomador encontrado.
                    </div>
                </div>
                
                {{-- Selecionado --}}
                <div x-show="selectedServiceTaker" class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        <i class="fas fa-check-circle mr-1"></i> Selecionado: <span class="font-medium" x-text="selectedServiceTaker?.text"></span>
                    </p>
                </div>
            </div>
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                <button type="button" @click="showServiceTakerModal = false" 
                        class="px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 text-gray-700 dark:text-gray-200">
                    Cancelar
                </button>
                <button type="button" @click="confirmServiceTaker()" 
                        :disabled="!selectedServiceTaker || loading"
                        class="px-4 py-2 text-sm rounded-md bg-primary-500 hover:bg-primary-600 text-white disabled:opacity-50">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-check'"></i> Confirmar
                </button>
            </div>
        </div>
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
        loadingHistory: false,
        loadingServiceTakers: false,
        showSendModal: false,
        showReceiveDocModal: false,
        showPaymentModal: false,
        showCancelModal: false,
        showHistoryModal: false,
        showServiceTakerModal: false,
        sendData: { notes: '' },
        receiveDocData: { documentation_type: '', documentation_number: '' },
        paymentData: { payment_date: new Date().toISOString().split('T')[0], notes: '' },
        cancelData: { cancel_reason: '' },
        historyNotes: [],
        serviceTakerSearch: '',
        serviceTakersList: [],
        selectedServiceTaker: null,

        init() {
            console.log('[settlementWorkflowActions] Initialized with gigId:', this.gigId, 'stage:', this.currentStage);
        },

        openServiceTakerModal() {
            this.serviceTakerSearch = '';
            this.serviceTakersList = [];
            this.selectedServiceTaker = null;
            this.showServiceTakerModal = true;
        },

        async searchServiceTakers() {
            this.loadingServiceTakers = true;
            try {
                const response = await fetch(`/service-takers-list?q=${encodeURIComponent(this.serviceTakerSearch)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (response.ok) {
                    this.serviceTakersList = await response.json();
                }
            } catch (error) {
                console.error('Erro ao buscar tomadores:', error);
            } finally {
                this.loadingServiceTakers = false;
            }
        },

        selectServiceTaker(st) {
            this.selectedServiceTaker = st;
        },

        async confirmServiceTaker() {
            if (!this.selectedServiceTaker) return;
            
            this.loading = true;
            try {
                const response = await fetch(`/gigs/${this.gigId}/link-service-taker`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ service_taker_id: this.selectedServiceTaker.id })
                });
                
                if (response.ok) {
                    this.showServiceTakerModal = false;
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao vincular tomador.');
                }
            } catch (error) {
                console.error('Erro ao vincular tomador:', error);
                alert('Erro ao vincular tomador.');
            } finally {
                this.loading = false;
            }
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

        openCancelModal() {
            this.cancelData = { cancel_reason: '' };
            this.showCancelModal = true;
        },

        async openHistoryModal() {
            this.showHistoryModal = true;
            this.loadingHistory = true;
            this.historyNotes = [];
            
            try {
                const response = await fetch(`/debit-notes/${this.gigId}/history`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.historyNotes = data.notes;
                }
            } catch (error) {
                console.error('Erro ao carregar histórico:', error);
            } finally {
                this.loadingHistory = false;
            }
        },

        async submitCancel() {
            if (this.cancelData.cancel_reason.length < 5) {
                alert('A justificativa deve ter pelo menos 5 caracteres.');
                return;
            }
            
            this.loading = true;
            try {
                // First cancel the debit note
                const cancelResponse = await fetch(`/debit-notes/${this.gigId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.cancelData)
                });
                
                if (!cancelResponse.ok) {
                    const data = await cancelResponse.json();
                    alert(data.message || 'Erro ao cancelar nota de débito');
                    return;
                }
                
                // Then revert the payment stage
                const revertResponse = await fetch(`/artists-settlements/${this.gigId}/revert`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ target_stage: 'documentacao_recebida' })
                });
                
                if (revertResponse.ok) {
                    this.showCancelModal = false;
                    window.location.reload();
                } else {
                    const data = await revertResponse.json();
                    alert(data.message || 'Erro ao reverter pagamento');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar reversão');
            } finally {
                this.loading = false;
            }
        },

        async activateNote(noteId) {
            this.loading = true;
            try {
                const response = await fetch(`/debit-notes/${noteId}/activate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.showHistoryModal = false;
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao ativar nota');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao ativar nota');
            } finally {
                this.loading = false;
            }
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

        async toggleRequiresNd() {
            this.loading = true;
            try {
                const response = await fetch(`/gigs/${this.gigId}/toggle-requires-nd`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao alterar exigência de ND');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao alterar exigência de ND');
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
