<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mt-6" x-data="artistPaymentBatchManager()">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Fechamento Financeiro do Artista</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-600 dark:text-gray-300">Resumo do Período</h4>
            <dl class="mt-2 space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Total de Gigs Realizadas:</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $realizedGigs->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Cachê Bruto Total:</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">R$ {{ number_format($metrics['totalGrossFee'], 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Líquido Pago:</dt>
                    <dd class="font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($metrics['cache_received_brl'], 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Líquido Pendente:</dt>
                    <dd class="font-semibold text-yellow-600 dark:text-yellow-400">R$ {{ number_format($metrics['cache_pending_brl'], 2, ',', '.') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg flex flex-col justify-center">
            <h4 class="font-medium text-gray-600 dark:text-gray-300 mb-3">Ações de Pagamento em Massa</h4>

            <div class="mb-3">
                <label for="payment_date" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Data do Pagamento</label>
                <input type="date"
                       id="payment_date"
                       x-model="paymentDate"
                       max="{{ now()->format('Y-m-d') }}"
                       class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="space-y-2">
                <button type="button"
                        @click="submitBatchAction('pay')"
                        :disabled="selectedGigs.length === 0"
                        :class="selectedGigs.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-check-circle mr-2"></i>
                    Pagar Selecionados (<span x-text="selectedGigs.length"></span>)
                </button>
                <button type="button"
                        @click="submitBatchAction('unpay')"
                        :disabled="selectedGigs.length === 0"
                        :class="selectedGigs.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-700'"
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-undo mr-2"></i>
                    Desfazer Pagamento (<span x-text="selectedGigs.length"></span>)
                </button>
            </div>
        </div>
    </div>

    {{-- Hidden forms for batch actions --}}
    <form id="batchPaymentForm" method="POST" action="{{ route('artists.payments.settleBatch') }}" style="display: none;">
        @csrf
        <template x-for="gigId in selectedGigs" :key="gigId">
            <input type="hidden" name="gig_ids[]" :value="gigId">
        </template>
        <input type="hidden" name="payment_date" :value="paymentDate">
        <input type="hidden" name="tab" value="financials">
    </form>

    <form id="batchUnpaymentForm" method="POST" action="{{ route('artists.payments.unsettleBatch') }}" style="display: none;">
        @csrf
        @method('PATCH')
        <template x-for="gigId in selectedGigs" :key="gigId">
            <input type="hidden" name="gig_ids[]" :value="gigId">
        </template>
        <input type="hidden" name="tab" value="financials">
    </form>

    {{-- Events Table with Selection --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <h4 class="text-lg font-medium text-gray-800 dark:text-white mb-3 p-4 pb-0">Eventos Realizados</h4>

        @if($realizedGigs->count() > 0)
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-12">
                            <input type="checkbox"
                                   @change="toggleSelectAll($event.target.checked)"
                                   :checked="areAllSelected()"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local/Evento</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Booker</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cachê Líquido</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Despesas</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Ações</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($realizedGigs as $gig)
                        @php
                            $gigCosts = $gig->gigCosts;
                            $confirmedCosts = $gigCosts->where('is_confirmed', true);
                            $pendingCosts = $gigCosts->where('is_confirmed', false);
                            $totalConfirmed = $confirmedCosts->sum('value_brl');
                            $totalPending = $pendingCosts->sum('value_brl');
                            $hasExpenses = $gigCosts->count() > 0;
                            $hasPendingCosts = $pendingCosts->count() > 0;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50"
                            :class="{'bg-yellow-50 dark:bg-yellow-900/10': {{ $hasPendingCosts ? 'true' : 'false' }}}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox"
                                       value="{{ $gig->id }}"
                                       @change="toggleGigSelection({{ $gig->id }})"
                                       :checked="selectedGigs.includes({{ $gig->id }})"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $gig->gig_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('gigs.show', $gig->id) }}"
                                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 hover:underline"
                                   title="Clique para editar o evento">
                                    {{ $gig->location_event_details ?: 'Gig #'.$gig->id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                {{ $gig->booker->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-mono text-gray-700 dark:text-gray-200">
                                R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($gig->artist_payment_status === 'pago')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        Pago
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        Pendente
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($hasExpenses)
                                    <button @click="toggleExpenses({{ $gig->id }})"
                                            class="text-xs px-2 py-1 rounded-md border transition-colors"
                                            :class="expandedGigs.includes({{ $gig->id }}) ? 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700' : 'bg-gray-100 text-gray-700 border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'">
                                        <i class="fas fa-fw" :class="expandedGigs.includes({{ $gig->id }}) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                        {{ $gigCosts->count() }} despesa{{ $gigCosts->count() > 1 ? 's' : '' }}
                                    </button>
                                    <div class="text-xs mt-1 space-y-1">
                                        @if ($totalConfirmed > 0)
                                            <div class="text-green-600 dark:text-green-400">
                                                Conf: R$ {{ number_format($totalConfirmed, 2, ',', '.') }}
                                            </div>
                                        @endif
                                        @if ($totalPending > 0)
                                            <div class="text-yellow-600 dark:text-yellow-400">
                                                Pend: R$ {{ number_format($totalPending, 2, ',', '.') }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">Sem despesas</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('gigs.show', $gig->id) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200">Ver Detalhes</a>
                            </td>
                        </tr>

                        {{-- Componente Completo de Gestão de Despesas --}}
                        @if ($hasExpenses)
                            <tr x-show="expandedGigs.includes({{ $gig->id }})" x-transition class="bg-gray-50 dark:bg-gray-700/30">
                                <td colspan="8" class="px-2 py-4">
                                    {{-- Incluindo o componente de gestão de despesas completo --}}
                                    <div x-data="costsManagers['costsManager{{ $gig->id }}']("
                                            {{ $gig->id }},
                                            {{ \Illuminate\Support\Js::from($costCenters->pluck('name', 'id')) }}
                                        )"
                                         x-init="$nextTick(() => fetchCosts())"
                                         class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">

                                        {{-- Header do Card com Totais e Botão Adicionar --}}
                                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap justify-between items-center gap-2 bg-gray-50 dark:bg-gray-700/50">
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Despesas / Custos - Gig #{{ $gig->id }}</h3>
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
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                Nenhum evento realizado encontrado para este período.
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function artistPaymentBatchManager() {
        return {
            selectedGigs: [],
            expandedGigs: [],
            paymentDate: "{{ now()->format('Y-m-d') }}",
            allGigIds: @json($realizedGigs->pluck('id')->toArray()),

            toggleExpenses(gigId) {
                const index = this.expandedGigs.indexOf(gigId);
                if (index > -1) {
                    this.expandedGigs.splice(index, 1);
                } else {
                    this.expandedGigs.push(gigId);
                }
            },

            toggleGigSelection(gigId) {
                const index = this.selectedGigs.indexOf(gigId);
                if (index > -1) {
                    this.selectedGigs.splice(index, 1);
                } else {
                    this.selectedGigs.push(gigId);
                }
            },

            toggleSelectAll(checked) {
                if (checked) {
                    this.selectedGigs = [...this.allGigIds];
                } else {
                    this.selectedGigs = [];
                }
            },

            areAllSelected() {
                return this.allGigIds.length > 0 && this.selectedGigs.length === this.allGigIds.length;
            },

            submitBatchAction(actionType) {
                if (this.selectedGigs.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Nenhum evento selecionado',
                        text: 'Selecione ao menos um evento para continuar.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                if (actionType === 'pay') {
                    if (!this.paymentDate) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data não informada',
                            text: 'Por favor, informe a data do pagamento.',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'question',
                        title: 'Confirmar pagamento em massa?',
                        html: `Você está prestes a marcar <strong>${this.selectedGigs.length} evento(s)</strong> como pagos.<br>Data do pagamento: <strong>${this.formatDate(this.paymentDate)}</strong>`,
                        showCancelButton: true,
                        confirmButtonText: 'Sim, pagar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#6b7280'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('batchPaymentForm').submit();
                        }
                    });
                } else if (actionType === 'unpay') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Desfazer pagamento em massa?',
                        html: `Você está prestes a desfazer o pagamento de <strong>${this.selectedGigs.length} evento(s)</strong>.<br>Esta ação marcará os eventos como pendentes novamente.`,
                        showCancelButton: true,
                        confirmButtonText: 'Sim, desfazer',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('batchUnpaymentForm').submit();
                        }
                    });
                }
            },

            formatDate(dateString) {
                const date = new Date(dateString + 'T00:00:00');
                return date.toLocaleDateString('pt-BR');
            }
        };
    }

    // Objeto global para managers de custos
    const costsManagers = {};

    // Função factory para criar um manager de custos único para cada gig
    @foreach ($realizedGigs as $gig)
    costsManagers['costsManager{{ $gig->id }}'] = function(gigId, costCentersInitial) {
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
            confirmCostData: { id: null, description: '', date: "{{ today()->format('Y-m-d') }}" },

            formatCurrency(value, withSymbol = true) {
                const num = parseFloat(value);
                if (isNaN(num)) return withSymbol ? 'R$ 0,00' : '0,00';
                const options = withSymbol ? { style: 'currency', currency: 'BRL' } : { minimumFractionDigits: 2, maximumFractionDigits: 2 };
                return num.toLocaleString('pt-BR', options);
            },

            async fetchCosts() {
                this.loading = true;
                console.log(`[Gig ${gigId}] Iniciando busca de custos...`);
                try {
                    const response = await fetch(`/gigs/${gigId}/costs-json`);
                    console.log(`[Gig ${gigId}] Response status:`, response.status);
                    if (!response.ok) throw new Error('Falha ao buscar custos');
                    const data = await response.json();
                    console.log(`[Gig ${gigId}] Dados recebidos:`, data);
                    this.costsByCenter = data;
                    console.log(`[Gig ${gigId}] costsByCenter após atribuição:`, this.costsByCenter);
                    this.calculateTotals();
                } catch (error) {
                    console.error(`[Gig ${gigId}] Erro buscando custos:`, error);
                } finally {
                    this.loading = false;
                    console.log(`[Gig ${gigId}] Loading finalizado. costsByCenter:`, this.costsByCenter);
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
                    // Recarrega a página para atualizar os totais
                    window.location.reload();
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
    };
    @endforeach
</script>
@endpush
