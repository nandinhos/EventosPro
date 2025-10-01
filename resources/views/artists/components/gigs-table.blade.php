<div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-md" x-data="gigsTableManager()">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
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
            @forelse ($gigs as $gig)
                @php
                    $gigCosts = $gig->costs;
                    $confirmedCosts = $gigCosts->where('is_confirmed', true);
                    $pendingCosts = $gigCosts->where('is_confirmed', false);
                    $totalConfirmed = $confirmedCosts->sum('value_brl');
                    $totalPending = $pendingCosts->sum('value_brl');
                    $hasExpenses = $gigCosts->count() > 0;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600/50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $gig->gig_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {{ $gig->location_event_details }}
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
                        <td colspan="7" class="px-6 py-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
                                <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                                    <h4 class="text-sm font-medium text-gray-800 dark:text-white">Despesas do Evento</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        O pagamento do artista só pode ser realizado quando todas as despesas estiverem confirmadas.
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
                                                        @if ($cost->is_confirmed)
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                Confirmado
                                                            </span>
                                                        @else
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                                Pendente
                                                            </span>
                                                        @endif
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
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        Nenhum evento encontrado para este período.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    function gigsTableManager() {
        return {
            expandedGigs: [],
            
            toggleExpenses(gigId) {
                const index = this.expandedGigs.indexOf(gigId);
                if (index > -1) {
                    this.expandedGigs.splice(index, 1);
                } else {
                    this.expandedGigs.push(gigId);
                }
            }
        };
    }
</script>
@endpush