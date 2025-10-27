<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
                    Central de Desempenho: <span class="text-primary-600 dark:text-primary-400">{{ $booker->name }}</span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Hub de informações de performance, comissões e atividades.</p>
            </div>
            <div class="flex items-center space-x-2 mt-4 md:mt-0">
                <a href="{{ route('bookers.index') }}" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors" title="Voltar para a lista de bookers">
                    <i class="fas fa-arrow-left fa-fw"></i>
                </a>
                <a href="{{ route('gigs.create', ['booker_id' => $booker->id]) }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-semibold flex items-center">
                    <i class="fas fa-plus mr-2"></i> Adicionar Gig
                </a>
                <a href="{{ route('bookers.edit', $booker) }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-800 dark:text-white px-4 py-2 rounded-md text-sm font-semibold">
                    Editar Cadastro
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
                <form method="GET" action="{{ route('bookers.show', $booker) }}">
                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Inicial</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? '' }}" class="mt-1 block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Final</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? '' }}" class="mt-1 block w-full text-sm rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-filter mr-2"></i>
                                Aplicar
                            </button>
                            <a href="{{ route('bookers.show', $booker) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-eraser mr-2"></i>
                                Limpar
                            </a>
                            <a href="{{ route('bookers.export.pdf', array_merge(['booker' => $booker->id], $filters)) }}" target="_blank" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-file-pdf mr-2"></i>
                                PDF
                            </a>
                            <a href="{{ route('bookers.export.excel', array_merge(['booker' => $booker->id], $filters)) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div x-data="{ tab: 'analysis' }" class="space-y-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                        <a href="#" @click.prevent="tab = 'analysis'"
                           :class="tab === 'analysis' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                           Análise Detalhada
                        </a>
                        <a href="#" @click.prevent="tab = 'highlights'"
                           :class="tab === 'highlights' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                           class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                           Destaques & Métricas Fixas
                        </a>
                    </nav>
                </div>

                <div x-show="tab === 'analysis'" x-transition>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Vendido {{ empty($filters['start_date']) ? '(Lifetime)' : '(no período)' }}</h3>
                                <p class="text-lg font-semibold text-blue-800 dark:text-blue-300 mt-1">R$ {{ number_format($salesKpis['total_sold_value'], 2, ',', '.') }}</p>
                            </div>
                            <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
                                <h3 class="text-sm text-gray-500 dark:text-gray-400">Gigs Vendidas {{ empty($filters['start_date']) ? '(Lifetime)' : '(no período)' }}</h3>
                                <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mt-1">{{ $salesKpis['total_gigs_sold'] }}</p>
                            </div>
                            <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
                                <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Total Recebida</h3>
                                <p class="text-lg font-semibold text-green-800 dark:text-green-300 mt-1">R$ {{ number_format($commissionKpis['commission_received'], 2, ',', '.') }}</p>
                            </div>
                            <div class="bg-orange-100 dark:bg-orange-900/20 p-4 rounded-lg">
                                <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão a Receber</h3>
                                <p class="text-lg font-semibold text-orange-800 dark:text-orange-300 mt-1">R$ {{ number_format($commissionKpis['commission_to_receive'], 2, ',', '.') }}</p>
                            </div>
                        </div>

                        <!-- Gráficos de Análise Detalhada -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Gráfico de Distribuição de Eventos -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Distribuição de Eventos</h3>
                                <div class="h-64 flex items-center justify-center">
                                    <canvas id="eventsDistributionChart"></canvas>
                                </div>
                            </div>

                            <!-- Gráfico de Status de Pagamentos -->
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Status de Comissões</h3>
                                <div class="h-64 flex items-center justify-center">
                                    <canvas id="commissionsStatusChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico de Evolução Temporal -->
                        @if($realizedEvents->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Evolução Temporal dos Eventos Realizados</h3>
                            <div class="h-72">
                                <canvas id="temporalEvolutionChart"></canvas>
                            </div>
                        </div>
                        @endif

                        <!-- Agrupamento de Eventos Realizados -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 dark:text-gray-200 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Eventos Realizados ({{ $realizedEvents->count() }})
                            </h4>

                            @if($realizedEvents->isNotEmpty())
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($realizedEvents as $event)
                                            <div x-data="{ open: false }" class="py-3">
                                                {{-- Linha Resumida Clicável --}}
                                                <div @click="open = !open" class="flex items-center justify-between cursor-pointer group">
                                                    {{-- Coluna Esquerda: Gig, Artista, Local --}}
                                                    <div class="flex items-center space-x-4">
                                                        {{-- Coluna 1: Link da Gig --}}
                                                        <a href="{{ route('gigs.show', $event['id']) }}" @click.stop class="text-sm font-semibold text-primary-600 hover:underline" title="Ver detalhes completos da Gig">
                                                            #{{ $event['id'] }}
                                                        </a>
                                                        {{-- Coluna 2: Artista e Local --}}
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-white">{{ $event['artist_name'] }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($event['location'], 35) }}</p>
                                                        </div>
                                                    </div>

                                                    {{-- Coluna Direita: Status, Data, Valor --}}
                                                    <div class="flex items-center space-x-4">
                                                        {{-- Coluna 3: Ícones de Status --}}
                                                        <div class="flex space-x-2" title="Status do Ciclo Financeiro (Cliente | Despesas | Artista | Booker)">
                                                            {{-- Status Pagamento Cliente --}}
                                                            <i class="fas fa-dollar-sign fa-fw {{ $event['payment_status'] === 'pago' ? 'text-green-500' : ($event['payment_status'] === 'vencido' ? 'text-red-500' : 'text-gray-400') }}"></i>
                                                            {{-- Status Pagamento Despesas --}}
                                                            <i class="fas fa-receipt fa-fw {{ ($event['total_costs_brl'] ?? 0) > 0 ? 'text-green-500' : 'text-gray-400' }}"></i>
                                                            {{-- Status Pagamento Artista --}}
                                                            <i class="fas fa-user-check fa-fw {{ $event['artist_payment_status'] === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                                                            {{-- Status Pagamento Booker --}}
                                                            <i class="fas fa-user-tag fa-fw {{ $event['booker_payment_status'] === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                                                        </div>
                                                        {{-- Coluna 4: Data e Valor --}}
                                                        <div class="text-sm text-right">
                                                            <p class="text-gray-700 dark:text-gray-300">{{ $event['gig_date'] }}</p>
                                                            <p class="text-xs font-semibold text-gray-500">R$ {{ number_format($event['cache_value_brl'], 2, ',', '.') }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Área Expandida com Detalhes --}}
                                                <div x-show="open" x-transition class="mt-4 pl-8 border-l-2 border-gray-200 dark:border-gray-700 ml-2">
                                                    <div class="space-y-2 text-xs">
                                                        @php
                                                            $statuses = [
                                                                'Pagamento Cliente' => ['status' => $event['payment_status'], 'color' => ($event['payment_status'] === 'pago' ? 'green' : ($event['payment_status'] === 'vencido' ? 'red' : 'gray'))],
                                                                'Pagamento Despesas' => ['status' => ($event['total_costs_brl'] ?? 0) > 0 ? 'OK' : 'Pendente', 'color' => ($event['total_costs_brl'] ?? 0) > 0 ? 'green' : 'gray'],
                                                                'Repasse Artista' => ['status' => $event['artist_payment_status'], 'color' => $event['artist_payment_status'] === 'pago' ? 'green' : 'gray'],
                                                                'Comissão Booker' => ['status' => $event['booker_payment_status'], 'color' => $event['booker_payment_status'] === 'pago' ? 'green' : 'gray'],
                                                            ];
                                                        @endphp
                                                        @foreach($statuses as $label => $info)
                                                            <div class="flex items-center">
                                                                <span class="w-3 h-3 rounded-full bg-{{ $info['color'] }}-500 mr-2"></span>
                                                                <span class="font-medium text-gray-600 dark:text-gray-400 w-32">{{ $label }}:</span>
                                                                <span class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($info['status']) }}</span>
                                                            </div>
                                                        @endforeach
                                                        <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <span class="font-medium text-gray-600 dark:text-gray-400 w-32">Comissão Booker:</span>
                                                            <span class="font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($event['booker_commission_brl'], 2, ',', '.') }}</span>
                                                        </div>
                                                        @if($event['is_exception'])
                                                            <div class="flex items-center">
                                                                <span class="w-3 h-3 rounded-full bg-orange-500 mr-2"></span>
                                                                <span class="font-medium text-orange-600 dark:text-orange-400">Pagamento com Exceção Autorizada</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">Nenhum evento realizado encontrado.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Agrupamento de Eventos Futuros -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-700 dark:text-gray-200 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Eventos Futuros ({{ $futureEvents->count() }})
                            </h4>

                            @if($futureEvents->isNotEmpty())
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($futureEvents as $event)
                                            <div x-data="{ open: false }" class="py-3">
                                                {{-- Linha Resumida Clicável --}}
                                                <div @click="open = !open" class="flex items-center justify-between cursor-pointer group">
                                                    {{-- Coluna Esquerda: Gig, Artista, Local --}}
                                                    <div class="flex items-center space-x-4">
                                                        {{-- Coluna 1: Link da Gig --}}
                                                        <a href="{{ route('gigs.show', $event['id']) }}" @click.stop class="text-sm font-semibold text-primary-600 hover:underline" title="Ver detalhes completos da Gig">
                                                            #{{ $event['id'] }}
                                                        </a>
                                                        {{-- Coluna 2: Artista e Local --}}
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-white">{{ $event['artist_name'] }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($event['location'], 35) }}</p>
                                                        </div>
                                                    </div>

                                                    {{-- Coluna Direita: Status, Data, Valor --}}
                                                    <div class="flex items-center space-x-4">
                                                        {{-- Coluna 3: Ícones de Status --}}
                                                        <div class="flex space-x-2" title="Status do Ciclo Financeiro (Cliente | Despesas | Artista | Booker)">
                                                            {{-- Status Pagamento Cliente --}}
                                                            <i class="fas fa-dollar-sign fa-fw {{ $event['payment_status'] === 'pago' ? 'text-green-500' : ($event['payment_status'] === 'vencido' ? 'text-red-500' : 'text-gray-400') }}"></i>
                                                            {{-- Status Pagamento Despesas --}}
                                                            <i class="fas fa-receipt fa-fw {{ ($event['total_costs_brl'] ?? 0) > 0 ? 'text-green-500' : 'text-gray-400' }}"></i>
                                                            {{-- Status Pagamento Artista --}}
                                                            <i class="fas fa-user-check fa-fw {{ $event['artist_payment_status'] === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                                                            {{-- Status Pagamento Booker --}}
                                                            <i class="fas fa-user-tag fa-fw {{ $event['booker_payment_status'] === 'pago' ? 'text-green-500' : ($event['is_exception'] ? 'text-orange-500' : 'text-gray-400') }}"></i>
                                                        </div>
                                                        {{-- Coluna 4: Data e Valor --}}
                                                        <div class="text-sm text-right">
                                                            <p class="text-gray-700 dark:text-gray-300">{{ $event['gig_date'] }}</p>
                                                            <p class="text-xs font-semibold text-gray-500">R$ {{ number_format($event['cache_value_brl'], 2, ',', '.') }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Área Expandida com Detalhes --}}
                                                <div x-show="open" x-transition class="mt-4 pl-8 border-l-2 border-gray-200 dark:border-gray-700 ml-2">
                                                    <div class="space-y-2 text-xs">
                                                        @php
                                                            $statuses = [
                                                                'Pagamento Cliente' => ['status' => $event['payment_status'], 'color' => ($event['payment_status'] === 'pago' ? 'green' : ($event['payment_status'] === 'vencido' ? 'red' : 'gray'))],
                                                                'Pagamento Despesas' => ['status' => ($event['total_costs_brl'] ?? 0) > 0 ? 'OK' : 'Pendente', 'color' => ($event['total_costs_brl'] ?? 0) > 0 ? 'green' : 'gray'],
                                                                'Repasse Artista' => ['status' => $event['artist_payment_status'], 'color' => $event['artist_payment_status'] === 'pago' ? 'green' : 'gray'],
                                                                'Comissão Booker' => ['status' => $event['booker_payment_status'], 'color' => $event['booker_payment_status'] === 'pago' ? 'green' : ($event['is_exception'] ? 'orange' : 'gray')],
                                                            ];
                                                        @endphp
                                                        @foreach($statuses as $label => $info)
                                                            <div class="flex items-center">
                                                                <span class="w-3 h-3 rounded-full bg-{{ $info['color'] }}-500 mr-2"></span>
                                                                <span class="font-medium text-gray-600 dark:text-gray-400 w-32">{{ $label }}:</span>
                                                                <span class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($info['status']) }}</span>
                                                            </div>
                                                        @endforeach
                                                        <div class="flex items-center mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <span class="font-medium text-gray-600 dark:text-gray-400 w-32">Comissão Booker:</span>
                                                            <span class="font-semibold text-blue-600 dark:text-blue-400">R$ {{ number_format($event['booker_commission_brl'], 2, ',', '.') }}</span>
                                                        </div>
                                                        @if($event['is_exception'])
                                                            <div class="flex items-center">
                                                                <span class="w-3 h-3 rounded-full bg-orange-500 mr-2"></span>
                                                                <span class="font-medium text-orange-600 dark:text-orange-400">Pagamento Antecipado com Exceção Autorizada</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg text-center py-8">
                                    <p class="text-gray-500 dark:text-gray-400">Nenhum evento futuro encontrado.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Tabela de Análise Detalhada Original (mantida para compatibilidade) -->
                        @if($analyticalTableData->isNotEmpty())
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50">
                                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-200">Análise por Período Filtrado</h4>
                                </div>
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-2 text-left">Data Venda</th>
                                            <th class="px-4 py-2 text-left">Artista</th>
                                            <th class="px-4 py-2 text-left">Local</th>
                                            <th class="px-4 py-2 text-right">Valor Contrato (BRL)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        @foreach($analyticalTableData as $gig)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="px-4 py-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($gig->sale_date)->isoFormat('L') }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-800 dark:text-white">{{ $gig->artist->name }}</td>
                                            <td class="px-4 py-2">{{ Str::limit($gig->location_event_details, 50) }}</td>
                                            <td class="px-4 py-2 text-right">R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md text-center py-16 px-6">
                                <i class="fas fa-filter fa-3x text-gray-400 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Detalhes por Período</h3>
                                <p class="text-sm text-gray-500 mt-1">Aplique um filtro de data para ver a lista detalhada de vendas.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div x-show="tab === 'highlights'" x-transition style="display: none;">
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Evolução de Comissões Pagas (Últimos 12 Meses)</h3>
                            <div class="h-72"><canvas id="commissionsChart"></canvas></div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Artistas em Destaque (Lifetime)</h3>
                                <div x-data="{ openArtist: null }" class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($topArtists as $index => $item)
                                        <div class="py-3">
                                            <div @click="openArtist = (openArtist === {{ $index }} ? null : {{ $index }})" class="flex justify-between items-center cursor-pointer">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $item->artist_name }}</span>
                                                <div class="flex items-center text-right">
                                                    <div class="mr-4">
                                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $item->gigs_count }} Gigs</span>
                                                        <span class="text-xs block text-gray-500 dark:text-gray-400">R$ {{ number_format($item->total_value, 2, ',', '.') }}</span>
                                                    </div>
                                                    <i class="fas fa-chevron-up text-indigo-500 dark:text-indigo-400 transition-transform w-4" :class="{'rotate-180': openArtist !== {{ $index }} }"></i>
                                                </div>
                                            </div>
                                            <div x-show="openArtist === {{ $index }}" x-transition:enter.duration.300ms x-transition:leave.duration.200ms class="mt-4 pl-4" style="display: none;">
                                                <table class="w-full text-xs">
                                                    <thead class="text-gray-500 dark:text-gray-400">
                                                        <tr>
                                                            <th class="py-1 text-left">Data</th>
                                                            <th class="py-1 text-left">Local</th>
                                                            <th class="py-1 text-right">Valor</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($item->gigs as $gig)
                                                        <tr class="border-t border-gray-100 dark:border-gray-700">
                                                            <td class="py-1">{{ $gig->sale_date }}</td>
                                                            <td class="py-1">{{ $gig->location }}</td>
                                                            <td class="py-1 text-right">R$ {{ number_format($gig->value, 2, ',', '.') }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">Nenhum dado de artista para exibir.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Gigs Mais Recentes</h3>
    
    {{-- Container para a lista de gigs --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($recentGigs as $gig)
            {{-- Cada item da lista agora é um componente Alpine para controlar a expansão --}}
            <div x-data="{ open: false }" class="py-3">
                {{-- Linha Resumida Clicável --}}
                <div @click="open = !open" class="flex items-center justify-between cursor-pointer group">
                    {{-- Coluna Esquerda: Gig, Artista, Local --}}
                    <div class="flex items-center space-x-4">
                        {{-- Coluna 1: Link da Gig --}}
                        <a href="{{ route('gigs.show', $gig) }}" @click.stop class="text-sm font-semibold text-primary-600 hover:underline" title="Ver detalhes completos da Gig">
                            #{{ $gig->id }}
                        </a>
                        {{-- Coluna 2: Artista e Local --}}
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $gig->artist->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($gig->location_event_details, 35) }}</p>
                        </div>
                    </div>

                    {{-- Coluna Direita: Status, Data, Valor --}}
                    <div class="flex items-center space-x-4">
                        {{-- Coluna 3: Ícones de Status --}}
                        <div class="flex space-x-2" title="Status do Ciclo Financeiro (Cliente | Despesas | Artista | Booker)">
                            {{-- Status Pagamento Cliente --}}
                            <i class="fas fa-dollar-sign fa-fw {{ $gig->payment_status === 'pago' ? 'text-green-500' : ($gig->payment_status === 'vencido' ? 'text-red-500' : 'text-gray-400') }}"></i>
                            {{-- Status Pagamento Despesas --}}
                            <i class="fas fa-receipt fa-fw {{ $gig->are_all_costs_confirmed ? 'text-green-500' : 'text-gray-400' }}"></i>
                            {{-- Status Pagamento Artista --}}
                            <i class="fas fa-user-check fa-fw {{ $gig->artist_payment_status === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                             {{-- Status Pagamento Booker --}}
                            <i class="fas fa-user-tag fa-fw {{ $gig->booker_payment_status === 'pago' ? 'text-green-500' : 'text-gray-400' }}"></i>
                        </div>
                        {{-- Coluna 4: Data e Valor --}}
                        <div class="text-sm text-right">
                            <p class="text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($gig->contract_date ?? $gig->gig_date)->isoFormat('L') }}</p>
                            <p class="text-xs font-semibold text-gray-500">R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Área Expandida com Detalhes --}}
                <div x-show="open" x-transition class="mt-4 pl-8 border-l-2 border-gray-200 dark:border-gray-700 ml-2">
                    <div class="space-y-2 text-xs">
                        @php
                            $statuses = [
                                'Pagamento Cliente' => ['status' => $gig->payment_status, 'color' => ($gig->payment_status === 'pago' ? 'green' : ($gig->payment_status === 'vencido' ? 'red' : 'gray'))],
                                'Pagamento Despesas' => ['status' => $gig->are_all_costs_confirmed ? 'OK' : 'Pendente', 'color' => $gig->are_all_costs_confirmed ? 'green' : 'gray'],
                                'Repasse Artista' => ['status' => $gig->artist_payment_status, 'color' => $gig->artist_payment_status === 'pago' ? 'green' : 'gray'],
                                'Comissão Booker' => ['status' => $gig->booker_payment_status, 'color' => $gig->booker_payment_status === 'pago' ? 'green' : 'gray'],
                            ];
                        @endphp
                        @foreach($statuses as $label => $info)
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full bg-{{ $info['color'] }}-500 mr-2"></span>
                                <span class="font-medium text-gray-600 dark:text-gray-400 w-32">{{ $label }}:</span>
                                <span class="font-semibold text-gray-800 dark:text-white">{{ ucfirst($info['status']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Nenhuma gig recente para exibir.</p>
        @endforelse
    </div>
</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
{{-- Script do Chart.js como antes --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

{{-- Dados do servidor --}}
<script type="application/json" id="chart-labels">@json($chart['labels'] ?? [])</script>
<script type="application/json" id="chart-data">@json($chart['data'] ?? [])</script>
<script type="application/json" id="realized-events">@json($realizedEvents ?? [])</script>
<script type="application/json" id="future-events">@json($futureEvents ?? [])</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Dados do servidor
        const chartLabels = JSON.parse(document.getElementById('chart-labels').textContent);
        const chartData = JSON.parse(document.getElementById('chart-data').textContent);
        const realizedEventsData = JSON.parse(document.getElementById('realized-events').textContent);
        const futureEventsData = JSON.parse(document.getElementById('future-events').textContent);
        // Gráfico de Comissões Existente
        const ctx = document.getElementById('commissionsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Comissão Paga (R$)',
                        data: chartData,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Gráfico de Distribuição de Eventos (Donut)
        const eventsDistCtx = document.getElementById('eventsDistributionChart');
        if (eventsDistCtx) {
            const realizedCount = realizedEventsData.length || 0;
            const futureCount = futureEventsData.length || 0;
            
            new Chart(eventsDistCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Eventos Realizados', 'Eventos Futuros'],
                    datasets: [{
                        data: [realizedCount, futureCount],
                        backgroundColor: ['#10b981', '#3b82f6'],
                        borderColor: ['#059669', '#2563eb'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de Status de Comissões (Bar)
        const commissionsStatusCtx = document.getElementById('commissionsStatusChart');
        if (commissionsStatusCtx) {
            const realizedEvents = realizedEventsData;
            const futureEvents = futureEventsData;
            
            // Calcular totais por status
            let paidCount = 0;
            let pendingCount = 0;
            let exceptionCount = 0;
            let paidValue = 0;
            let pendingValue = 0;
            let exceptionValue = 0;
            
            realizedEvents.forEach(function(event) {
                if (event.booker_payment_status === 'pago') {
                    paidCount++;
                    paidValue += parseFloat(event.booker_commission_brl || 0);
                } else {
                    pendingCount++;
                    pendingValue += parseFloat(event.booker_commission_brl || 0);
                }
            });
            
            futureEvents.forEach(function(event) {
                if (event.is_exception) {
                    exceptionCount++;
                    exceptionValue += parseFloat(event.booker_commission_brl || 0);
                } else if (event.booker_payment_status === 'pago') {
                    paidCount++;
                    paidValue += parseFloat(event.booker_commission_brl || 0);
                } else {
                    pendingCount++;
                    pendingValue += parseFloat(event.booker_commission_brl || 0);
                }
            });
            
            new Chart(commissionsStatusCtx, {
                type: 'bar',
                data: {
                    labels: ['Pagas', 'Pendentes', 'Exceções'],
                    datasets: [{
                        label: 'Valor (R$)',
                        data: [paidValue, pendingValue, exceptionValue],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderColor: ['#059669', '#d97706', '#dc2626'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Gráfico de Evolução Temporal (Line)
        const temporalCtx = document.getElementById('temporalEvolutionChart');
        if (temporalCtx) {
            const realizedEvents = realizedEventsData;
            
            // Agrupar eventos por mês
            const monthlyData = {};
            realizedEvents.forEach(function(event) {
                const date = new Date(event.gig_date);
                const monthKey = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                
                if (!monthlyData[monthKey]) {
                    monthlyData[monthKey] = {
                        count: 0,
                        value: 0,
                        commission: 0
                    };
                }
                
                monthlyData[monthKey].count++;
                monthlyData[monthKey].value += parseFloat(event.cache_value_brl);
                monthlyData[monthKey].commission += parseFloat(event.booker_commission_brl);
            });
            
            // Ordenar por data e preparar dados
            const sortedMonths = Object.keys(monthlyData).sort();
            const labels = sortedMonths.map(month => {
                const [year, monthNum] = month.split('-');
                const date = new Date(year, monthNum - 1);
                return date.toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
            });
            
            const eventCounts = sortedMonths.map(month => monthlyData[month].count);
            const commissionValues = sortedMonths.map(month => monthlyData[month].commission);
            
            new Chart(temporalCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Número de Eventos',
                        data: eventCounts,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y'
                    }, {
                        label: 'Comissão (R$)',
                        data: commissionValues,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Período'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Número de Eventos'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Comissão (R$)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        }
    });
</script>

<script>
// Função para alternar exibição dos detalhes do evento
function toggleEventDetails(eventId) {
    const detailsRow = document.getElementById(eventId);
    if (detailsRow) {
        detailsRow.classList.toggle('hidden');
    }
}

// Função para atualizar comissão do evento
function updateEventCommission(event, eventId, type) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Adicionar CSRF token
    data._token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/bookers/events/${eventId}/commission`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensagem de sucesso
            showNotification('Comissão atualizada com sucesso!', 'success');
            
            // Recarregar a página para atualizar os dados
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Erro ao atualizar comissão', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao atualizar comissão', 'error');
    });
}

// Função para mostrar notificações
function showNotification(message, type) {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remover após 3 segundos
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Gerenciar checkboxes de exceção
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar listeners para checkboxes de exceção
    document.querySelectorAll('input[name="is_exception"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const eventId = this.closest('form').querySelector('input[name="event_id"]')?.value || 
                           this.closest('tr').id.split('-')[1];
            const justificationDiv = document.getElementById(`exception-justification-${eventId}`);
            
            if (justificationDiv) {
                if (this.checked) {
                    justificationDiv.classList.remove('hidden');
                } else {
                    justificationDiv.classList.add('hidden');
                }
            }
        });
    });
});
</script>
@endpush
</x-app-layout>