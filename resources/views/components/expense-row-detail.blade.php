@props(['cost'])

@php
    $gig = $cost->gig;
    $effectiveStage = $cost->effective_reimbursement_stage ?? 'aguardando_comprovante';
    
    // Preparar dados para JavaScript
    $costData = [
        'id' => $cost->id,
        'gig_id' => $cost->gig_id,
        'description' => $cost->description,
        'value' => $cost->value,
        'currency' => $cost->currency,
        'is_confirmed' => $cost->is_confirmed,
        'is_invoice' => $cost->is_invoice,
        'effective_stage' => $effectiveStage,
        'expense_date' => $cost->expense_date?->format('Y-m-d'),
        'cost_center_name' => $cost->costCenter->name ?? 'Não definido',
    ];
@endphp

{{-- Card de Detalhes Expandido - Inspirado no gigs/_show_costs.blade.php --}}
<div x-data="expenseRowDetail({{ json_encode($costData) }})" 
     class="p-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-600">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
        {{-- Card 1: Informações do Evento --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-primary-500"></i>Evento
            </h5>
            <div class="space-y-2">
                <p class="font-medium text-gray-800 dark:text-white">
                    <a href="{{ route('gigs.show', $gig) }}" class="hover:text-primary-600 dark:hover:text-primary-400">
                        Gig #{{ $gig->id }}
                    </a>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $gig->location_event_details ?? 'Sem detalhes' }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    <i class="far fa-calendar mr-1"></i>{{ $gig->gig_date?->isoFormat('L') ?? '-' }}
                </p>
                @if($gig->artist)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-user mr-1"></i>{{ $gig->artist->name }}
                    </p>
                @endif
            </div>
        </div>
        
        {{-- Card 2: Dados da Despesa --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-receipt mr-2 text-primary-500"></i>Despesa
            </h5>
            <div class="space-y-2">
                <p class="font-medium text-gray-800 dark:text-white" x-text="cost.description || 'Sem descrição'"></p>
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                    <span x-text="cost.currency + ' ' + formatCurrency(cost.value)"></span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Centro: <span x-text="cost.cost_center_name"></span>
                </p>
            </div>
        </div>
        
        {{-- Card 3: Confirmação + NF (Funcionalidades do gigs.show) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-check-square mr-2 text-primary-500"></i>Confirmação & NF
            </h5>
            <div class="space-y-3">
                {{-- Status de Confirmação --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                          :class="cost.is_confirmed 
                              ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                              : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'"
                          x-text="cost.is_confirmed ? 'Confirmado' : 'Pendente'">
                    </span>
                </div>
                
                {{-- Botão Confirmar/Reverter --}}
                <button @click="toggleConfirmation()" 
                        :disabled="loading"
                        class="w-full px-3 py-2 text-xs rounded-md flex items-center justify-center gap-2 disabled:opacity-50"
                        :class="cost.is_confirmed 
                            ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800 dark:bg-yellow-900/50 dark:hover:bg-yellow-900 dark:text-yellow-200' 
                            : 'bg-green-500 hover:bg-green-600 text-white'">
                    <i class="fas" :class="cost.is_confirmed ? 'fa-undo-alt' : 'fa-check-circle'"></i>
                    <span x-text="loading ? 'Salvando...' : (cost.is_confirmed ? 'Reverter Confirmação' : 'Confirmar Despesa')"></span>
                </button>
                
                {{-- Checkbox NF (só habilitado se confirmado) --}}
                <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <input type="checkbox" 
                           :checked="cost.is_invoice" 
                           @change="toggleInvoice()"
                           :disabled="!cost.is_confirmed || loading"
                           class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <label class="text-sm" :class="!cost.is_confirmed ? 'text-gray-400' : 'text-gray-700 dark:text-gray-300'">
                        Marcar para NF (Reembolsável)
                    </label>
                </div>
                <p x-show="!cost.is_confirmed" class="text-xs text-gray-500 italic">
                    * Confirme a despesa primeiro para marcar NF
                </p>
            </div>
        </div>
        
        {{-- Card 4: Reembolso (se NF marcada) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-exchange-alt mr-2 text-primary-500"></i>Reembolso
            </h5>
            
            <template x-if="cost.is_invoice">
                <div class="space-y-3">
                    {{-- Status do Reembolso --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                              :class="cost.effective_stage === 'pago' 
                                  ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' 
                                  : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'">
                            <span x-text="cost.effective_stage === 'pago' ? 'Pago' : 'Aguardando'"></span>
                        </span>
                    </div>
                    
                    {{-- Botão Pagar/Reverter --}}
                    <template x-if="cost.effective_stage === 'aguardando_comprovante'">
                        <button @click="updateReimbursementStage('pago')" 
                                :disabled="loading"
                                class="w-full px-3 py-2 text-xs rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-2 disabled:opacity-50">
                            <i class="fas fa-money-bill-wave"></i>
                            <span x-text="loading ? 'Salvando...' : 'Marcar como Pago'"></span>
                        </button>
                    </template>
                    <template x-if="cost.effective_stage === 'pago'">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-1">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium text-sm">Reembolsado ✓</span>
                            </div>
                            <button @click="updateReimbursementStage('aguardando_comprovante')" 
                                    :disabled="loading"
                                    class="w-full px-3 py-1.5 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1 disabled:opacity-50">
                                <i class="fas fa-undo"></i>
                                <span x-text="loading ? 'Salvando...' : 'Reverter Pagamento'"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
            
            <template x-if="!cost.is_invoice">
                <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p class="text-sm">Marque "NF" para habilitar reembolso</p>
                </div>
            </template>
        </div>
    </div>
</div>

@pushOnce('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('expenseRowDetail', (initialCost) => ({
        cost: initialCost,
        loading: false,
        
        formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
        },
        
        async toggleConfirmation() {
            this.loading = true;
            const url = this.cost.is_confirmed 
                ? `/gigs/${this.cost.gig_id}/costs/${this.cost.id}/unconfirm`
                : `/gigs/${this.cost.gig_id}/costs/${this.cost.id}/confirm`;
            
            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.cost.is_confirmed = !this.cost.is_confirmed;
                    if (!this.cost.is_confirmed) {
                        this.cost.is_invoice = false;
                        this.cost.effective_stage = 'aguardando_comprovante';
                    }
                } else {
                    alert('Erro ao atualizar confirmação');
                }
            } catch (e) {
                console.error('Erro:', e);
                alert('Erro ao atualizar confirmação');
            } finally {
                this.loading = false;
            }
        },
        
        async toggleInvoice() {
            this.loading = true;
            try {
                const response = await fetch(`/gigs/${this.cost.gig_id}/costs/${this.cost.id}/toggle-invoice`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.cost.is_invoice = !this.cost.is_invoice;
                    if (!this.cost.is_invoice) {
                        this.cost.effective_stage = 'aguardando_comprovante';
                    }
                } else {
                    alert('Erro ao atualizar NF');
                }
            } catch (e) {
                console.error('Erro:', e);
                alert('Erro ao atualizar NF');
            } finally {
                this.loading = false;
            }
        },
        
        async updateReimbursementStage(newStage) {
            this.loading = true;
            try {
                const response = await fetch(`/api/costs/${this.cost.id}/reimbursement-stage`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ stage: newStage })
                });
                
                if (response.ok) {
                    this.cost.effective_stage = newStage;
                } else {
                    alert('Erro ao atualizar reembolso');
                }
            } catch (e) {
                console.error('Erro:', e);
                alert('Erro ao atualizar reembolso');
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
@endPushOnce
