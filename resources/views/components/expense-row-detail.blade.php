@props(['cost'])

@php
    $gig = $cost->gig;
    $effectiveStage = $cost->effective_reimbursement_stage ?? 'aguardando_comprovante';
@endphp

<div class="p-4 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-600">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Card: Informações do Evento --}}
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
        
        {{-- Card: Dados da Despesa --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-receipt mr-2 text-primary-500"></i>Despesa
            </h5>
            <div class="space-y-2">
                <p class="font-medium text-gray-800 dark:text-white">{{ $cost->description ?? 'Sem descrição' }}</p>
                <p class="text-lg font-bold text-primary-600 dark:text-primary-400">
                    {{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Centro: {{ $cost->costCenter->name ?? 'Não definido' }}
                </p>
                <div class="flex items-center gap-2 mt-2">
                    <x-status-badge :status="$cost->is_confirmed ? 'confirmado' : 'pendente'" type="cost-confirmation" />
                    @if($cost->is_invoice)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            <i class="fas fa-file-invoice mr-1"></i>NF
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        {{-- Card: Reembolso (se aplicável) --}}
        @if($cost->is_invoice)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                <i class="fas fa-exchange-alt mr-2 text-primary-500"></i>Reembolso
            </h5>
            <div x-data="{ 
                stage: '{{ $effectiveStage }}',
                loading: false,
                async updateStage(newStage) {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/costs/{{ $cost->id }}/reimbursement-stage', {
                            method: 'PATCH',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ stage: newStage })
                        });
                        if (response.ok) {
                            this.stage = newStage;
                        }
                    } catch (e) {
                        console.error('Erro ao atualizar reembolso:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }" class="space-y-3">
                {{-- Status Atual --}}
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <x-status-badge :status="$effectiveStage" type="reimbursement" x-bind:class="{ 'opacity-50': loading }" />
                </div>
                
                {{-- Ações de Reembolso --}}
                <div class="flex gap-2">
                    <template x-if="stage === 'aguardando_comprovante'">
                        <button @click="updateStage('pago')" 
                                :disabled="loading"
                                class="flex-1 px-3 py-2 text-xs rounded-md bg-green-500 hover:bg-green-600 text-white flex items-center justify-center gap-1 disabled:opacity-50">
                            <i class="fas fa-check-circle"></i>
                            <span x-text="loading ? 'Salvando...' : 'Marcar Pago'"></span>
                        </button>
                    </template>
                    <template x-if="stage === 'pago'">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400 py-1">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-medium text-sm">Reembolsado ✓</span>
                            </div>
                            <button @click="updateStage('aguardando_comprovante')" 
                                    :disabled="loading"
                                    class="w-full px-3 py-1.5 text-xs rounded-md bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1 disabled:opacity-50">
                                <i class="fas fa-undo"></i>
                                <span x-text="loading ? 'Salvando...' : 'Reverter'"></span>
                            </button>
                        </div>
                    </template>
                </div>
                
                {{-- Data de confirmação (se pago) --}}
                @if($cost->reimbursement_confirmed_at)
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Confirmado em: {{ $cost->reimbursement_confirmed_at->isoFormat('L LT') }}
                </p>
                @endif
            </div>
        </div>
        @else
        {{-- Card: Não Reembolsável --}}
        <div class="bg-gray-100 dark:bg-gray-800/50 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-center">
            <div class="text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle text-2xl mb-2"></i>
                <p class="text-sm">Despesa não reembolsável</p>
            </div>
        </div>
        @endif
    </div>
</div>
