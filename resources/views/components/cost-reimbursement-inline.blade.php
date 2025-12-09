{{--
    Componente: x-cost-reimbursement-inline
    Exibe item de despesa com botões inline para ações de reembolso.
    
    Props:
    - $cost: Modelo GigCost
    - $showValue: Se true, mostra o valor (default: true)
    - $compact: Se true, mostra versão compacta sem descrição (default: false)
--}}
@props([
    'cost',
    'showValue' => true,
    'compact' => false
])

@php
    $stage = $cost->reimbursement_stage ?? 'aguardando_comprovante';
    
    $stageConfig = [
        'aguardando_comprovante' => [
            'color' => 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400',
            'label' => 'Aguardando',
            'icon' => 'clock',
        ],
        'comprovante_recebido' => [
            'color' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400',
            'label' => 'Recebido',
            'icon' => 'file-alt',
        ],
        'conferido' => [
            'color' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400',
            'label' => 'Conferido',
            'icon' => 'check-double',
        ],
        'reembolsado' => [
            'color' => 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400',
            'label' => 'Pago',
            'icon' => 'check-circle',
        ],
    ];
    
    $config = $stageConfig[$stage] ?? $stageConfig['aguardando_comprovante'];
@endphp

<div x-data="costReimbursementInline({{ $cost->id }}, '{{ $stage }}')"
     {{ $attributes->merge(['class' => 'cost-reimbursement-inline flex items-center justify-between text-xs p-2 rounded bg-gray-50 dark:bg-gray-700/50']) }}>
    
    @if(!$compact)
    <div class="flex-1 truncate pr-2">
        <span class="text-gray-700 dark:text-gray-300">{{ $cost->description ?: 'Despesa' }}</span>
        @if($showValue)
            <span class="text-gray-400 ml-1">(R$ {{ number_format($cost->value, 2, ',', '.') }})</span>
        @endif
    </div>
    @endif
    
    <div class="flex items-center gap-1">
        {{-- Badge de Status (clicável para abrir modal) --}}
        <div class="flex flex-col items-start">
            <button @click="openModal()"
                    class="px-1.5 py-0.5 rounded-full font-medium {{ $config['color'] }} hover:opacity-80 transition-opacity flex items-center gap-1 cursor-pointer"
                    title="Clique para gerenciar">
                <i class="fas fa-{{ $config['icon'] }} text-xxs"></i>
                <span>{{ $config['label'] }}</span>
            </button>
            {{-- Número do documento (se existir e estágio for >= recebido) --}}
            @if(in_array($stage, ['comprovante_recebido', 'conferido', 'reembolsado']) && $cost->reimbursement_notes)
                <span class="text-xxs text-gray-500 dark:text-gray-400 ml-1 mt-0.5 truncate max-w-[80px]" title="{{ $cost->reimbursement_notes }}">
                    {{ $cost->reimbursement_notes }}
                </span>
            @endif
        </div>
        
        {{-- Botões de ação rápida --}}
        @if($stage === 'aguardando_comprovante')
            <button @click="advanceStage('comprovante_recebido')"
                    class="p-1 rounded text-yellow-600 hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition-colors"
                    title="Registrar Recebimento">
                <i class="fas fa-file-upload text-xs"></i>
            </button>
        @elseif($stage === 'comprovante_recebido')
            <button @click="advanceStage('reembolsado')"
                    class="p-1 rounded text-green-600 hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors"
                    title="Marcar como Pago">
                <i class="fas fa-check-circle text-xs"></i>
            </button>
            <button @click="revertStage('aguardando_comprovante')"
                    class="p-1 rounded text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    title="Reverter">
                <i class="fas fa-undo text-xs"></i>
            </button>
        @elseif($stage === 'conferido')
            <button @click="advanceStage('reembolsado')"
                    class="p-1 rounded text-green-600 hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors"
                    title="Marcar como Pago">
                <i class="fas fa-check-circle text-xs"></i>
            </button>
            <button @click="revertStage('comprovante_recebido')"
                    class="p-1 rounded text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    title="Reverter">
                <i class="fas fa-undo text-xs"></i>
            </button>
        @elseif($stage === 'reembolsado')
            <span class="text-green-500 ml-1" title="Documento em posse">
                <i class="fas fa-check text-xs"></i>
            </span>
            <button @click="revertStage('comprovante_recebido')"
                    class="p-1 rounded text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                    title="Reverter">
                <i class="fas fa-undo text-xs"></i>
            </button>
        @endif
    </div>

    {{-- Modal de Detalhes/Ações --}}
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showModal = false">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    <i class="fas fa-receipt mr-2 text-primary-500"></i>Comprovante de Despesa
                </h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                {{-- Informações da despesa --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Despesa:</p>
                    <p class="font-semibold text-gray-800 dark:text-white">{{ $cost->description ?: 'Sem descrição' }}</p>
                    <p class="text-lg font-bold text-primary-600 dark:text-primary-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</p>
                </div>

                {{-- Status atual --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                    <span class="px-2 py-1 rounded-full font-semibold {{ $config['color'] }}">
                        <i class="fas fa-{{ $config['icon'] }} mr-1"></i>{{ $config['label'] }}
                    </span>
                </div>

                {{-- Seletor de tipo e número (só aguardando) --}}
                @if($stage === 'aguardando_comprovante')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Comprovante</label>
                    <select x-model="proofType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="recibo">Recibo</option>
                        <option value="nf">Nota Fiscal</option>
                        <option value="transferencia">Comprovante de Transferência</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento (opcional)</label>
                    <input type="text" x-model="proofNumber" 
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                           placeholder="Ex: NF-e 123456 ou Recibo #001">
                </div>
                @endif

                {{-- Ações --}}
                <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Ações</p>
                    
                    @if($stage === 'aguardando_comprovante')
                        <button @click="advanceStageWithType('comprovante_recebido'); showModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-yellow-500 hover:bg-yellow-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-file-upload"></i>Registrar Recebimento
                        </button>
                    @elseif($stage === 'comprovante_recebido')
                        <button @click="advanceStage('reembolsado'); showModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>Marcar como Pago
                        </button>
                        <button @click="revertStage('aguardando_comprovante'); showModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                            <i class="fas fa-undo"></i>Reverter p/ Aguardando
                        </button>
                    @elseif($stage === 'conferido')
                        <button @click="advanceStage('reembolsado'); showModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>Marcar como Pago
                        </button>
                        <button @click="revertStage('comprovante_recebido'); showModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                            <i class="fas fa-undo"></i>Voltar p/ Recebido
                        </button>
                    @elseif($stage === 'reembolsado')
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-2">
                            <i class="fas fa-check-circle text-lg"></i>
                            <span class="font-medium">Despesa Paga ✓</span>
                        </div>
                        <button @click="revertStage('comprovante_recebido'); showModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                            <i class="fas fa-undo"></i>Voltar p/ Recebido
                        </button>
                    @endif
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <button @click="showModal = false" class="w-full px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
function costReimbursementInline(costId, currentStage) {
    return {
        costId: costId,
        currentStage: currentStage,
        showModal: false,
        proofType: 'recibo',
        proofNumber: '',
        loading: false,

        openModal() {
            this.showModal = true;
        },

        async advanceStage(newStage) {
            await this.updateStage(newStage, null);
        },

        async advanceStageWithType(newStage) {
            await this.updateStage(newStage, this.proofType, this.proofNumber);
        },

        async revertStage(targetStage) {
            await this.updateStage(targetStage, null);
        },

        async updateStage(newStage, proofType, proofNumber) {
            this.loading = true;
            try {
                const body = { stage: newStage };
                if (proofType) body.proof_type = proofType;
                if (proofNumber) body.proof_number = proofNumber;
                
                // Rota para atualizar estágio de comprovante
                const response = await fetch(`/api/costs/${this.costId}/reimbursement-stage`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                
                if (response.ok) {
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Erro ao atualizar estágio');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao atualizar estágio');
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endPushOnce
