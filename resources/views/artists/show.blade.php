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

            {{-- Lista de Eventos Interativa --}}
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

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    {{-- Cabeçalho --}}
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 rounded-t-lg">
                        <div class="flex items-center justify-between text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <div class="flex items-center space-x-4">
                                <span class="w-20">Data</span>
                                <span class="w-32">Booker</span>
                                <span>Gig / Local</span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="w-16 text-center">Status</span>
                                <span class="w-24 text-right">Cachê</span>
                                <span class="w-4"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Lista de Eventos --}}
                    <div class="p-4">
                    @forelse ($allGigs as $gig)
                        @php
                            $gigCosts = $gig->gigCosts;
                            $confirmedCosts = $gigCosts->where('is_confirmed', true);
                            $pendingCosts = $gigCosts->where('is_confirmed', false);
                            $totalConfirmed = $confirmedCosts->sum('value_brl');
                            $totalPending = $pendingCosts->sum('value_brl');
                            $hasExpenses = $gigCosts->count() > 0;
                        @endphp

                        <div x-data="{ open: false }" class="py-2 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                            {{-- Linha Resumida Clicável --}}
                            <div @click="open = !open" class="flex items-center justify-between cursor-pointer group hover:bg-gray-50 dark:hover:bg-gray-700/30 -mx-2 px-2 py-1 rounded">
                                {{-- Coluna Esquerda: Data, Booker, Gig/Local --}}
                                <div class="flex items-center space-x-4">
                                    {{-- Data --}}
                                    <div class="w-20 text-xs text-gray-700 dark:text-gray-300">
                                        {{ $gig->gig_date->isoFormat('DD/MM/YY') }}
                                        @if($gig->gig_date->isFuture())
                                            <span class="block text-[9px] text-blue-600 dark:text-blue-400 font-medium">Futuro</span>
                                        @endif
                                    </div>
                                    {{-- Booker --}}
                                    <div class="w-32">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white uppercase truncate">{{ $gig->booker->name ?? 'N/A' }}</p>
                                    </div>
                                    {{-- Gig / Local --}}
                                    <div>
                                        <a href="{{ route('gigs.show', $gig->id) }}" @click.stop class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline" title="Ver detalhes da Gig">
                                            @if($gig->contract_number)
                                                #{{ $gig->contract_number }}
                                            @else
                                                #{{ $gig->id }}
                                            @endif
                                        </a>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($gig->location_event_details, 35) ?: '-' }}</p>
                                    </div>
                                </div>

                                {{-- Coluna Direita: Status, Valor, Chevron --}}
                                <div class="flex items-center space-x-4">
                                    {{-- Ícones de Status --}}
                                    <div class="w-16 flex justify-center space-x-1" title="Cliente | Despesas | Artista">
                                        <i class="fas fa-dollar-sign fa-fw text-xs {{ $gig->payment_status === 'pago' ? 'text-green-500' : ($gig->payment_status === 'vencido' ? 'text-red-500' : 'text-gray-400') }}"></i>
                                        <i class="fas fa-receipt fa-fw text-xs {{ $hasExpenses && $totalConfirmed > 0 ? 'text-green-500' : ($hasExpenses ? 'text-yellow-500' : 'text-gray-400') }}"></i>
                                        <i class="fas fa-user-check fa-fw text-xs {{ $gig->artist_payment_status === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                                    </div>
                                    {{-- Valor --}}
                                    <div class="w-24 text-right">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</p>
                                    </div>
                                    {{-- Indicador de expansão --}}
                                    <i class="fas fa-chevron-down w-4 text-xs text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                </div>
                            </div>

                            {{-- Área Expandida com Detalhes --}}
                            <div x-show="open" x-collapse class="mt-4 pl-8 border-l-2 border-gray-200 dark:border-gray-700 ml-2">
                                <div class="space-y-2 text-xs">
                                    {{-- Status --}}
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 rounded-full {{ $gig->payment_status === 'pago' ? 'bg-green-500' : ($gig->payment_status === 'vencido' ? 'bg-red-500' : 'bg-gray-400') }} mr-2"></span>
                                        <span class="font-medium text-gray-600 dark:text-gray-400 w-32">Pagamento Cliente:</span>
                                        <span class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($gig->payment_status) }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 rounded-full {{ $gig->artist_payment_status === 'pago' ? 'bg-green-500' : 'bg-gray-400' }} mr-2"></span>
                                        <span class="font-medium text-gray-600 dark:text-gray-400 w-32">Repasse Artista:</span>
                                        <span class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($gig->artist_payment_status) }}</span>
                                    </div>

                                    {{-- Cachê Líquido --}}
                                    <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                        <span class="font-medium text-gray-600 dark:text-gray-400 w-32">Cachê Líquido:</span>
                                        <span class="font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</span>
                                    </div>

                                    {{-- Despesas --}}
                                    @if ($hasExpenses)
                                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-xs font-medium text-gray-800 dark:text-white">Despesas do Evento</h4>
                                                <div class="text-xs space-x-2">
                                                    @if ($totalConfirmed > 0)
                                                        <span class="text-green-600 dark:text-green-400">Conf: R$ {{ number_format($totalConfirmed, 2, ',', '.') }}</span>
                                                    @endif
                                                    @if ($totalPending > 0)
                                                        <span class="text-yellow-600 dark:text-yellow-400">Pend: R$ {{ number_format($totalPending, 2, ',', '.') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 space-y-2">
                                                @foreach ($gigCosts as $cost)
                                                    <div class="flex items-center justify-between text-xs {{ !$cost->is_confirmed ? 'bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded' : '' }}">
                                                        <div class="flex-1">
                                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $cost->costCenter->name ?? 'N/A' }}</span>
                                                            @if($cost->description)
                                                                <span class="text-gray-500 dark:text-gray-400"> - {{ $cost->description }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="font-mono text-gray-700 dark:text-gray-300">
                                                                {{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}
                                                            </span>
                                                            @if ($cost->is_invoice)
                                                                <i class="fas fa-file-invoice text-green-500" title="Incluído na NF"></i>
                                                            @endif
                                                            @if ($cost->is_confirmed)
                                                                <i class="fas fa-check-circle text-green-500" title="Confirmado"></i>
                                                            @else
                                                                <i class="fas fa-clock text-yellow-500" title="Pendente"></i>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                            <span class="text-gray-400 dark:text-gray-500 text-xs italic">Sem despesas registradas</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-times text-gray-400 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum evento encontrado para este período.</p>
                        </div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>