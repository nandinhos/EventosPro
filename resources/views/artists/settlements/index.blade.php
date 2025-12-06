<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Fechamentos de Artistas') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Gerencie os pagamentos de cachês aos artistas</p>
    </x-slot>

    <div class="container mx-auto px-4 py-6" x-data="settlementsManager()">
        {{-- Cards de Resumo --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg shadow">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Pendentes</h3>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $pendingCount }}</p>
            </div>
            <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg shadow">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Pagos</h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $paidCount }}</p>
            </div>
            <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg shadow">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Pendente</h3>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">R$ {{ number_format($pendingTotal, 2, ',', '.') }}</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
            <form method="GET" action="{{ route('artists.settlements.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Busca Livre</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Artista, booker, local, ID..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista</label>
                    <select name="artist_id" id="artist_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        @foreach ($artists as $artist)
                            <option value="{{ $artist->id }}" {{ request('artist_id') == $artist->id ? 'selected' : '' }}>{{ $artist->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento (De)</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="date_until" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento (Até)</label>
                    <input type="date" name="date_until" id="date_until" value="{{ request('date_until') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Fechamento</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="pendente" {{ request('status') == 'pendente' ? 'selected' : '' }}>Pendente</option>
                        <option value="pago" {{ request('status') == 'pago' ? 'selected' : '' }}>Pago</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex gap-2">
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="{{ route('artists.settlements.index') }}" class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-times mr-2"></i>Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- Ações em Massa --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-grow sm:flex-grow-0">
                    <label for="payment_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento</label>
                    <input type="date" id="payment_date" x-model="paymentDate" :max="today"
                           class="w-full sm:w-auto rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <button type="button" @click="submitBatchAction('pay')"
                        :disabled="selectedGigs.length === 0"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-check-double mr-2"></i>Pagar Selecionados (<span x-text="selectedGigs.length"></span>)
                </button>
                <button type="button" @click="submitBatchAction('unsettle')"
                        :disabled="selectedGigs.length === 0"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-4 py-2 rounded-md text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-undo-alt mr-2"></i>Reverter Selecionados (<span x-text="selectedGigs.length"></span>)
                </button>
            </div>

            {{-- Hidden Forms --}}
            <form id="batchPaymentForm" action="{{ route('artists.settlements.settleBatch', request()->query()) }}" method="POST" class="hidden">
                @csrf
            </form>
            <form id="batchUnsettleForm" action="{{ route('artists.settlements.unsettleBatch', request()->query()) }}" method="POST" class="hidden">
                @method('PATCH')
                @csrf
            </form>
        </div>

        {{-- Tabela de Fechamentos --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-3 py-3 w-10">
                                <input type="checkbox" 
                                       @change="toggleSelectAll($event.target.checked)"
                                       :checked="areAllSelected()"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Artista / Booker</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Local</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Moeda</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cachê (R$)</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($gigs as $gig)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-3 py-2">
                                    <input type="checkbox" 
                                           value="{{ $gig->id }}"
                                           x-model="selectedGigs"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $gig->gig_date->isoFormat('L') }}
                                </td>
                                {{-- Coluna 1: Artista / Booker --}}
                                <td class="px-4 py-2">
                                    <div class="text-xs font-bold text-gray-900 dark:text-white uppercase">
                                        {{ $gig->artist->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs italic text-gray-500 dark:text-gray-400 uppercase">
                                        {{ $gig->booker->name ?? 'N/A' }}
                                    </div>
                                </td>
                                {{-- Coluna 2: Gig / Local --}}
                                <td class="px-4 py-2">
                                    <div>
                                        <a href="{{ route('gigs.show', $gig) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium" title="Ver detalhes da Gig">
                                            @if($gig->contract_number)
                                                #{{ $gig->contract_number }}
                                            @else
                                                #{{ $gig->id }}
                                            @endif
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $gig->location_event_details ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-center text-xs text-gray-700 dark:text-gray-300 uppercase">
                                    {{ $gig->currency ?? 'BRL' }}
                                </td>
                                <td class="px-4 py-2 text-xs text-right font-mono text-gray-700 dark:text-gray-200">
                                    R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    @if ($gig->artist_payment_status === 'pago')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            Pago
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                            Pendente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-search text-4xl mb-3 opacity-50"></i>
                                    <p>Nenhum fechamento encontrado com os filtros aplicados.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            @if ($gigs->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $gigs->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function settlementsManager() {
            return {
                selectedGigs: [],
                paymentDate: '{{ now()->format('Y-m-d') }}',
                today: '{{ now()->format('Y-m-d') }}',
                allGigIds: @json($gigs->pluck('id')->toArray()),

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

                formatDate(dateString) {
                    const date = new Date(dateString + 'T00:00:00');
                    return date.toLocaleDateString('pt-BR');
                },

                submitBatchAction(actionType) {
                    if (this.selectedGigs.length === 0) {
                        Swal.fire('Atenção!', 'Nenhum fechamento selecionado.', 'warning');
                        return;
                    }

                    let form, confirmationText;

                    if (actionType === 'pay') {
                        if (!this.paymentDate) {
                            Swal.fire('Atenção!', 'Por favor, selecione a data do pagamento.', 'warning');
                            return;
                        }
                        form = document.getElementById('batchPaymentForm');
                        confirmationText = `Confirmar pagamento de ${this.selectedGigs.length} fechamento(s) com data ${this.formatDate(this.paymentDate)}?`;
                    } else {
                        form = document.getElementById('batchUnsettleForm');
                        confirmationText = `Confirmar a reversão de ${this.selectedGigs.length} fechamento(s) para "Pendente"?`;
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
                            // Clear existing hidden inputs
                            form.querySelectorAll('input[name^="gig_ids"]').forEach(el => el.remove());
                            form.querySelectorAll('input[name="payment_date"]').forEach(el => el.remove());

                            // Add selected gigs
                            this.selectedGigs.forEach(gigId => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'gig_ids[]';
                                input.value = gigId;
                                form.appendChild(input);
                            });

                            // Add payment date for pay action
                            if (actionType === 'pay') {
                                const dateInput = document.createElement('input');
                                dateInput.type = 'hidden';
                                dateInput.name = 'payment_date';
                                dateInput.value = this.paymentDate;
                                form.appendChild(dateInput);
                            }

                            form.submit();
                        }
                    });
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
