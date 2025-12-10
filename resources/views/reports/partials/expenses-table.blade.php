@php
    // Variáveis esperadas do controller: $groupedExpensesReport
    $expenseGroups = $groupedExpensesReport['groups'] ?? collect([]);
    $totalGeral = $groupedExpensesReport['total_geral'] ?? 0;
    $totalConfirmado = $groupedExpensesReport['total_confirmado'] ?? 0;
    $totalPendente = $groupedExpensesReport['total_pendente'] ?? 0;
    
    // Calcular totais de reembolso
    $totalReembolsavel = 0;
    $totalReembolsado = 0;
    foreach ($expenseGroups as $group) {
        foreach ($group['costs'] as $cost) {
            if ($cost->is_invoice) {
                $totalReembolsavel += $cost->value;
                if ($cost->effective_reimbursement_stage === 'pago') {
                    $totalReembolsado += $cost->value;
                }
            }
        }
    }
    
    // Coletar todos os IDs de custos para seleção
    $allCostIds = [];
    foreach ($expenseGroups as $group) {
        foreach ($group['costs'] as $cost) {
            $allCostIds[] = $cost->id;
        }
    }
@endphp

<div x-data="expenseBatchManager()" class="space-y-6 mt-4">
    {{-- Cards de Resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card Vermelho: Total Geral --}}
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Geral de Despesas</h3>
            <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($totalGeral, 2, ',', '.') }}</p>
        </div>
        {{-- Card Verde: Confirmado --}}
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Confirmado</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($totalConfirmado, 2, ',', '.') }}</p>
        </div>
        {{-- Card Amarelo: Pendente --}}
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Pendente</h3>
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">R$ {{ number_format($totalPendente, 2, ',', '.') }}</p>
        </div>
        {{-- Card Azul: Reembolsável --}}
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Reembolsável (Pago/Total)</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">
                R$ {{ number_format($totalReembolsado, 2, ',', '.') }}
                <span class="text-sm font-normal text-blue-600 dark:text-blue-400">/ {{ number_format($totalReembolsavel, 2, ',', '.') }}</span>
            </p>
        </div>
    </div>

    {{-- Barra de Ações em Massa (Pagamento/Reembolso) --}}
    <div class="flex flex-wrap items-end gap-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg border dark:border-gray-700">
        {{-- Campo de Data --}}
        <div class="flex-shrink-0">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento</label>
            <input type="date" x-model="paymentDate" 
                   class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                   :max="new Date().toISOString().split('T')[0]">
        </div>
        
        {{-- Contador de Seleção --}}
        <div class="flex-grow text-sm text-gray-600 dark:text-gray-400">
            <span x-text="selectedCosts.length"></span> de <span>{{ count($allCostIds) }}</span> despesas selecionadas
            <p class="text-xs text-gray-500">* Apenas despesas confirmadas e com NF marcada podem ser pagas em massa</p>
        </div>
        
        {{-- Botões de Ação --}}
        <div class="flex gap-2">
            <button @click="submitBatchAction('settle')"
                    :disabled="selectedCosts.length === 0 || submitting"
                    title="Marcar como pagas/reembolsadas as despesas selecionadas (confirmadas + NF)"
                    class="px-4 py-2 text-sm rounded-md bg-green-600 hover:bg-green-700 text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <i class="fas fa-money-bill-wave"></i>
                <span x-text="submitting ? 'Processando...' : 'Pagar Selecionados'"></span>
            </button>
            <button @click="submitBatchAction('unsettle')"
                    :disabled="selectedCosts.length === 0 || submitting"
                    title="Reverter pagamento das despesas selecionadas"
                    class="px-4 py-2 text-sm rounded-md bg-yellow-600 hover:bg-yellow-700 text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <i class="fas fa-undo"></i>
                <span x-text="submitting ? 'Processando...' : 'Reverter Pagamento'"></span>
            </button>
        </div>
    </div>


    {{-- Formulários para Ações (hidden) --}}
    <form id="settle-form" method="POST" action="{{ route('reports.expenses.settleBatch') }}" class="hidden">
        @csrf
        <input type="hidden" name="payment_date" :value="paymentDate">
        <template x-for="costId in selectedCosts" :key="costId">
            <input type="hidden" name="cost_ids[]" :value="costId">
        </template>
        @foreach(request()->only(['start_date', 'end_date', 'booker_id', 'artist_id']) as $key => $value)
            @if($value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
    </form>
    
    <form id="unsettle-form" method="POST" action="{{ route('reports.expenses.unsettleBatch') }}" class="hidden">
        @csrf
        @method('PATCH')
        <template x-for="costId in selectedCosts" :key="costId">
            <input type="hidden" name="cost_ids[]" :value="costId">
        </template>
        @foreach(request()->only(['start_date', 'end_date', 'booker_id', 'artist_id']) as $key => $value)
            @if($value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
    </form>

    {{-- Tabela Agrupada por Centro de Custo --}}
    @if ($expenseGroups->isNotEmpty())
        <div class="space-y-6">
            @foreach ($expenseGroups as $group)
                @php
                    $groupCostIds = collect($group['costs'])->pluck('id')->toArray();
                @endphp
                <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border dark:border-gray-700 overflow-hidden">
                    {{-- Cabeçalho do Grupo --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            {{-- Checkbox Selecionar Todos do Grupo --}}
                            <input type="checkbox" 
                                   :checked="areAllSelectedForGroup({{ json_encode($groupCostIds) }})"
                                   @click="toggleSelectAllForGroup({{ json_encode($groupCostIds) }})"
                                   class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                            <h4 class="text-md font-semibold text-gray-800 dark:text-white">
                                <i class="fas fa-folder mr-2 text-primary-500"></i>{{ $group['cost_center_name'] }}
                            </h4>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">({{ count($group['costs']) }} itens)</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                Subtotal: R$ {{ number_format($group['subtotal'], 2, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Tabela de Despesas --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-100 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 w-8"></th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Artista</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Descrição</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Valor</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Confirmação</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">NF?</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Reembolso</th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($group['costs'] as $cost)
                                    {{-- Linha Principal --}}
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer transition-colors"
                                        :class="{ 'bg-yellow-50 dark:bg-yellow-900/10': !{{ $cost->is_confirmed ? 'true' : 'false' }} }">
                                        <td class="px-3 py-2" @click.stop>
                                            <input type="checkbox" value="{{ $cost->id }}" x-model="selectedCosts"
                                                   class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap" @click="toggleExpand({{ $cost->id }})">
                                            {{ $cost->expense_date?->isoFormat('L') ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap" @click="toggleExpand({{ $cost->id }})">
                                            <a href="{{ route('gigs.show', $cost->gig) }}" 
                                               class="font-semibold text-primary-600 hover:underline"
                                               @click.stop>
                                                Gig #{{ $cost->gig_id }}
                                            </a>
                                            <span class="block text-gray-500 dark:text-gray-400">{{ $cost->gig->artist->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $cost->description }}" @click="toggleExpand({{ $cost->id }})">
                                            {{ $cost->description ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right font-semibold" @click="toggleExpand({{ $cost->id }})">
                                            {{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center" @click="toggleExpand({{ $cost->id }})">
                                            <x-status-badge :status="$cost->is_confirmed ? 'confirmado' : 'pendente'" type="cost-confirmation" />
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center" @click="toggleExpand({{ $cost->id }})">
                                            @if($cost->is_invoice)
                                                <i class="fas fa-check-circle text-green-500" title="Sim, reembolsável"></i>
                                            @else
                                                <i class="fas fa-times-circle text-gray-400" title="Não"></i>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center" @click="toggleExpand({{ $cost->id }})">
                                            @if($cost->is_invoice)
                                                <x-status-badge :status="$cost->effective_reimbursement_stage ?? 'aguardando_comprovante'" type="reimbursement" />
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center" @click="toggleExpand({{ $cost->id }})">
                                            <i class="fas transition-transform duration-200" 
                                               :class="isExpanded({{ $cost->id }}) ? 'fa-chevron-up text-primary-500' : 'fa-chevron-down text-gray-400'"></i>
                                        </td>
                                    </tr>
                                    
                                    {{-- Linha Expandida com Detalhes --}}
                                    <tr x-show="isExpanded({{ $cost->id }})" x-collapse.duration.200ms>
                                        <td colspan="9" class="p-0">
                                            <x-expense-row-detail :cost="$cost" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <i class="fas fa-receipt text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">
                Nenhuma despesa encontrada para os filtros selecionados.
            </p>
        </div>
    @endif
</div>

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('expenseBatchManager', () => ({
        selectedCosts: [],
        expandedRows: [],
        paymentDate: '{{ now()->format("Y-m-d") }}',
        submitting: false,
        
        toggleExpand(costId) {
            const index = this.expandedRows.indexOf(costId);
            if (index > -1) {
                this.expandedRows.splice(index, 1);
            } else {
                this.expandedRows.push(costId);
            }
        },
        
        isExpanded(costId) {
            return this.expandedRows.includes(costId);
        },
        
        areAllSelectedForGroup(costIds) {
            return costIds.every(id => this.selectedCosts.includes(String(id)));
        },
        
        toggleSelectAllForGroup(costIds) {
            const allSelected = this.areAllSelectedForGroup(costIds);
            if (allSelected) {
                // Remove todos do grupo
                this.selectedCosts = this.selectedCosts.filter(id => !costIds.map(String).includes(id));
            } else {
                // Adiciona todos do grupo
                const costIdStrings = costIds.map(String);
                const toAdd = costIdStrings.filter(id => !this.selectedCosts.includes(id));
                this.selectedCosts = [...this.selectedCosts, ...toAdd];
            }
        },
        
        submitBatchAction(actionType) {
            if (this.selectedCosts.length === 0) {
                alert('Selecione pelo menos uma despesa.');
                return;
            }
            
            const message = actionType === 'settle' 
                ? `Confirmar ${this.selectedCosts.length} despesa(s)?` 
                : `Reverter ${this.selectedCosts.length} despesa(s)?`;
            
            if (confirm(message)) {
                this.submitting = true;
                const form = actionType === 'settle' ? 'settle-form' : 'unsettle-form';
                document.getElementById(form).submit();
            }
        }
    }));
});
</script>
@endPushOnce