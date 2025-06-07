{{-- resources/views/gigs/_show_costs.blade.php --}}
@props(['gig'])

<div x-data="{
    showCostFormModal: false, // Controla visibilidade do modal de adicionar/editar
    isEditMode: false,        // Indica se o modal está em modo de edição
    costsByCenter: [],        // Dados dos custos agrupados, vindos do backend
    totalConfirmedCosts: 0,
    totalPendingCosts: 0,
    selectedCenterFilter: '', // Para o filtro de centro de custo

    // Função para alternar o status is_invoice
    async toggleInvoice(costId, currentStatus) {
        try {
            const response = await fetch(`/gigs/{{ $gig->id }}/costs/${costId}/toggle-invoice`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
            });
            const result = await response.json();
            if (response.ok) {
                this.fetchCosts(); // Recarrega a lista
            } else {
                alert(result.message || 'Erro ao atualizar status da nota fiscal.');
            }
        } catch (error) { 
            console.error('Erro:', error); 
            alert('Erro de comunicação ao atualizar status da nota fiscal.'); 
        }
    },
    costFormData: {           // Dados para o formulário do modal
        id: null,
        cost_center_id: '',
        description: '',
        value: '',
        currency: 'BRL',
        expense_date: '{{ today()->format('Y-m-d') }}',
        payer_type: 'agencia',
        payer_details: '',
        notes: ''
    },
    // Lista de centros de custo para o select no modal (populado pelo PHP)
    costCenters: {{ \App\Models\CostCenter::orderBy('name')->get()->mapWithKeys(fn($center) => [$center->id => $center->name])->toJson() }},

    // Busca os custos da Gig via API JSON
    async fetchCosts() {
        try {
            const response = await fetch(`{{ route('gigs.costs.listJson', $gig) }}`); // Rota JSON
            if (!response.ok) throw new Error('Falha ao buscar custos: ' + response.statusText);
            const data = await response.json();
            this.costsByCenter = data; // Atualiza os dados agrupados
            this.calculateTotals();     // Recalcula os totais
        } catch (error) {
            console.error('Erro buscando custos:', error);
            alert('Erro ao carregar despesas. Verifique o console.');
        }
    },

    // Calcula totais de custos confirmados e pendentes
    calculateTotals() {
        let confirmed = 0;
        let pending = 0;
        (this.costsByCenter || []).forEach(group => {
            (group.costs || []).forEach(cost => {
                // Converte valor para float antes de somar
                const costValue = parseFloat(cost.value) || 0;
                if (cost.is_confirmed) {
                    confirmed += costValue;
                } else {
                    pending += costValue;
                }
            });
        });
        this.totalConfirmedCosts = confirmed;
        this.totalPendingCosts = pending;
    },

    // Abre o modal para uma NOVA despesa
    openNewCostModal() {
        this.isEditMode = false;
        // Reseta o formulário para uma nova despesa
        this.costFormData = {
            id: null, gig_id: {{ $gig->id }}, cost_center_id: '', description: '',
            value: '', currency: 'BRL', expense_date: '{{ today()->format('Y-m-d') }}',
            payer_type: 'agencia', payer_details: '', notes: ''
        };
        this.showCostFormModal = true;
        this.$nextTick(() => { if(this.$refs.costCenterSelectModal) this.$refs.costCenterSelectModal.focus() });
    },

    // Abre o modal para EDITAR uma despesa existente
    openEditCostModal(cost) {
        this.isEditMode = true;
        // Preenche o formulário com os dados do custo selecionado
        this.costFormData = {
            id: cost.id,
            gig_id: {{ $gig->id }}, // Já temos o gig_id no contexto do Alpine
            cost_center_id: cost.cost_center_id,
            description: cost.description || '',
            value: cost.value,
            currency: cost.currency || 'BRL',
            expense_date: cost.expense_date || '{{ today()->format('Y-m-d') }}',
            payer_type: cost.payer_type || 'agencia',
            payer_details: cost.payer_details || '',
            notes: cost.notes || ''
        };
        this.showCostFormModal = true;
        this.$nextTick(() => { if(this.$refs.costCenterSelectModal) this.$refs.costCenterSelectModal.focus() });
    },

    // Submete o formulário de adicionar/editar via FETCH
    async submitCostForm() {
        const url = this.isEditMode ? `/gigs/{{ $gig->id }}/costs/${this.costFormData.id}` : `/gigs/{{ $gig->id }}/costs`;
        const method = this.isEditMode ? 'PUT' : 'POST';

        // Limpa ID se for novo para não enviar como parte do body
        let dataToSend = { ...this.costFormData };
        if (!this.isEditMode) {
            delete dataToSend.id;
        }

         console.log('Enviando para o backend (submitCostForm):', dataToSend); // << ADICIONE ESTE LOG

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', // Essencial para POST/PUT/PATCH/DELETE
                    'Accept': 'application/json'
                },
                body: JSON.stringify(dataToSend) // Envia dados como JSON
            });
            const result = await response.json(); // Espera resposta JSON

            if (response.ok) {
                this.fetchCosts(); // Recarrega a lista de custos
                this.showCostFormModal = false; // Fecha o modal
                // TODO: Usar um sistema de notificação Alpine mais elegante
                alert(result.message || 'Operação realizada com sucesso!');
            } else {
                // Tenta exibir erros de validação, se houver
                let errorMessage = result.message || 'Erro ao salvar despesa.';
                if (result.errors) {
                    errorMessage += '\n';
                    for (const field in result.errors) {
                        errorMessage += result.errors[field].join('\n') + '\n';
                    }
                }
                alert(errorMessage);
                console.error('Erro ao salvar despesa:', result);
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            alert('Erro de comunicação ao salvar despesa.');
        }
    },

    // Confirma ou desconfirma uma despesa
    async toggleConfirm(costId, isCurrentlyConfirmed) {
        const action = isCurrentlyConfirmed ? 'unconfirm' : 'confirm';
        const url = `/gigs/{{ $gig->id }}/costs/${costId}/${action}`; // Rota correta

        try {
            const response = await fetch(url, {
                method: 'PATCH', // Usar PATCH para updates parciais como confirmação
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
            });
            const result = await response.json();
            if (response.ok) {
                this.fetchCosts(); // Recarrega a lista
                $dispatch('costs-updated', { gigId: {{ $gig->id }} });
                alert(result.message);
            } else {
                 alert(result.message || `Erro ao ${action} despesa.`);
            }
        } catch (error) { console.error('Erro:', error); alert('Erro de comunicação.'); }
    },

    // Exclui uma despesa
    async deleteCost(costId) {
        if (!confirm('Tem certeza que deseja excluir esta despesa?')) return;

        const url = `/gigs/{{ $gig->id }}/costs/${costId}`; // Rota correta

        try {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
            });
            const result = await response.json();
            if (response.ok) {
                this.fetchCosts(); // Recarrega a lista
                $dispatch('costs-updated', { gigId: {{ $gig->id }} });
                alert(result.message);
            } else {
                 alert(result.message || 'Erro ao excluir despesa.');
            }
        } catch (error) { console.error('Erro:', error); alert('Erro de comunicação.'); }
    }
}"
x-init="fetchCosts()" {{-- Carrega os custos ao iniciar o componente --}}
class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mt-6">

    {{-- Header do Card e Filtros (código igual ao anterior) --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2 bg-gray-50 dark:bg-gray-700/50">
        {{-- ... (Título, Botão Adicionar, Totais, Filtro Centro) ... --}}
         <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Despesas / Custos</h3>
                <button type="button" @click="openNewCostModal()"
                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
                    <i class="fas fa-plus mr-1"></i> Adicionar Despesa
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-sm">
                <p class="text-gray-600 dark:text-gray-300">
                    Confirmado: <span class="font-semibold text-green-600 dark:text-green-400" x-text="`R$ ${totalConfirmedCosts.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"></span>
                </p>
                <p class="text-gray-600 dark:text-gray-300">
                    Pendente: <span class="font-semibold text-yellow-600 dark:text-yellow-400" x-text="`R$ ${totalPendingCosts.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"></span>
                </p>
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-500 dark:text-gray-400">Filtrar Centro:</label>
                    <select x-model="selectedCenterFilter" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:border-primary-500 focus:ring-primary-500 py-1 px-2">
                        <option value="">Todos</option>
                        <template x-for="group in costsByCenter" :key="group.cost_center.id">
                            <option :value="group.cost_center.id" x-text="group.cost_center.name"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista de Despesas Agrupadas Renderizada com AlpineJS --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <template x-if="!costsByCenter || costsByCenter.length === 0">
            <p class="p-6 text-sm text-gray-500 dark:text-gray-400">Nenhuma despesa registrada para esta Gig.</p>
        </template>
        {{-- Filtra os grupos antes de iterar --}}
        <template x-for="group in costsByCenter.filter(g => !selectedCenterFilter || g.cost_center.id == selectedCenterFilter)" :key="group.cost_center.id">
            <div class="p-4">
                {{-- Cabeçalho do Grupo de Centro de Custo --}}
                <div class="flex justify-between items-center mb-3 bg-gray-100 dark:bg-gray-700/50 p-2 rounded-md">
                    <h4 class="text-md font-medium text-gray-800 dark:text-white" x-text="group.cost_center.name"></h4>
                    <span class="text-xs text-gray-600 dark:text-gray-300">
                        Total Centro: <span class="font-medium" x-text="`R$ ${parseFloat(group.total_value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"></span>
                        (<span x-text="group.count"></span> itens)
                    </span>
                </div>
                {{-- Tabela de Custos do Grupo --}}
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
            <td class="py-2 px-1 whitespace-nowrap text-right" x-text="`${cost.currency} ${parseFloat(cost.value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`"></td>
            <td class="py-2 px-1 whitespace-nowrap text-center">
                <div class="flex justify-center items-center space-x-1">
                    <input type="checkbox" 
                           :checked="cost.is_invoice"
                           @click="toggleInvoice(cost.id, cost.is_invoice)"
                           :disabled="!cost.is_confirmed"
                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50 disabled:opacity-50"
                           :title="!cost.is_confirmed ? 'Confirme a despesa primeiro' : 'Incluir na nota fiscal'">
                    <i class="fas fa-file-invoice text-gray-400" :class="{'text-primary-500': cost.is_invoice}"></i>
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
                    {{-- !! CORREÇÕES AQUI !! --}}
                    <button @click="toggleConfirm(cost.id, cost.is_confirmed)" {{-- Passa cost.id e cost.is_confirmed --}}
                            :title="cost.is_confirmed ? 'Reverter Confirmação' : 'Confirmar Despesa'"
                            :class="cost.is_confirmed ? 'text-yellow-500 hover:text-yellow-700' : 'text-green-500 hover:text-green-700'"
                            class="p-1 focus:outline-none">
                        <i class="fas fa-fw" :class="cost.is_confirmed ? 'fa-undo-alt' : 'fa-check-circle'"></i>
                    </button>
                    <button @click="openEditCostModal(cost)" {{-- Passa o objeto cost INTEIRO --}}
                            title="Editar Despesa"
                            :disabled="cost.is_confirmed"
                            class="p-1 focus:outline-none"
                            :class="!cost.is_confirmed ? 'text-primary-500 hover:text-primary-700' : 'text-gray-400 cursor-not-allowed opacity-50'">
                        <i class="fas fa-edit fa-fw"></i>
                    </button>
                    <button @click="deleteCost(cost.id)" title="Excluir Despesa"
            :disabled="cost.is_confirmed"
            class="p-1 focus:outline-none"
            :class="!cost.is_confirmed ? 'text-red-500 hover:text-red-700' : 'text-gray-400 cursor-not-allowed opacity-50'">
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

    {{-- Modal para Adicionar/Editar Despesa --}}
    <div x-show="showCostFormModal"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
         style="display: none;"
         x-trap.inert.noscroll="showCostFormModal"> {{-- Melhora acessibilidade --}}
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" @click="showCostFormModal = false"></div>
        {{-- Conteúdo do Modal --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full mx-auto shadow-xl p-6" @click.away="showCostFormModal = false">
            <div class="flex justify-between items-center pb-3">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white" x-text="isEditMode ? 'Editar Despesa' : 'Adicionar Nova Despesa'"></h3>
                <button @click="showCostFormModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="submitCostForm()" class="space-y-4">
                {{-- Inclui os campos do formulário de um parcial --}}
                @include('gig_costs._form_fields_modal') {{-- Este parcial já existe --}}
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" @click="showCostFormModal = false" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">Cancelar</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                        <span x-text="isEditMode ? 'Atualizar Despesa' : 'Salvar Despesa'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>