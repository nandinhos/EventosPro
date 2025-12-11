@props(['gig', 'costCenters'])

<div x-data="costsManager(
        {{ $gig->id }},
        {{ \Illuminate\Support\Js::from($costCenters) }}
    )" 
     x-init="fetchCosts()"
     class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">

    {{-- Header do Card com Totais e Botão Adicionar --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Despesas / Custos</h3>
                <button type="button" @click="openNewCostModal()" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                    <i class="fas fa-plus mr-1"></i> Adicionar Despesa
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm">
                <p class="text-gray-600 dark:text-gray-300">
                    Confirmado: <span class="font-semibold text-green-600 dark:text-green-400" x-text="formatCurrency(totalConfirmedCosts)"></span>
                </p>
                <p class="text-gray-600 dark:text-gray-300">
                    Pendente: <span class="font-semibold text-yellow-600 dark:text-yellow-400" x-text="formatCurrency(totalPendingCosts)"></span>
                </p>
            </div>
        </div>
    </div>

    {{-- Lista de Despesas Agrupadas --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <template x-if="loading">
            <div class="p-6 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Carregando despesas...</div>
        </template>
        <template x-if="!loading && costsByCenter.length === 0">
            <p class="p-6 text-sm text-gray-500 dark:text-gray-400">Nenhuma despesa registrada para esta Gig.</p>
        </template>
        
        <template x-for="group in costsByCenter" :key="group.cost_center.id">
            <div class="p-4">
                <div class="flex justify-between items-center mb-3 bg-gray-100 dark:bg-gray-700/50 p-2 rounded-md">
                    <h4 class="text-md font-medium text-gray-800 dark:text-white" x-text="group.cost_center.name"></h4>
                    <span class="text-xs text-gray-600 dark:text-gray-300">
                        Total Centro: <span class="font-medium" x-text="formatCurrency(group.total_value)"></span>
                        (<span x-text="group.count"></span> itens)
                    </span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="pb-2 text-left font-medium uppercase">Descrição</th>
                                <th class="pb-2 text-right font-medium uppercase">Valor</th>
                                <th class="pb-2 text-center font-medium uppercase">NF</th>
                                <th class="pb-2 text-center font-medium uppercase">Comprovante</th>
                                <th class="pb-2 text-center font-medium uppercase">Status</th>
                                <th class="pb-2 text-center font-medium uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <template x-for="cost in group.costs" :key="cost.id">
                                <tr :class="!cost.is_confirmed ? 'bg-yellow-50 dark:bg-yellow-900/10' : ''">
                                    <td class="py-2 px-1 whitespace-normal" x-text="cost.description || '-'"></td>
                                    <td class="py-2 px-1 whitespace-nowrap text-right" x-text="`${cost.currency} ${formatCurrency(cost.value, false)}`"></td>
                                    <td class="py-2 px-1 whitespace-nowrap text-center">
                                        <div class="flex justify-center items-center">
                                            <input type="checkbox" :checked="cost.is_invoice" @change="toggleInvoice(cost.id)" :disabled="!cost.is_confirmed"
                                                   class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 disabled:opacity-50"
                                                   :title="!cost.is_confirmed ? 'Confirme a despesa primeiro para marcar para NF' : 'Incluir/Remover da Nota Fiscal do Artista'">
                                        </div>
                                    </td>
                                    <td class="py-2 px-1 whitespace-nowrap text-center">
                                        <template x-if="cost.is_invoice">
                                            <!-- Badge clicável + número do documento (3 estágios) -->
                                            <!-- Badge clicável + número do documento (3 estágios) -->
                                            <div class="flex flex-col items-center gap-1">
                                                <button @click="openProofModal(cost)"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs transition-colors cursor-pointer"
                                                        :class="{
                                                            'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200': cost.effective_stage === 'aguardando_comprovante',
                                                            'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 hover:bg-green-200': cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente'
                                                        }">
                                                    <span x-show="cost.effective_stage === 'aguardando_comprovante'"><i class="fas fa-clock mr-1"></i>Aguard.</span>
                                                    <span x-show="cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente'"><i class="fas fa-check-circle mr-1"></i>Pago</span>
                                                </button>
                                                
                                                <!-- Badge secundário para Anexo Pendente -->
                                                <div x-show="cost.effective_stage === 'anexo_pendente' && !cost.reimbursement_notes" 
                                                     class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 border border-orange-200 dark:border-orange-800/50 cursor-pointer"
                                                     @click="openProofModal(cost)"
                                                     title="Clique para anexar comprovante">
                                                    <i class="fas fa-paperclip mr-1"></i> Pendente
                                                </div>

                                                <!-- Número do documento em itálico com prefixo -->
                                                <template x-if="cost.reimbursement_notes && (cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente')">
                                                    <span class="text-xxs text-gray-500 dark:text-gray-400 mt-0 truncate max-w-[100px] italic" 
                                                          :title="cost.reimbursement_notes" 
                                                          x-text="getProofTypeLabel(cost.reimbursement_proof_type) + ' ' + cost.reimbursement_notes"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!cost.is_invoice">
                                            <span class="text-gray-400">-</span>
                                        </template>
                                    </td>
                                    <td class="py-2 px-1 whitespace-nowrap text-center">
                                        <span x-text="cost.is_confirmed ? 'Confirmado' : 'Pendente'"
                                              :class="{
                                                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize': true,
                                                  'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200': cost.is_confirmed,
                                                  'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200': !cost.is_confirmed
                                              }">
                                        </span>
                                        <span x-show="cost.is_confirmed && cost.confirmed_by_name" class="block text-xxs text-gray-400 dark:text-gray-500" x-text="`por ${cost.confirmed_by_name} em ${cost.confirmed_at_formatted}`"></span>
                                    </td>
                                    <td class="py-2 px-1 whitespace-nowrap text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button @click="cost.is_confirmed ? unconfirmCost(cost.id) : openConfirmCostModal(cost)"
                                                    :title="cost.is_confirmed ? 'Reverter Confirmação' : 'Confirmar Despesa'"
                                                    :class="cost.is_confirmed ? 'text-yellow-500 hover:text-yellow-700' : 'text-green-500 hover:text-green-700'"
                                                    class="p-1 focus:outline-none">
                                                <i class="fas fa-fw" :class="cost.is_confirmed ? 'fa-undo-alt' : 'fa-check-circle'"></i>
                                            </button>
                                            <button @click="openEditCostModal(cost)" title="Editar Despesa" :disabled="cost.is_confirmed" class="p-1 focus:outline-none" :class="!cost.is_confirmed ? 'text-primary-500 hover:text-primary-700' : 'text-gray-400 cursor-not-allowed opacity-50'">
                                                <i class="fas fa-edit fa-fw"></i>
                                            </button>
                                            <button @click="deleteCost(cost.id)" title="Excluir Despesa" :disabled="cost.is_confirmed" class="p-1 focus:outline-none" :class="!cost.is_confirmed ? 'text-red-500 hover:text-red-700' : 'text-gray-400 cursor-not-allowed opacity-50'">
                                                <i class="fas fa-trash-alt fa-fw"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>

    {{-- Inclusão dos Modais --}}
    @include('gig_costs._confirm_modal')
    @include('gig_costs._form_modal')
    
    {{-- Modal de Gerenciamento de Comprovante --}}
    <div x-show="showProofModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         x-trap.inert.noscroll="showProofModal">
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="showProofModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-xl" @click.away="showProofModal = false">
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    <i class="fas fa-receipt mr-2 text-primary-500"></i>Gerenciar Comprovante
                </h3>
                <button @click="showProofModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-4">
                {{-- Informações da despesa --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Despesa:</p>
                    <p class="font-semibold text-gray-800 dark:text-white" x-text="proofCostData.description || 'Sem descrição'"></p>
                    <p class="text-lg font-bold text-primary-600 dark:text-primary-400" x-text="formatCurrency(proofCostData.value)"></p>
                </div>

                {{-- Status atual (dinâmico - 3 estágios) --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Status atual:</span>
                    <span x-show="proofCostData.stage === 'aguardando_comprovante'"
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                        <i class="fas fa-clock mr-1"></i>Aguardando
                    </span>
                    <span x-show="proofCostData.stage === 'anexo_pendente'"
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400">
                        <i class="fas fa-paperclip mr-1"></i>Anexo Pendente
                    </span>
                    <span x-show="proofCostData.stage === 'pago'"
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400">
                        <i class="fas fa-check-circle mr-1"></i>Pago
                    </span>
                </div>

                {{-- Campos de Comprovante (sempre visíveis, editáveis quando aguardando) --}}
                <div class="space-y-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Dados do Comprovante</p>
                    
                    {{-- Tipo de Comprovante --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Comprovante</label>
                        <select x-model="proofCostData.proof_type" 
                                :disabled="proofCostData.stage === 'pago'"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm disabled:opacity-50">
                            <option value="">-- Selecione (opcional) --</option>
                            <option value="nf">Nota Fiscal</option>
                            <option value="recibo">Recibo</option>
                            <option value="transferencia">Comprovante de Transferência</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    {{-- Número do Documento --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento (opcional)</label>
                        <input type="text" x-model="proofCostData.proof_number" 
                               :disabled="proofCostData.stage === 'pago'"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm disabled:opacity-50"
                               placeholder="Ex: NF-e 123456 ou Recibo #001">
                    </div>
                    
                    {{-- Arquivo do Comprovante --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arquivo do Comprovante</label>
                        
                        {{-- Se TEM arquivo: mostrar link + botão remover --}}
                        <div x-show="proofCostData.has_file" x-cloak class="flex items-center justify-between gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800 mb-2">
                            <a :href="proofCostData.proof_file_url" 
                               target="_blank"
                               class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 hover:underline">
                                <i class="fas fa-file-alt"></i> Ver comprovante
                            </a>
                            <button type="button" @click="removeProofFile()" 
                                    class="text-sm text-red-500 hover:text-red-700 dark:text-red-400 flex items-center gap-1"
                                    title="Remover arquivo">
                                <i class="fas fa-trash-alt"></i> Remover
                            </button>
                        </div>
                        
                        {{-- Se NÃO TEM arquivo: mostrar input de upload --}}
                        <div x-show="!proofCostData.has_file" x-cloak>
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

                {{-- Ações disponíveis --}}
                <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Ações</p>
                    
                    {{-- Estado: AGUARDANDO --}}
                    <div x-show="proofCostData.stage === 'aguardando_comprovante'" x-cloak>
                        <button @click="marcarComoPago()"
                                class="w-full px-4 py-2 text-sm rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>Marcar como Pago
                        </button>
                    </div>
                    
                    {{-- Estado: ANEXO PENDENTE --}}
                    <div x-show="proofCostData.stage === 'anexo_pendente'" x-cloak class="space-y-2">
                        <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400 py-2">
                            <i class="fas fa-exclamation-triangle text-lg"></i>
                            <span class="font-medium">Aguardando anexo do comprovante</span>
                        </div>
                        <button @click="anexarComprovante()"
                                class="w-full px-4 py-2 text-sm rounded-md bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-paperclip"></i>Anexar Comprovante
                        </button>
                        <button @click="revertToAguardando()"
                                class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                            <i class="fas fa-undo"></i>Reverter p/ Aguardando
                        </button>
                    </div>

                    {{-- Estado: PAGO --}}
                    <div x-show="proofCostData.stage === 'pago'" x-cloak class="space-y-2">
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-2">
                            <i class="fas fa-check-circle text-lg"></i>
                            <span class="font-medium">Reembolsado ✓</span>
                        </div>
                        {{-- Botão para salvar alterações (anexar arquivo posterior) --}}
                        <button @click="salvarAlteracoes()"
                                class="w-full px-4 py-2 text-sm rounded-md bg-blue-500 hover:bg-blue-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>Salvar Alterações
                        </button>
                        <button @click="revertToAguardando()"
                                class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                            <i class="fas fa-undo"></i>Reverter p/ Aguardando
                        </button>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <button @click="showProofModal = false" class="w-full px-4 py-2 text-sm rounded-md bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200">
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>


{{--
    O script para a função `costsManager` deve ser colocado na view PAI 
    (neste caso, `gigs.show.blade.php`) usando @push('scripts')
--}}
@push('scripts')
<script>
    function costsManager(gigId, costCentersInitial) {
        return {
            loading: true,
            costsByCenter: [],
            totalConfirmedCosts: 0,
            totalPendingCosts: 0,
            costCentersData: costCentersInitial,
            
            showCostFormModal: false,
            isEditMode: false,
            costFormData: {},

            showConfirmModal: false,
            confirmCostData: { id: null, description: '', date: '{{ today()->format("Y-m-d") }}' },
            
            // Modal de Comprovante
            showProofModal: false,
            proofCostData: { id: null, description: '', value: 0, stage: '', proof_type: '', proof_number: '', has_file: false, proof_file_url: null },
            
            formatCurrency(value, withSymbol = true) {
                const num = parseFloat(value);
                if (isNaN(num)) return withSymbol ? 'R$ 0,00' : '0,00';
                const options = withSymbol ? { style: 'currency', currency: 'BRL' } : { minimumFractionDigits: 2, maximumFractionDigits: 2 };
                return num.toLocaleString('pt-BR', options);
            },

            async fetchCosts() {
                this.loading = true;
                try {
                    const response = await fetch(`/gigs/${gigId}/costs-json`);
                    if (!response.ok) throw new Error('Falha ao buscar custos');
                    const data = await response.json();
                    // Adiciona effective_stage normalizado (mapeia estágios legados para 'pago')
                    data.forEach(group => {
                        (group.costs || []).forEach(cost => {
                            const legacyStages = ['comprovante_recebido', 'conferido', 'reembolsado'];
                            const stage = cost.reimbursement_stage || 'aguardando_comprovante';
                            cost.effective_stage = legacyStages.includes(stage) ? 'pago' : stage;
                        });
                    });
                    this.costsByCenter = data;
                    this.calculateTotals();
                } catch (error) { 
                    console.error('Erro buscando custos:', error);
                } finally { 
                    this.loading = false; 
                }
            },

            calculateTotals() {
                let confirmed = 0, pending = 0;
                (this.costsByCenter || []).forEach(group => {
                    (group.costs || []).forEach(cost => {
                        const costValue = parseFloat(cost.value) || 0;
                        if (cost.is_confirmed) { confirmed += costValue; } else { pending += costValue; }
                    });
                });
                this.totalConfirmedCosts = confirmed;
                this.totalPendingCosts = pending;
            },

            async performAction(url, method, body = null) {
                try {
                    const response = await fetch(url, {
                        method: method.toUpperCase(),
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: body ? JSON.stringify(body) : null
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        let errorMessage = result.message || 'Ocorreu um erro.';
                        if (response.status === 422 && result.errors) {
                            errorMessage += '\n\n- ' + Object.values(result.errors).map(err => err[0]).join('\n- ');
                        }
                        throw new Error(errorMessage);
                    }
                    await this.fetchCosts();
                    this.$dispatch('financial-data-changed', { gigId: gigId });
                } catch (error) {
                    console.error(`Erro na ação ${method} ${url}:`, error);
                    alert(error.message);
                }
            },
            
            unconfirmCost(costId) {
                if (confirm('Reverter confirmação desta despesa?')) {
                    this.performAction(`/gigs/${gigId}/costs/${costId}/unconfirm`, 'PATCH');
                }
            },
            toggleInvoice(costId) {
                this.performAction(`/gigs/${gigId}/costs/${costId}/toggle-invoice`, 'PATCH');
            },
            deleteCost(costId) {
                if (confirm('Tem certeza que deseja excluir esta despesa?')) {
                    this.performAction(`/gigs/${gigId}/costs/${costId}`, 'DELETE');
                }
            },
            
            openNewCostModal() {
                this.isEditMode = false;
                this.costFormData = { id: null, cost_center_id: '', description: '', value: '', currency: 'BRL', expense_date: '{{ today()->format("Y-m-d") }}', notes: '', is_confirmed: false, is_invoice: false };
                this.showCostFormModal = true;
                this.$nextTick(() => document.getElementById('modal_cost_center_id')?.focus());
            },
            openEditCostModal(cost) {
                this.isEditMode = true;
                this.costFormData = {
                    ...cost,
                    cost_center_id: String(cost.cost_center_id || '') 
                };
                this.showCostFormModal = true;
                this.$nextTick(() => document.getElementById('modal_cost_center_id')?.focus());
            },
            openConfirmCostModal(cost) {
                this.confirmCostData = { id: cost.id, description: cost.description || 'Despesa', date: '{{ today()->format("Y-m-d") }}' };
                this.showConfirmModal = true;
                this.$nextTick(() => document.getElementById('confirmation_date_input')?.focus());
            },

            async submitConfirmForm() {
                const url = `/gigs/${gigId}/costs/${this.confirmCostData.id}/confirm`;
                const body = { confirmed_at_date: this.confirmCostData.date };
                
                await this.performAction(url, 'PATCH', body);
                this.showConfirmModal = false;
            },
            async submitCostForm() {
                const url = this.isEditMode 
                    ? `/gigs/${gigId}/costs/${this.costFormData.id}`
                    : `/gigs/${gigId}/costs`;
                const method = this.isEditMode ? 'PUT' : 'POST';
                
                await this.performAction(url, method, this.costFormData);
                this.showCostFormModal = false;
            },

            // Método para atualizar estágio de reembolso
            async updateReimbursementStage(costId, newStage, proofType = null, proofNumber = null) {
                const body = { stage: newStage };
                if (proofType) body.proof_type = proofType;
                if (proofNumber) body.proof_number = proofNumber;
                await this.performAction(`/gigs/${gigId}/costs/${costId}/reimbursement-stage`, 'PATCH', body);
            },

            // Abre o modal de gerenciamento de comprovante
            openProofModal(cost) {
                // Normaliza estágio legado para 'pago'
                const legacyStages = ['comprovante_recebido', 'conferido', 'reembolsado'];
                const rawStage = cost.reimbursement_stage || 'aguardando_comprovante';
                const effectiveStage = legacyStages.includes(rawStage) ? 'pago' : rawStage;
                
                this.proofCostData = {
                    id: cost.id,
                    description: cost.description || 'Despesa',
                    value: cost.value || 0,
                    stage: effectiveStage,
                    proof_type: cost.reimbursement_proof_type || '',
                    proof_number: cost.reimbursement_notes || '',
                    has_file: !!cost.reimbursement_proof_file,
                    proof_file_url: cost.reimbursement_proof_file ? `/storage/${cost.reimbursement_proof_file}` : null
                };
                this.showProofModal = true;
            },
            
            // Label dinâmico para tipo de documento
            getProofTypeLabel(proofType) {
                const types = {
                    'nf': 'Nº NF:',
                    'recibo': 'Nº Recibo:',
                    'transferencia': 'Nº Transf.:',
                    'outro': 'Nº Doc.:'
                };
                return types[proofType] || 'Nº Doc.:';
            },
            
            // Marca como pago - determina estágio baseado em ter comprovante (número OU arquivo)
            async marcarComoPago() {
                const fileInput = this.$refs.proofFile;
                const hasNewFile = fileInput && fileInput.files && fileInput.files[0];
                const hasProofNumber = !!this.proofCostData.proof_number && this.proofCostData.proof_number.trim() !== '';
                
                // Se tem número OU arquivo, vai para 'pago'; senão vai para 'anexo_pendente'
                const hasComprovante = hasNewFile || hasProofNumber;
                const newStage = hasComprovante ? 'pago' : 'anexo_pendente';
                
                const formData = new FormData();
                formData.append('stage', newStage);
                formData.append('_method', 'PATCH');
                
                if (this.proofCostData.proof_type) formData.append('proof_type', this.proofCostData.proof_type);
                if (this.proofCostData.proof_number) formData.append('proof_number', this.proofCostData.proof_number);
                
                if (hasNewFile) {
                    formData.append('proof_file', fileInput.files[0]);
                }
                
                try {
                    const response = await fetch(`/api/costs/${this.proofCostData.id}/reimbursement-stage`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        
                        if (typeof Swal !== 'undefined') {
                            const msg = hasComprovante ? 'Comprovante registrado!' : 'Pago! Preencha o comprovante depois.';
                            await Swal.fire({ icon: 'success', title: 'Sucesso!', text: msg, timer: 1500, showConfirmButton: false });
                        }
                        
                        // Fecha modal e atualiza tabela
                        this.showProofModal = false;
                        await this.fetchCosts();
                    } else {
                        const data = await response.json();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Erro', data.message || 'Erro ao salvar', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', 'Erro ao salvar', 'error');
                    }
                }
            },
            
            // Anexar comprovante (quando já está em anexo_pendente)
            async anexarComprovante() {
                const fileInput = this.$refs.proofFile;
                const hasNewFile = fileInput && fileInput.files && fileInput.files[0];
                const hasProofNumber = !!this.proofCostData.proof_number && this.proofCostData.proof_number.trim() !== '';
                
                // Precisa ter pelo menos um: número OU arquivo
                if (!hasNewFile && !hasProofNumber) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Atenção', 'Preencha o número do documento ou anexe um arquivo.', 'warning');
                    }
                    return;
                }
                
                const formData = new FormData();
                formData.append('stage', 'pago');
                formData.append('_method', 'PATCH');
                
                if (hasNewFile) {
                    formData.append('proof_file', fileInput.files[0]);
                }
                
                if (this.proofCostData.proof_type) formData.append('proof_type', this.proofCostData.proof_type);
                if (this.proofCostData.proof_number) formData.append('proof_number', this.proofCostData.proof_number);
                
                try {
                    const response = await fetch(`/api/costs/${this.proofCostData.id}/reimbursement-stage`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    if (response.ok) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({ icon: 'success', title: 'Comprovante anexado!', timer: 1500, showConfirmButton: false });
                        }
                        
                        // Fecha modal e atualiza tabela
                        this.showProofModal = false;
                        await this.fetchCosts();
                    } else {
                        const data = await response.json();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Erro', data.message || 'Erro ao anexar', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', 'Erro ao anexar', 'error');
                    }
                }
            },
            
            // Salvar alterações no estado Pago (para anexar arquivo posteriormente)
            async salvarAlteracoes() {
                const fileInput = this.$refs.proofFile;
                const hasNewFile = fileInput && fileInput.files && fileInput.files[0];
                
                const formData = new FormData();
                formData.append('stage', 'pago'); // Mantém pago
                formData.append('_method', 'PATCH');
                
                if (hasNewFile) {
                    formData.append('proof_file', fileInput.files[0]);
                }
                
                if (this.proofCostData.proof_type) formData.append('proof_type', this.proofCostData.proof_type);
                if (this.proofCostData.proof_number) formData.append('proof_number', this.proofCostData.proof_number);
                
                try {
                    const response = await fetch(`/api/costs/${this.proofCostData.id}/reimbursement-stage`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    if (response.ok) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({ icon: 'success', title: 'Alterações salvas!', timer: 1500, showConfirmButton: false });
                        }
                        
                        // Fecha modal e atualiza tabela
                        this.showProofModal = false;
                        await this.fetchCosts();
                    } else {
                        const data = await response.json();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Erro', data.message || 'Erro ao salvar', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', 'Erro ao salvar', 'error');
                    }
                }
            },

            // Submete a ação selecionada no modal (sem arquivo)
            async submitProofAction(newStage) {
                const proofType = this.proofCostData.stage === 'aguardando_comprovante' ? this.proofCostData.proof_type : null;
                const proofNumber = this.proofCostData.stage === 'aguardando_comprovante' ? this.proofCostData.proof_number : null;
                await this.updateReimbursementStage(this.proofCostData.id, newStage, proofType, proofNumber);
            },
            
            // Submete com arquivo anexo
            async submitProofActionWithFile(newStage) {
                const formData = new FormData();
                formData.append('stage', newStage);
                formData.append('_method', 'PATCH');
                
                if (this.proofCostData.proof_type) formData.append('proof_type', this.proofCostData.proof_type);
                if (this.proofCostData.proof_number) formData.append('proof_number', this.proofCostData.proof_number);
                
                // Verifica se há arquivo para upload
                const fileInput = this.$refs.proofFile;
                const hasNewFile = fileInput && fileInput.files && fileInput.files[0];
                if (hasNewFile) {
                    formData.append('proof_file', fileInput.files[0]);
                }
                
                try {
                    const response = await fetch(`/api/costs/${this.proofCostData.id}/reimbursement-stage`, {
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
                        this.proofCostData.stage = newStage;
                        if (hasNewFile && updatedCost.reimbursement_proof_file) {
                            this.proofCostData.has_file = true;
                            this.proofCostData.proof_file_url = `/storage/${updatedCost.reimbursement_proof_file}`;
                        }
                        
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({ icon: 'success', title: 'Sucesso!', text: 'Comprovante salvo!', timer: 1500, showConfirmButton: false });
                        }
                        
                        // Atualiza tabela também (para badge externo)
                        await this.fetchCosts();
                    } else {
                        const data = await response.json();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Erro', data.message || 'Erro ao salvar comprovante', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', 'Erro ao salvar comprovante', 'error');
                    }
                }
            },
            
            // Remove arquivo de comprovante
            async removeProofFile() {
                if (typeof Swal !== 'undefined') {
                    const result = await Swal.fire({
                        title: 'Remover Comprovante',
                        text: 'Deseja remover o arquivo anexado? O status voltará para "Anexo Pendente".',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonText: 'Cancelar',
                        confirmButtonText: 'Sim, remover'
                    });
                    if (!result.isConfirmed) return;
                }
                
                try {
                    const response = await fetch(`/api/costs/${this.proofCostData.id}/remove-proof-file`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({ icon: 'success', title: 'Arquivo removido!', timer: 1500, showConfirmButton: false });
                        }
                        
                        // Fecha modal e atualiza tabela
                        this.showProofModal = false;
                        await this.fetchCosts();
                    } else {
                        const data = await response.json();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Erro', data.message || 'Erro ao remover arquivo', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', 'Erro ao remover arquivo', 'error');
                    }
                }
            },
            
            // Reverte para Aguardando e limpa todos os dados
            async revertToAguardando() {
                if (typeof Swal !== 'undefined') {
                    const result = await Swal.fire({
                        title: 'Reverter Pagamento',
                        text: 'Deseja reverter? Todos os dados do comprovante serão limpos.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonText: 'Cancelar',
                        confirmButtonText: 'Sim, reverter'
                    });
                    if (!result.isConfirmed) return;
                }
                
                try {
                    const response = await fetch(`/api/costs/${this.proofCostData.id}/reimbursement-stage`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ stage: 'aguardando_comprovante' })
                    });
                    
                    if (response.ok) {
                        if (typeof Swal !== 'undefined') {
                            await Swal.fire({ icon: 'success', title: 'Pagamento revertido!', timer: 1500, showConfirmButton: false });
                        }
                        
                        // Fecha modal e atualiza tabela
                        this.showProofModal = false;
                        await this.fetchCosts();
                    } else {
                        const data = await response.json();
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Erro', data.message || 'Erro ao reverter', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Erro', 'Erro ao reverter', 'error');
                    }
                }
            }
        };
    }
</script>
@endpush