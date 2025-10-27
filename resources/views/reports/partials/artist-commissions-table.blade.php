@php
    $artistGroups = $artistCommissionsReport['groups'] ?? collect([]);
    $artistSummary = $artistCommissionsReport['summary'] ?? [];
@endphp

<div x-data="artistPaymentBatchManager" class="space-y-6 mt-4">

    {{-- Cards de Resumo --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Base de Cálculo</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($artistSummary['total_payout_base'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-indigo-100 dark:bg-indigo-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total a Pagar (Artistas)</h3>
            <p class="text-lg font-semibold text-indigo-800 dark:text-indigo-300">R$ {{ number_format($artistSummary['total_payouts'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-cyan-100 dark:bg-cyan-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Eventos com Cachê</h3>
            <p class="text-lg font-semibold text-cyan-800 dark:text-cyan-300">{{ $artistSummary['events_with_payouts'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Formulário e Botões para Ações em Massa --}}
    <div class="mb-6">
        {{-- Formulário para PAGAR em Massa --}}
        <form id="artistBatchPaymentForm" action="{{ route('reports.artist-payments.settleBatch') }}" method="POST" class="hidden">
            @csrf
        </form>

        {{-- Formulário para REVERTER em Massa --}}
        <form id="artistBatchUnsettleForm" action="{{ route('reports.artist-payments.unsettleBatch') }}" method="POST" class="hidden">
            @method('PATCH')
            @csrf
        </form>

    <div class="flex flex-wrap items-end gap-4 p-4 bg-gray-50 dark:bg-gray-800/30 rounded-lg border dark:border-gray-700">
        {{-- Campo de Data --}}
        <div class="flex-grow sm:flex-grow-0">
            <label for="artist_batch_payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data da Ação em Massa</label>
            <input type="date" id="artist_batch_payment_date" x-model="paymentDate" required
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

{{-- Tabela Agrupada por Artista --}}
@if ($artistGroups->isNotEmpty())
    <div class="space-y-6">
        @foreach ($artistGroups as $group)
            @php
                $allGigIdsInGroup = collect($group['gigs'])->pluck('id')->all();
            @endphp
            <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border dark:border-gray-700">
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-t-lg flex justify-between items-center">
                    <div class="flex items-center">
                        {{-- Checkbox "Selecionar Todos" --}}
                        <input type="checkbox"
                               title="Selecionar/Desselecionar todos para {{ $group['artist_name'] }}"
                               :checked="areAllSelectedForArtist({{ Js::from($allGigIdsInGroup) }})"
                               @click="toggleSelectAllForArtist({{ Js::from($allGigIdsInGroup) }})"
                               class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 mr-3">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-white">{{ $group['artist_name'] }}</h4>
                    </div>

                </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-1 py-2 w-8"></th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Data da Gig</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Booker</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Local</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Base de Cálculo (R$)</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Valor Total a Pagar (R$)</th>
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
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $gig->gig_date ? $gig->gig_date->format('d/m/Y') : 'N/A' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap font-semibold">{{ $gig->booker->name ?? 'N/A' }}</td>
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
                                        <td class="px-3 py-2 whitespace-nowrap text-right">
                                            <div class="font-bold">{{ number_format($gig->calculated_total_artist_payment, 2, ',', '.') }}</div>
                                            @if($gig->calculated_reimbursable_expenses > 0)
                                                <div class="text-xxs text-gray-500 dark:text-gray-400">
                                                    + R$ {{ number_format($gig->calculated_reimbursable_expenses, 2, ',', '.') }} reemb.
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center">
                                            <x-status-badge :status="$gig->artist_payment_status" type="payment-internal" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 dark:bg-gray-800/80 border-t-2 border-gray-300 dark:border-gray-600">
                            <tr class="font-bold">

                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200" colspan="2">
                                    TOTAIS {{ strtoupper($group['artist_name']) }}
                                </td>
                                <td></td> {{-- Célula vazia para alinhar --}}
                                <td class="px-3 py-2 text-center text-gray-800 dark:text-white">
                                    {{ $group['gig_count'] }} Gigs
                                </td>

                                <td class="px-3 py-2 text-right text-gray-800 dark:text-white">
                                    R$ {{ number_format($group['total_payout_base'], 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 text-right text-primary-600 dark:text-primary-400">
                                    R$ {{ number_format($group['total_payout_value'], 2, ',', '.') }}
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
            Nenhum cachê de artista encontrado para os filtros selecionados.
        </p>
    @endif
</div>

@pushOnce('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('artistPaymentBatchManager', () => ({
            selectedGigs: [],
            paymentDate: '{{ now()->format('Y-m-d') }}',

            areAllSelectedForArtist(allGigIdsInGroup) {
                if (!allGigIdsInGroup || allGigIdsInGroup.length === 0) return false;
                return allGigIdsInGroup.every(id => this.selectedGigs.includes(id));
            },

            toggleSelectAllForArtist(allGigIdsInGroup) {
                const allSelected = this.areAllSelectedForArtist(allGigIdsInGroup);
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

            submitBatchAction(actionType) {
                if (this.selectedGigs.length === 0) {
                    Swal.fire('Atenção!', 'Nenhum cachê selecionado.', 'warning');
                    return;
                }
                if (!this.paymentDate && actionType === 'pay') {
                    Swal.fire('Atenção!', 'Por favor, selecione a data do pagamento.', 'warning');
                    return;
                }

                let form;
                let confirmationText;

                const currentUrlParams = new URLSearchParams(window.location.search);
                const filterParams = {
                    start_date: currentUrlParams.get('start_date') || '',
                    end_date: currentUrlParams.get('end_date') || '',
                    booker_id: currentUrlParams.get('booker_id') || '',
                    artist_id: currentUrlParams.get('artist_id') || '',
                };

                Object.keys(filterParams).forEach(key => filterParams[key] === '' && delete filterParams[key]);
                const queryString = new URLSearchParams(filterParams).toString();

                if (actionType === 'pay') {
                    form = document.getElementById('artistBatchPaymentForm');
                    form.action = `{{ route('reports.artist-payments.settleBatch') }}?${queryString}`;
                    confirmationText = `Confirmar pagamento de ${this.selectedGigs.length} cachês com data ${new Date(this.paymentDate + 'T00:00:00').toLocaleDateString('pt-BR')}?`;
                } else { // 'unsettle'
                    form = document.getElementById('artistBatchUnsettleForm');
                    form.action = `{{ route('reports.artist-payments.unsettleBatch') }}?${queryString}`;
                    confirmationText = `Confirmar a reversão de ${this.selectedGigs.length} cachês para "Pendente"?`;
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
                        Array.from(form.querySelectorAll('input[name^="gig_ids"]')).forEach(el => el.remove());
                        Array.from(form.querySelectorAll('input[name="payment_date"]')).forEach(el => el.remove());

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
