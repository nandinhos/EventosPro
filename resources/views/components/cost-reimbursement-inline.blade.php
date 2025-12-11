{{--
    Componente: x-cost-reimbursement-inline
    Exibe item de despesa com botões inline para ações de reembolso.
    Workflow simplificado: aguardando_comprovante <-> pago
    
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
    // Usa o estágio efetivo (normaliza estágios legados)
    $stage = $cost->effective_reimbursement_stage ?? 'aguardando_comprovante';
    
    // Configurações (3 estágios)
    $stageConfig = [
        'aguardando_comprovante' => [
            'color' => 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300',
            'label' => 'Aguardando',
            'icon' => 'clock',
        ],
        'anexo_pendente' => [
            'color' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400',
            'label' => 'Anexo Pendente',
            'icon' => 'paperclip',
        ],
        'pago' => [
            'color' => 'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400',
            'label' => 'Pago',
            'icon' => 'check-circle',
        ],
    ];
    
    $config = $stageConfig[$stage] ?? $stageConfig['aguardando_comprovante'];
@endphp

<div x-data="costReimbursementInline({{ json_encode([
        'costId' => $cost->id,
        'stage' => $stage,
        'proofType' => $cost->reimbursement_proof_type,
        'proofNumber' => $cost->reimbursement_notes,
        'hasFile' => !empty($cost->reimbursement_proof_file),
        'proofFileUrl' => $cost->reimbursement_proof_file ? Storage::url($cost->reimbursement_proof_file) : null,
    ]) }})"
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
        {{-- Badge de Status (clicável) --}}
        <div class="flex flex-col items-start">
            <button @click="openModal()"
                    class="px-1.5 py-0.5 rounded-full font-medium {{ $config['color'] }} hover:opacity-80 transition-opacity flex items-center gap-1 cursor-pointer"
                    title="Clique para gerenciar">
                <i class="fas fa-{{ $config['icon'] }} text-xxs"></i>
                <span>{{ $config['label'] }}</span>
            </button>
            {{-- Número do documento com label dinâmico --}}
            <template x-if="currentStage === 'pago' && proofNumber">
                <span class="text-xxs text-gray-500 dark:text-gray-400 ml-1 mt-0.5 truncate max-w-[100px]" :title="proofNumber">
                    <span class="font-medium" x-text="getProofTypeLabel()"></span> <span x-text="proofNumber"></span>
                </span>
            </template>
        </div>
        
        {{-- Botões de ação rápida (simplificado: 2 estágios) --}}
        @if($stage === 'aguardando_comprovante')
            <button @click="advanceStage('pago')"
                    class="p-1 rounded text-green-600 hover:bg-green-100 dark:hover:bg-green-900/40 transition-colors"
                    title="Marcar como Pago">
                <i class="fas fa-check-circle text-xs"></i>
            </button>
        @elseif($stage === 'pago')
            <span class="text-green-500 ml-1" title="Comprovante OK">
                <i class="fas fa-check text-xs"></i>
            </span>
            <button @click="revertStage('aguardando_comprovante')"
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

                {{-- Status atual (dinâmico) --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                    <template x-if="currentStage === 'aguardando_comprovante'">
                        <span class="px-2 py-1 rounded-full font-semibold bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300">
                            <i class="fas fa-clock mr-1"></i>Aguardando
                        </span>
                    </template>
                    <template x-if="currentStage === 'pago'">
                        <span class="px-2 py-1 rounded-full font-semibold bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle mr-1"></i>Pago
                        </span>
                    </template>
                </div>

                {{-- Campos de Comprovante (sempre visíveis) --}}
                <div class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Dados do Comprovante</p>
                    
                    {{-- Tipo de Comprovante --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Comprovante</label>
                        <select x-model="proofType" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">-- Selecione (opcional) --</option>
                            <option value="nf" {{ $cost->reimbursement_proof_type === 'nf' ? 'selected' : '' }}>Nota Fiscal</option>
                            <option value="recibo" {{ $cost->reimbursement_proof_type === 'recibo' ? 'selected' : '' }}>Recibo</option>
                            <option value="transferencia" {{ $cost->reimbursement_proof_type === 'transferencia' ? 'selected' : '' }}>Comprovante de Transferência</option>
                            <option value="outro" {{ $cost->reimbursement_proof_type === 'outro' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>
                    
                    {{-- Número do Documento --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento</label>
                        <input type="text" x-model="proofNumber" 
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="Ex: NF-e 123456 ou Recibo #001">
                    </div>
                    
                    {{-- Arquivo Anexo (dinâmico com Alpine) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arquivo do Comprovante</label>
                        
                        {{-- Se TEM arquivo: mostrar link + botão remover --}}
                        <div x-show="hasFile" x-cloak class="flex items-center justify-between gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800 mb-2">
                            <a :href="proofFileUrl" 
                               target="_blank"
                               class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 hover:underline">
                                <i class="fas fa-file-alt"></i> Ver comprovante
                            </a>
                            <button type="button" @click="removeProofFile()" 
                                    :disabled="loading"
                                    class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 flex items-center gap-1 disabled:opacity-50"
                                    title="Remover arquivo">
                                <i class="fas fa-trash-alt"></i> Remover
                            </button>
                        </div>
                        
                        {{-- Se NÃO TEM arquivo: mostrar input de upload --}}
                        <div x-show="!hasFile" x-cloak>
                            <input type="file" x-ref="proofFile"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full text-sm text-gray-500 dark:text-gray-400 
                                          file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 
                                          file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 
                                          dark:file:bg-primary-900/30 dark:file:text-primary-400
                                          hover:file:bg-primary-100 dark:hover:file:bg-primary-900/50
                                          cursor-pointer">
                            <p class="text-xs text-gray-400 mt-1">PDF, JPG ou PNG (opcional)</p>
                        </div>
                    </div>
                </div>

                {{-- Ações --}}
                <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Ações</p>
                    
                    {{-- Estado: AGUARDANDO COMPROVANTE --}}
                    <template x-if="currentStage === 'aguardando_comprovante'">
                        <div>
                            <button @click="saveAndAdvance('pago')"
                                    :disabled="loading"
                                    class="w-full px-4 py-2.5 text-sm rounded-lg bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2 disabled:opacity-50 shadow-sm">
                                <i class="fas fa-check-circle"></i>
                                <span x-text="loading ? 'Salvando...' : 'Marcar como Pago'"></span>
                            </button>
                        </div>
                    </template>
                    
                    {{-- Estado: PAGO --}}
                    <template x-if="currentStage === 'pago'">
                        <div class="space-y-3">
                            {{-- Resumo do comprovante salvo --}}
                            <div class="flex flex-col items-start gap-1 text-green-600 dark:text-green-400 py-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-check-circle text-lg"></i>
                                    <span class="font-medium">Reembolsado ✓</span>
                                </div>
                                {{-- Número do documento com label dinâmico --}}
                                <template x-if="proofNumber">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                                        <span class="font-medium" x-text="getProofTypeLabel()"></span> <span x-text="proofNumber"></span>
                                    </span>
                                </template>
                                {{-- Link do arquivo --}}
                                <template x-if="hasFile">
                                    <a :href="proofFileUrl" 
                                       target="_blank"
                                       class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 ml-6 flex items-center gap-1 hover:underline">
                                        <i class="fas fa-file-alt"></i> Ver comprovante
                                    </a>
                                </template>
                            </div>
                            
                            {{-- Botão Salvar Alterações --}}
                            <button @click="saveProofData()"
                                    :disabled="loading"
                                    class="w-full px-4 py-2 text-sm rounded-lg bg-primary-500 hover:bg-primary-600 text-white flex items-center justify-center gap-2 disabled:opacity-50 shadow-sm">
                                <i class="fas fa-save"></i>
                                <span x-text="loading ? 'Salvando...' : 'Salvar Comprovante'"></span>
                            </button>
                            
                            {{-- Botão Reverter --}}
                            <button @click="revertStage('aguardando_comprovante')"
                                    :disabled="loading"
                                    class="w-full px-4 py-2 text-sm rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2 disabled:opacity-50">
                                <i class="fas fa-undo"></i>Reverter p/ Aguardando
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <button @click="showModal = false" class="w-full px-4 py-2 text-sm rounded-lg bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
function costReimbursementInline(config) {
    return {
        costId: config.costId,
        currentStage: config.stage,
        showModal: false,
        proofType: config.proofType || '',
        proofNumber: config.proofNumber || '',
        hasFile: config.hasFile || false,
        proofFileUrl: config.proofFileUrl || null,
        loading: false,
        
        getProofTypeLabel() {
            const types = {
                'nf': 'Nº NF:',
                'recibo': 'Nº Recibo:',
                'transferencia': 'Nº Transf.:',
                'outro': 'Nº Doc.:'
            };
            return types[this.proofType] || 'Nº Doc.:';
        },

        openModal() {
            this.showModal = true;
        },

        async advanceStage(newStage) {
            await this.updateStage(newStage, null);
        },

        async saveAndAdvance(newStage) {
            await this.updateStageWithFile(newStage);
        },

        async saveProofData() {
            // Salva apenas os dados do comprovante sem mudar o estágio
            await this.updateStageWithFile(this.currentStage);
        },

        async revertStage(targetStage) {
            const confirmed = await this.showConfirm(
                'Reverter Pagamento',
                'Deseja reverter? Todos os dados do comprovante serão limpos.',
                'Sim, reverter'
            );
            if (!confirmed) return;
            await this.updateStage(targetStage, null);
        },
        
        async showConfirm(title, text, confirmText) {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonText: 'Cancelar',
                    confirmButtonText: confirmText
                });
                return result.isConfirmed;
            }
            return confirm(text);
        },

        async updateStage(newStage, proofType, proofNumber) {
            this.loading = true;
            try {
                const body = { stage: newStage };
                if (proofType) body.proof_type = proofType;
                if (proofNumber) body.proof_number = proofNumber;
                
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
                    // Atualiza estado local
                    this.currentStage = newStage;
                    if (newStage === 'aguardando_comprovante') {
                        // Reset ao reverter
                        this.proofType = '';
                        this.proofNumber = '';
                        this.hasFile = false;
                        this.proofFileUrl = null;
                    }
                    this.showSuccess(newStage === 'pago' ? 'Pagamento registrado!' : 'Pagamento revertido!');
                    this.showModal = false;
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao atualizar estágio');
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showError('Erro ao atualizar estágio');
            } finally {
                this.loading = false;
            }
        },

        async updateStageWithFile(newStage) {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('stage', newStage);
                formData.append('_method', 'PATCH');
                
                if (this.proofType) formData.append('proof_type', this.proofType);
                if (this.proofNumber) formData.append('proof_number', this.proofNumber);
                
                // Verificar se há arquivo para upload
                const fileInput = this.$refs.proofFile;
                const hasNewFile = fileInput && fileInput.files && fileInput.files[0];
                if (hasNewFile) {
                    formData.append('proof_file', fileInput.files[0]);
                }
                
                const response = await fetch(`/api/costs/${this.costId}/reimbursement-stage`, {
                    method: 'POST', // POST com _method=PATCH para suportar FormData
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const updatedCost = data.cost;
                    
                    // Atualiza estado local reativamente
                    this.currentStage = newStage;
                    
                    // Atualiza dados do arquivo se foi anexado
                    if (hasNewFile && updatedCost.reimbursement_proof_file) {
                        this.hasFile = true;
                        this.proofFileUrl = `/storage/${updatedCost.reimbursement_proof_file}`;
                    }
                    
                    this.showSuccess('Comprovante salvo com sucesso!');
                    this.showModal = false;
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao salvar comprovante');
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showError('Erro ao salvar comprovante');
            } finally {
                this.loading = false;
            }
        },
        
        async removeProofFile() {
            const confirmed = await this.showConfirm(
                'Remover Comprovante',
                'Deseja remover o arquivo anexado?',
                'Sim, remover'
            );
            if (!confirmed) return;
            
            this.loading = true;
            try {
                const response = await fetch(`/api/costs/${this.costId}/remove-proof-file`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.hasFile = false;
                    this.proofFileUrl = null;
                    this.showSuccess('Arquivo removido!');
                } else {
                    const data = await response.json();
                    this.showError(data.message || 'Erro ao remover arquivo');
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showError('Erro ao remover arquivo');
            } finally {
                this.loading = false;
            }
        },
        
        showSuccess(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Sucesso!', text: message, timer: 2000, showConfirmButton: false });
            } else {
                alert(message);
            }
        },
        
        showError(message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire('Erro', message, 'error');
            } else {
                alert(message);
            }
        }
    };
}
</script>
@endPushOnce

