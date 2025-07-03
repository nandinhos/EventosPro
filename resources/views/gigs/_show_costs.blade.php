@props(['gig', 'costCenters'])

<div x-data="costsManager(
        {{ $gig->id }},
        {{ \Illuminate\Support\Js::from($costCenters->pluck('name', 'id')) }}
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
            }
        };
    }
</script>
@endpush