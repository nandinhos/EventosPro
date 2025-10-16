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
    </form>

    <form id="batchUnpaymentForm" method="POST" action="{{ route('artists.payments.unsettleBatch') }}" style="display: none;">
        @csrf
        @method('PATCH')
        <template x-for="gigId in selectedGigs" :key="gigId">
            <input type="hidden" name="gig_ids[]" :value="gigId">
        </template>
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

                        {{-- Subtabela de Despesas Expansível --}}
                        @if ($hasExpenses)
                            <tr x-show="expandedGigs.includes({{ $gig->id }})" x-transition class="bg-gray-50 dark:bg-gray-700/30">
                                <td colspan="8" class="px-6 py-4">
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                                        <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                            <h5 class="text-sm font-medium text-gray-800 dark:text-white">Despesas do Evento #{{ $gig->id }}</h5>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                Clique no status para confirmar/desconfirmar despesas. O pagamento só pode ser realizado com todas as despesas confirmadas.
                                            </p>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-xs">
                                                <thead class="bg-gray-50 dark:bg-gray-700">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Centro de Custo</th>
                                                        <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                                                        <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                                                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NF</th>
                                                        <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-600">
                                                    @foreach ($gigCosts as $cost)
                                                        <tr class="{{ !$cost->is_confirmed ? 'bg-yellow-50 dark:bg-yellow-900/10' : '' }}">
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                                {{ $cost->costCenter->name ?? 'N/A' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                                {{ $cost->description ?? '-' }}
                                                            </td>
                                                            <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                                                {{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}
                                                            </td>
                                                            <td class="px-3 py-2 text-center">
                                                                @if ($cost->is_invoice)
                                                                    <i class="fas fa-check text-green-500" title="Incluído na NF do Artista"></i>
                                                                @else
                                                                    <i class="fas fa-times text-gray-400" title="Não incluído na NF"></i>
                                                                @endif
                                                            </td>
                                                            <td class="px-3 py-2 text-center">
                                                                <form method="POST" action="{{ route('gigs.costs.'.($cost->is_confirmed ? 'unconfirm' : 'confirm'), [$gig, $cost]) }}" style="display: inline;">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit"
                                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer transition-colors
                                                                            {{ $cost->is_confirmed ? 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:hover:bg-green-800' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900 dark:text-yellow-200 dark:hover:bg-yellow-800' }}"
                                                                            title="Clique para {{ $cost->is_confirmed ? 'desconfirmar' : 'confirmar' }}">
                                                                        {{ $cost->is_confirmed ? 'Confirmado' : 'Pendente' }}
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
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
            paymentDate: '{{ now()->format('Y-m-d') }}',
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
</script>
@endpush
