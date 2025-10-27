@php
    $commissionGroups = $commissionsReport['groups'] ?? collect([]);
    $commissionsSummary = $commissionsReport['summary'] ?? [];
@endphp

<div x-data="commissionBatchManager" class="space-y-6 mt-4">

    {{-- ***** INÍCIO DA ALTERAÇÃO NO GRID DE CARDS ***** --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Base de Cálculo</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($commissionsSummary['total_commission_base'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-indigo-100 dark:bg-indigo-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Comissões (Bookers)</h3>
            <p class="text-lg font-semibold text-indigo-800 dark:text-indigo-300">R$ {{ number_format($commissionsSummary['total_commissions'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-cyan-100 dark:bg-cyan-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Eventos com Comissão</h3>
            <p class="text-lg font-semibold text-cyan-800 dark:text-cyan-300">{{ $commissionsSummary['events_with_commissions'] ?? 0 }}</p>
        </div>
    </div>
    {{-- ***** FIM DA ALTERAÇÃO NO GRID DE CARDS ***** --}}

    {{-- Formulário e Botões para Ações em Massa --}}
    <div class="mb-6">
        {{-- Formulário para PAGAR em Massa --}}
        <form id="batchPaymentForm" action="{{ route('reports.commissions.settleBatch') }}" method="POST" class="hidden">
            @csrf
            {{-- ***** REMOVER OS INPUTS HIDDEN DE FILTRO DAQUI ***** --}}
        </form>
        
        {{-- Formulário para REVERTER em Massa --}}
        <form id="batchUnsettleForm" action="{{ route('reports.commissions.unsettleBatch') }}" method="POST" class="hidden">
            @method('PATCH')
            @csrf
            {{-- ***** REMOVER OS INPUTS HIDDEN DE FILTRO DAQUI ***** --}}
        </form>

    <div class="flex flex-wrap items-end gap-4 p-4 bg-gray-50 dark:bg-gray-800/30 rounded-lg border dark:border-gray-700">
        {{-- Campo de Data --}}
        <div class="flex-grow sm:flex-grow-0">
            <label for="batch_payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Ação em Massa</label>
            <input type="date" id="batch_payment_date" x-model="paymentDate" required
                   class="mt-1 block w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white text-sm">
        </div>
        
        {{-- Botão Pagar --}}
        <button type="button" @click="submitBatchAction('pay')"
                :disabled="selectedGigs.length === 0"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-check-double mr-2"></i>Pagar Selecionados (<span x-text="selectedGigs.length"></span>)
        </button>

        {{-- Botão Reverter --}}
        <button type="button" @click="submitBatchAction('unsettle')"
                :disabled="selectedGigs.length === 0"
                class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-undo-alt mr-2"></i>Reverter Selecionados (<span x-text="selectedGigs.length"></span>)
        </button>
    </div>
</div>

{{-- Tabela Agrupada por Booker --}}
@if ($commissionGroups->isNotEmpty())
    <div class="space-y-6">
        @foreach ($commissionGroups as $group)
            @php
                // Agora pegamos TODOS os IDs do grupo para a lógica do checkbox "Selecionar Todos"
                $allGigIdsInGroup = collect($group['gigs'])->pluck('id')->all();
            @endphp
            <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border dark:border-gray-700">
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-t-lg flex justify-between items-center">
                    <div class="flex items-center">
                        {{-- ***** CHECKBOX "SELECIONAR TODOS" REINTRODUZIDO ***** --}}
                        <input type="checkbox"
                               title="Selecionar/Desselecionar todos para {{ $group['booker_name'] }}"
                               :checked="areAllSelectedForBooker({{ Js::from($allGigIdsInGroup) }})"
                               @click="toggleSelectAllForBooker({{ Js::from($allGigIdsInGroup) }})"
                               class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 mr-3">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-white">{{ $group['booker_name'] }}</h4>
                    </div>
                  
                </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-1 py-2 w-8"></th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Data da Gig</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Artista</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Local</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Base de Cálculo (R$)</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Comissão (R$)</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Status Pgto.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($group['gigs'] as $gig)
                                    <tr>
                                        <td class="px-1 py-2 text-center">
                                           
                                                <input type="checkbox" value="{{ $gig->id }}"
                                                       x-model="selectedGigs"
                                                       class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                           
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $gig->gig_date ? $gig->gig_date->isoFormat('L') : 'N/A' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap font-semibold">{{ $gig->artist->name ?? 'N/A' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <a href="{{ route('gigs.show', $gig) }}" class="text-primary-600 hover:underline" title="Ver detalhes da Gig">
                                                Gig #{{ $gig->id }}
                                            </a>
                                            @if($gig->location_event_details)
                                                <span class="block text-gray-500 dark:text-gray-400 italic text-xxs truncate max-w-[150px]" title="{{ $gig->location_event_details }}">
                                                    {{ Str::limit($gig->location_event_details, 30) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right text-gray-500">{{ number_format($gig->calculated_gross_cash_brl, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right font-bold">{{ number_format($gig->calculated_booker_commission, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center">
                                            <x-status-badge :status="$gig->booker_payment_status" type="payment-internal" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 dark:bg-gray-800/80 border-t-2 border-gray-300 dark:border-gray-600">
                            <tr class="font-bold">
                            
                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200" colspan="2">
                                    TOTAIS {{ strtoupper($group['booker_name']) }}
                                </td>
                                <td></td> {{-- Célula vazia para alinhar --}}
                                <td class="px-3 py-2 text-center text-gray-800 dark:text-white">
                                    {{ $group['gig_count'] }} Gigs
                                </td>
                              
                                <td class="px-3 py-2 text-right text-gray-800 dark:text-white">
                                    R$ {{ number_format($group['total_commission_base'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-right text-primary-600 dark:text-primary-400">
                                    R$ {{ number_format($group['total_commission_value'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-2"></td> {{-- Célula vazia para alinhar --}}
                            </tr>
                        </tfoot>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
            Nenhuma comissão de booker encontrada para os filtros selecionados.
        </p>
    @endif
</div>

@pushOnce('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('commissionBatchManager', () => ({
            selectedGigs: [],
            paymentDate: '{{ now()->format('Y-m-d') }}',

            // ... (métodos areAllSelectedForBooker e toggleSelectAllForBooker como antes) ...
            areAllSelectedForBooker(allGigIdsInGroup) {
                if (!allGigIdsInGroup || allGigIdsInGroup.length === 0) return false;
                return allGigIdsInGroup.every(id => this.selectedGigs.includes(id));
            },

            toggleSelectAllForBooker(allGigIdsInGroup) {
                const allSelected = this.areAllSelectedForBooker(allGigIdsInGroup);
                if (allSelected) {
                    this.selectedGigs = this.selectedGigs.filter(id => !allGigIdsInGroup.includes(id));
                } else {
                    allGigIdsInGroup.forEach(id => {
                        if (!this.selectedGigs.includes(id)) {
                            this.selectedGigs.push(id);
                        }
                    });
                }
            },

            // Lógica para submeter o formulário
            submitBatchAction(actionType) {
                if (this.selectedGigs.length === 0) {
                    Swal.fire('Atenção!', 'Nenhuma comissão selecionada.', 'warning');
                    return;
                }
                if (!this.paymentDate && actionType === 'pay') {
                    Swal.fire('Atenção!', 'Por favor, selecione a data do pagamento.', 'warning');
                    return;
                }

                let form;
                let confirmationText;

                // ***** INÍCIO DA ALTERAÇÃO *****
                // 1. Pega os parâmetros de filtro da URL atual
                const currentUrlParams = new URLSearchParams(window.location.search);
                const filterParams = {
                    start_date: currentUrlParams.get('start_date') || '',
                    end_date: currentUrlParams.get('end_date') || '',
                    booker_id: currentUrlParams.get('booker_id') || '',
                    artist_id: currentUrlParams.get('artist_id') || '',
                };

                // Remove parâmetros vazios para não poluir a URL
                Object.keys(filterParams).forEach(key => filterParams[key] === '' && delete filterParams[key]);
                const queryString = new URLSearchParams(filterParams).toString();
                // ***** FIM DA ALTERAÇÃO *****

                if (actionType === 'pay') {
                    form = document.getElementById('batchPaymentForm');
                    // 2. Constrói a action com a query string
                    form.action = `{{ route('reports.commissions.settleBatch') }}?${queryString}`;
                    confirmationText = `Confirmar pagamento de ${this.selectedGigs.length} comissões com data ${new Date(this.paymentDate + 'T00:00:00').toLocaleDateString('pt-BR')}?`;
                } else { // 'unsettle'
                    form = document.getElementById('batchUnsettleForm');
                    // 2. Constrói a action com a query string
                    form.action = `{{ route('reports.commissions.unsettleBatch') }}?${queryString}`;
                    confirmationText = `Confirmar a reversão de ${this.selectedGigs.length} comissões para "Pendente"?`;
                }

                Swal.fire({
                    title: 'Confirmar Ação',
                    text: confirmationText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, confirmar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Limpa inputs antigos e adiciona os novos antes de submeter
                        Array.from(form.querySelectorAll('input[name^="gig_ids"]')).forEach(el => el.remove());
                        Array.from(form.querySelectorAll('input[name="payment_date"]')).forEach(el => el.remove());
                        
                        // O token CSRF e o _method já estão no formulário no Blade
                        
                        this.selectedGigs.forEach(gigId => {
                            form.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'gig_ids[]', value: gigId }));
                        });
                        
                        if (actionType === 'pay') {
                            form.appendChild(Object.assign(document.createElement('input'), { type: 'hidden', name: 'payment_date', value: this.paymentDate }));
                        }
                        
                        form.submit();
                    }
                });
            }
        }));
    });
</script>
@endPushOnce