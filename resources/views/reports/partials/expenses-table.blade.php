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
    
    // Coletar todos os IDs de custos e criar mapa inicial
    $allCostIds = [];
    $costsDataMap = [];
    foreach ($expenseGroups as $group) {
        foreach ($group['costs'] as $cost) {
            $allCostIds[] = $cost->id;
            $costsDataMap[$cost->id] = [
                'id' => $cost->id,
                'value' => $cost->value,
                'is_confirmed' => $cost->is_confirmed,
                'is_invoice' => $cost->is_invoice,
                'effective_stage' => $cost->effective_reimbursement_stage ?? 'aguardando_comprovante',
                'has_proof' => !empty($cost->reimbursement_notes) || !empty($cost->reimbursement_proof_file),
                'proof_number' => $cost->reimbursement_notes,
            ];
        }
    }
    
    // Totais iniciais para variáveis Alpine
    $initialTotals = [
        'total_geral' => $totalGeral,
        'total_confirmado' => $totalConfirmado,
        'total_pendente' => $totalPendente,
        'total_reembolsavel' => $totalReembolsavel,
        'total_reembolsado' => $totalReembolsado,
    ];
@endphp

<div x-data="expenseBatchManager({{ json_encode($costsDataMap) }}, {{ json_encode($initialTotals) }})" 
     @cost-updated.window="updateCostState($event.detail)"
     class="space-y-6 mt-4">
    {{-- Cards de Resumo (Reativos) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card Vermelho: Total Geral --}}
        <div class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Geral de Despesas</h3>
            <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ <span x-text="formatCurrency(computedTotals.totalGeral)"></span></p>
        </div>
        {{-- Card Verde: Confirmado --}}
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Confirmado</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ <span x-text="formatCurrency(computedTotals.totalConfirmado)"></span></p>
        </div>
        {{-- Card Amarelo: Pendente --}}
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Pendente</h3>
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">R$ <span x-text="formatCurrency(computedTotals.totalPendente)"></span></p>
        </div>
        {{-- Card Azul: Reembolsável --}}
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Reembolsável (Pago/Total)</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">
                R$ <span x-text="formatCurrency(computedTotals.totalReembolsado)"></span>
                <span class="text-sm font-normal text-blue-600 dark:text-blue-400">/ <span x-text="formatCurrency(computedTotals.totalReembolsavel)"></span></span>
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
                                    {{-- Linha Principal com dados reativos --}}
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer transition-colors"
                                        :class="{ 'bg-yellow-50 dark:bg-yellow-900/10': !getCostState({{ $cost->id }}).is_confirmed }">
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
                                        {{-- Confirmação - reativo --}}
                                        <td class="px-3 py-2 whitespace-nowrap text-center" @click="toggleExpand({{ $cost->id }})">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="getCostState({{ $cost->id }}).is_confirmed 
                                                      ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                                                      : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'"
                                                  x-text="getCostState({{ $cost->id }}).is_confirmed ? 'Confirmado' : 'Pendente'">
                                            </span>
                                        </td>
                                        {{-- NF - reativo --}}
                                        <td class="px-3 py-2 whitespace-nowrap text-center" @click="toggleExpand({{ $cost->id }})">
                                            <i class="fas"
                                               :class="getCostState({{ $cost->id }}).is_invoice 
                                                   ? 'fa-check-circle text-green-500' 
                                                   : 'fa-times-circle text-gray-400'"
                                               :title="getCostState({{ $cost->id }}).is_invoice ? 'Sim, reembolsável' : 'Não'"></i>
                                        </td>
                                        {{-- Reembolso - reativo com badge de comprovante --}}
                                        <td class="px-3 py-2 whitespace-nowrap text-center" @click="toggleExpand({{ $cost->id }})">
                                            <template x-if="getCostState({{ $cost->id }}).is_invoice">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                          :class="(getCostState({{ $cost->id }}).effective_stage === 'pago' || getCostState({{ $cost->id }}).effective_stage === 'anexo_pendente')
                                                              ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                                                              : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'"
                                                          x-text="(getCostState({{ $cost->id }}).effective_stage === 'pago' || getCostState({{ $cost->id }}).effective_stage === 'anexo_pendente') ? 'Pago' : 'Aguardando'">
                                                    </span>
                                                    {{-- Badge Anexar Comprovante --}}
                                                    <template x-if="getCostState({{ $cost->id }}).effective_stage === 'anexo_pendente' && !getCostState({{ $cost->id }}).proof_number">
                                                        <span class="px-1.5 py-0.5 text-xxs rounded bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400"
                                                              title="Comprovante não anexado">
                                                            <i class="fas fa-paperclip mr-0.5"></i>Pendente
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!getCostState({{ $cost->id }}).is_invoice">
                                                <span class="text-gray-400">-</span>
                                            </template>
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
    Alpine.data('expenseBatchManager', (initialCostsMap, initialTotals) => ({
        selectedCosts: [],
        expandedRows: [],
        paymentDate: '{{ now()->format("Y-m-d") }}',
        submitting: false,
        costsState: initialCostsMap,
        
        // Computed totals - recalcula baseado no estado atual
        get computedTotals() {
            let totalGeral = 0;
            let totalConfirmado = 0;
            let totalPendente = 0;
            let totalReembolsavel = 0;
            let totalReembolsado = 0;
            
            Object.values(this.costsState).forEach(cost => {
                const value = parseFloat(cost.value) || 0;
                totalGeral += value;
                
                if (cost.is_confirmed) {
                    totalConfirmado += value;
                } else {
                    totalPendente += value;
                }
                
                if (cost.is_invoice) {
                    totalReembolsavel += value;
                    if (cost.effective_stage === 'pago' || cost.effective_stage === 'anexo_pendente') {
                        totalReembolsado += value;
                    }
                }
            });
            
            return {
                totalGeral,
                totalConfirmado,
                totalPendente,
                totalReembolsavel,
                totalReembolsado
            };
        },
        
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
        },
        
        getCostState(costId) {
            return this.costsState[costId] || { is_confirmed: false, is_invoice: false, effective_stage: 'aguardando_comprovante', value: 0 };
        },
        
        updateCostState(detail) {
            if (this.costsState[detail.id]) {
                this.costsState[detail.id] = { ...this.costsState[detail.id], ...detail };
            }
        },
        
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
                this.selectedCosts = this.selectedCosts.filter(id => !costIds.map(String).includes(id));
            } else {
                const costIdStrings = costIds.map(String);
                const toAdd = costIdStrings.filter(id => !this.selectedCosts.includes(id));
                this.selectedCosts = [...this.selectedCosts, ...toAdd];
            }
        },
        
        // Analisa as despesas selecionadas e retorna relatório de elegibilidade
        analyzeSelectedCosts(actionType) {
            const eligible = [];
            const ineligible = [];
            
            this.selectedCosts.forEach(costId => {
                const state = this.getCostState(parseInt(costId));
                const costInfo = { id: costId, ...state };
                
                if (actionType === 'settle') {
                    // Para pagar: precisa estar confirmada + NF marcada + não pago ainda
                    if (!state.is_confirmed) {
                        costInfo.reason = 'Não confirmada';
                        ineligible.push(costInfo);
                    } else if (!state.is_invoice) {
                        costInfo.reason = 'NF não marcada';
                        ineligible.push(costInfo);
                    } else if (state.effective_stage === 'pago') {
                        costInfo.reason = 'Já pago';
                        ineligible.push(costInfo);
                    } else {
                        eligible.push(costInfo);
                    }
                } else {
                    // Para reverter: precisa estar pago
                    if (!state.is_invoice) {
                        costInfo.reason = 'Não é reembolsável';
                        ineligible.push(costInfo);
                    } else if (state.effective_stage !== 'pago') {
                        costInfo.reason = 'Não está pago';
                        ineligible.push(costInfo);
                    } else {
                        eligible.push(costInfo);
                    }
                }
            });
            
            return { eligible, ineligible };
        },
        
        // Gera HTML do relatório para o modal
        generateReportHtml(actionType, eligible, ineligible) {
            const actionName = actionType === 'settle' ? 'pagas' : 'revertidas';
            let html = '<div class="text-left text-sm">';
            
            // Resumo
            html += `<div class="mb-4 p-3 bg-gray-100 rounded-lg">`;
            html += `<strong>Resumo:</strong><br>`;
            html += `<span class="text-green-600">✓ ${eligible.length} despesa(s) serão ${actionName}</span><br>`;
            if (ineligible.length > 0) {
                html += `<span class="text-yellow-600">⚠ ${ineligible.length} despesa(s) serão ignoradas</span>`;
            }
            html += `</div>`;
            
            // Lista de inelegíveis (se houver)
            if (ineligible.length > 0) {
                html += `<div class="mb-3">`;
                html += `<strong class="text-yellow-600">Despesas ignoradas:</strong>`;
                html += `<ul class="list-disc pl-5 mt-1 max-h-32 overflow-y-auto">`;
                ineligible.forEach(c => {
                    html += `<li class="text-gray-600">ID #${c.id}: <span class="text-yellow-600">${c.reason}</span></li>`;
                });
                html += `</ul></div>`;
            }
            
            // Lista de elegíveis
            if (eligible.length > 0) {
                html += `<div>`;
                html += `<strong class="text-green-600">Despesas a processar:</strong>`;
                html += `<ul class="list-disc pl-5 mt-1 max-h-32 overflow-y-auto">`;
                eligible.forEach(c => {
                    html += `<li class="text-gray-600">ID #${c.id}</li>`;
                });
                html += `</ul></div>`;
            }
            
            html += '</div>';
            return html;
        },
        
        async submitBatchAction(actionType) {
            if (this.selectedCosts.length === 0) {
                Swal.fire('Atenção!', 'Selecione pelo menos uma despesa.', 'warning');
                return;
            }
            
            if (!this.paymentDate && actionType === 'settle') {
                Swal.fire('Atenção!', 'Por favor, selecione a data do pagamento.', 'warning');
                return;
            }
            
            // Analisar elegibilidade
            const { eligible, ineligible } = this.analyzeSelectedCosts(actionType);
            
            // Se nenhum elegível, mostrar erro
            if (eligible.length === 0) {
                const reasons = [...new Set(ineligible.map(i => i.reason))].join(', ');
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhuma despesa elegível',
                    html: `<p>Nenhuma das ${this.selectedCosts.length} despesa(s) selecionada(s) pode ser processada.</p>
                           <p class="text-sm text-gray-500 mt-2">Motivos: ${reasons}</p>`
                });
                return;
            }
            
            // Gerar relatório
            const reportHtml = this.generateReportHtml(actionType, eligible, ineligible);
            const title = actionType === 'settle' ? 'Confirmar Pagamento em Massa' : 'Reverter Pagamentos em Massa';
            const confirmText = actionType === 'settle' ? `Pagar ${eligible.length} despesa(s)` : `Reverter ${eligible.length} despesa(s)`;
            
            // Mostrar modal com relatório
            const result = await Swal.fire({
                title: title,
                html: reportHtml,
                icon: ineligible.length > 0 ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: actionType === 'settle' ? '#10B981' : '#F59E0B',
                cancelButtonColor: '#6B7280',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                width: '500px'
            });
            
            if (result.isConfirmed) {
                // Atualizar selectedCosts para conter apenas os elegíveis
                this.selectedCosts = eligible.map(c => String(c.id));
                this.submitting = true;
                
                // Aguardar próximo tick para atualizar os inputs hidden
                await this.$nextTick();
                
                const form = actionType === 'settle' ? 'settle-form' : 'unsettle-form';
                document.getElementById(form).submit();
            }
        }
    }));
});
</script>
@endPushOnce