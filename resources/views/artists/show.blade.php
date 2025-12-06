<x-app-layout>
    <div class="max-w-9xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg">
            {{-- Cabeçalho --}}
            <div class="p-4 sm:px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $artist->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Painel administrativo do artista</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('artists.index') }}" class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-arrow-left mr-2"></i>Voltar
                        </a>
                        <a href="{{ route('artists.edit', $artist) }}" class="inline-flex items-center px-3 py-1.5 bg-gray-800 dark:bg-gray-700 rounded-md text-xs font-medium text-white hover:bg-gray-700 dark:hover:bg-gray-600">
                            <i class="fas fa-pen mr-2"></i>Editar
                        </a>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="p-4 sm:px-6 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <form method="GET" action="{{ route('artists.show', $artist) }}" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-grow">
                        <label for="search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Busca Livre</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                               placeholder="Booker, local, contrato..."
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label for="start_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">De</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" 
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label for="end_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Até</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" 
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-xs font-medium">
                            <i class="fas fa-search mr-2"></i>Filtrar
                        </button>
                        <a href="{{ route('artists.show', $artist) }}" class="inline-flex items-center px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md text-xs font-medium">
                            <i class="fas fa-times mr-1"></i>Limpar
                        </a>
                    </div>
                </form>
            </div>

            {{-- Cards de Métricas --}}
            <div class="p-4 sm:px-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border-l-4 border-blue-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total de Gigs</p>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $realizedGigs->count() + $futureGigs->count() }}</p>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 border-l-4 border-purple-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Cachê Bruto</p>
                        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">R$ {{ number_format($metrics['totalGrossFee'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border-l-4 border-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Líquido Recebido</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($metrics['cache_received_brl'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 border-l-4 border-yellow-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">A Receber</p>
                        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">R$ {{ number_format($metrics['cache_pending_brl'] ?? 0, 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Tabela de Eventos --}}
            <div class="p-4 sm:px-6">
                @php
                    // Combina gigs realizados + futuros e aplica busca
                    $allGigs = $realizedGigs->concat($futureGigs)->sortByDesc('gig_date');
                    
                    // Aplica filtro de busca se houver
                    if (request('search')) {
                        $search = strtolower(request('search'));
                        $allGigs = $allGigs->filter(function ($gig) use ($search) {
                            return str_contains(strtolower($gig->booker->name ?? ''), $search) ||
                                   str_contains(strtolower($gig->location_event_details ?? ''), $search) ||
                                   str_contains(strtolower($gig->contract_number ?? ''), $search) ||
                                   str_contains((string) $gig->id, $search);
                        });
                    }
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Data</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Booker / Local</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gig</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cachê (R$)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($allGigs as $gig)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            {{ $gig->gig_date->isoFormat('L') }}
                                            @if($gig->gig_date->isFuture())
                                                <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Futuro</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-xs font-medium text-gray-900 dark:text-white">
                                                {{ $gig->booker->name ?? 'N/A' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $gig->location_event_details ?: '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <a href="{{ route('gigs.show', $gig) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                                @if($gig->contract_number)
                                                    #{{ $gig->contract_number }}
                                                @else
                                                    #{{ $gig->id }}
                                                @endif
                                            </a>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <div class="flex justify-center space-x-1" title="Cliente | Despesas | Artista">
                                                @php
                                                    $hasExpenses = $gig->gigCosts->count() > 0;
                                                    $confirmedCosts = $gig->gigCosts->where('is_confirmed', true)->sum('value_brl');
                                                @endphp
                                                <i class="fas fa-dollar-sign text-xs {{ $gig->payment_status === 'pago' ? 'text-green-500' : ($gig->payment_status === 'vencido' ? 'text-red-500' : 'text-gray-400') }}" title="Pagamento Cliente"></i>
                                                <i class="fas fa-receipt text-xs {{ $hasExpenses && $confirmedCosts > 0 ? 'text-green-500' : ($hasExpenses ? 'text-yellow-500' : 'text-gray-400') }}" title="Despesas"></i>
                                                <i class="fas fa-user-check text-xs {{ $gig->artist_payment_status === 'pago' ? 'text-green-500' : 'text-gray-400' }}" title="Pagamento Artista"></i>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-right font-mono text-gray-700 dark:text-gray-200">
                                            R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-calendar-times text-3xl mb-2 opacity-50"></i>
                                            <p class="text-sm">Nenhum evento encontrado para este período.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>