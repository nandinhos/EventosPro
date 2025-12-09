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
                                            <!-- Badge clicável que abre modal + número do documento -->
                                            <div class="flex flex-col items-center">
                                                <button @click="openProofModal(cost)"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs transition-colors cursor-pointer"
                                                        :class="{
                                                            'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200': cost.reimbursement_stage === 'aguardando_comprovante',
                                                            'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-200': cost.reimbursement_stage === 'comprovante_recebido',
                                                            'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-200': cost.reimbursement_stage === 'conferido',
                                                            'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 hover:bg-green-200': cost.reimbursement_stage === 'reembolsado'
                                                        }">
                                                    <template x-if="cost.reimbursement_stage === 'aguardando_comprovante'"><span><i class="fas fa-clock mr-1"></i>Aguard.</span></template>
                                                    <template x-if="cost.reimbursement_stage === 'comprovante_recebido'"><span><i class="fas fa-file-alt mr-1"></i>Recebido</span></template>
                                                    <template x-if="cost.reimbursement_stage === 'conferido'"><span><i class="fas fa-check-double mr-1"></i>Conferido</span></template>
                                                    <template x-if="cost.reimbursement_stage === 'reembolsado'"><span><i class="fas fa-check-circle mr-1"></i>Pago</span></template>
                                                </button>
                                                <!-- Número do documento abaixo do badge -->
                                                <template x-if="cost.reimbursement_notes && ['comprovante_recebido', 'conferido', 'reembolsado'].includes(cost.reimbursement_stage)">
                                                    <span class="text-xxs text-gray-500 dark:text-gray-400 mt-0.5 truncate max-w-[80px]" :title="cost.reimbursement_notes" x-text="cost.reimbursement_notes"></span>
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

                {{-- Status atual --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Status atual:</span>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                          :class="{
                              'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400': proofCostData.stage === 'aguardando_comprovante',
                              'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-600 dark:text-yellow-400': proofCostData.stage === 'comprovante_recebido',
                              'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400': proofCostData.stage === 'conferido',
                              'bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400': proofCostData.stage === 'reembolsado'
                          }">
                        <template x-if="proofCostData.stage === 'aguardando_comprovante'">Aguardando</template>
                        <template x-if="proofCostData.stage === 'comprovante_recebido'">Recebido</template>
                        <template x-if="proofCostData.stage === 'conferido'">Conferido</template>
                        <template x-if="proofCostData.stage === 'reembolsado'">Pago</template>
                    </span>
                </div>

                {{-- Tipo de comprovante e número (só mostra se aguardando) --}}
                <template x-if="proofCostData.stage === 'aguardando_comprovante'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Comprovante</label>
                            <select x-model="proofCostData.proof_type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="recibo">Recibo</option>
                                <option value="nf">Nota Fiscal</option>
                                <option value="transferencia">Comprovante de Transferência</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento (opcional)</label>
                            <input type="text" x-model="proofCostData.proof_number" 
                                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
                                   placeholder="Ex: NF-e 123456 ou Recibo #001">
                        </div>
                    </div>
                </template>

                {{-- Ações disponíveis --}}
                <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Ações</p>
                    
                    <template x-if="proofCostData.stage === 'aguardando_comprovante'">
                        <button @click="submitProofAction('comprovante_recebido'); showProofModal = false"
                                class="w-full px-4 py-2 text-sm rounded-md bg-yellow-500 hover:bg-yellow-600 text-white flex items-center justify-center gap-2">
                            <i class="fas fa-file-upload"></i>Registrar Recebimento
                        </button>
                    </template>

                    <template x-if="proofCostData.stage === 'comprovante_recebido'">
                        <div class="space-y-2">
                            <button @click="submitProofAction('reembolsado'); showProofModal = false"
                                    class="w-full px-4 py-2 text-sm rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i>Marcar como Pago
                            </button>
                            <button @click="submitProofAction('aguardando_comprovante'); showProofModal = false"
                                    class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                                <i class="fas fa-undo"></i>Reverter p/ Aguardando
                            </button>
                        </div>
                    </template>

                    <template x-if="proofCostData.stage === 'conferido'">
                        <div class="space-y-2">
                            <button @click="submitProofAction('reembolsado'); showProofModal = false"
                                    class="w-full px-4 py-2 text-sm rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2">
                                <i class="fas fa-check-circle"></i>Marcar como Pago
                            </button>
                            <button @click="submitProofAction('comprovante_recebido'); showProofModal = false"
                                    class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                                <i class="fas fa-undo"></i>Voltar p/ Recebido
                            </button>
                        </div>
                    </template>

                    <template x-if="proofCostData.stage === 'reembolsado'">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-2">
                                <i class="fas fa-check-circle text-lg"></i>
                                <span class="font-medium">Despesa Paga ✓</span>
                            </div>
                            <button @click="submitProofAction('comprovante_recebido'); showProofModal = false"
                                    class="w-full px-4 py-2 text-sm rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-2">
                                <i class="fas fa-undo"></i>Voltar p/ Recebido
                            </button>
                        </div>
                    </template>
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
            proofCostData: { id: null, description: '', value: 0, stage: '', proof_type: 'recibo' },
            
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
                this.proofCostData = {
                    id: cost.id,
                    description: cost.description || 'Despesa',
                    value: cost.value || 0,
                    stage: cost.reimbursement_stage || 'aguardando_comprovante',
                    proof_type: cost.reimbursement_proof_type || 'recibo',
                    proof_number: cost.reimbursement_notes || ''
                };
                this.showProofModal = true;
            },

            // Submete a ação selecionada no modal
            async submitProofAction(newStage) {
                const proofType = this.proofCostData.stage === 'aguardando_comprovante' ? this.proofCostData.proof_type : null;
                const proofNumber = this.proofCostData.stage === 'aguardando_comprovante' ? this.proofCostData.proof_number : null;
                await this.updateReimbursementStage(this.proofCostData.id, newStage, proofType, proofNumber);
            }
        };
    }
</script>
@endpush