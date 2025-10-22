<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Relatório de Desempenho - Artistas
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise de performance por Artista.</p>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 print:max-w-none print:p-0">

            {{-- Formulário de Filtros (Oculto na impressão) --}}
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md print:hidden">
                <form method="GET" action="{{ route('reports.artist-performance.index') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Inicial</label>
                            <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] ?? now()->startOfMonth()->format('Y-m-d') }}" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Final</label>
                            <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] ?? now()->endOfMonth()->format('Y-m-d') }}" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artista</label>
                            <select name="artist_id" id="artist_id" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Todos</option>
                                @foreach ($artists as $artist)
                                    <option value="{{ $artist->id }}" @selected(request('artist_id') == $artist->id)>{{ $artist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-filter mr-2"></i>
                                Filtrar
                            </button>
                            <a href="{{ route('reports.artist-performance.export.pdf', request()->query()) }}" target="_blank" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-file-pdf mr-2"></i>
                                PDF
                            </a>
                            <a href="{{ route('reports.artist-performance.export.excel', request()->query()) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold text-sm rounded-md shadow transition-colors duration-200">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Cards de Resumo (Ocultos na impressão) --}}
           <div class="grid grid-cols-1 md:grid-cols-4 gap-6 print:hidden w-full">
            {{-- Card Azul Claro: Gigs Vendidas --}}
            <div class="bg-blue-100 dark:bg-blue-900/20 p-6 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Gigs Vendidas</h3>
                <p class="text-3xl font-semibold text-blue-800 dark:text-blue-300 mt-1">
                    {{ $performanceData['summaryCards']['total_gigs'] }}
                </p>
            </div>

            {{-- Card Verde Claro: Valor Total em Contratos --}}
            <div class="bg-green-100 dark:bg-green-900/20 p-6 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Valor Total em Contratos</h3>
                <p class="text-3xl font-semibold text-green-800 dark:text-green-300 mt-1">
                    R$ {{ number_format($performanceData['summaryCards']['total_value'], 2, ',', '.') }}
                </p>
            </div>

            {{-- Card Vermelho Claro: Total Cachês Brutos --}}
            <div class="bg-red-100 dark:bg-red-900/20 p-6 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Cachês Brutos</h3>
                <p class="text-3xl font-semibold text-red-800 dark:text-red-300 mt-1">
                    R$ {{ number_format($performanceData['summaryCards']['total_gross_cash'], 2, ',', '.') }}
                </p>
            </div>

            {{-- Card Roxo Claro: Total Líquido para Artistas --}}
            <div class="bg-purple-100 dark:bg-purple-900/20 p-6 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Líquido Artistas</h3>
                <p class="text-3xl font-semibold text-purple-800 dark:text-purple-300 mt-1">
                    R$ {{ number_format($performanceData['summaryCards']['total_net_payout'], 2, ',', '.') }}
                </p>
            </div>
        </div>



            {{-- Tabela Dinâmica --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/6">Artista</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Qtd</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Total Contrato</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Cachê Bruto</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Líquido Artista</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($performanceData['tableData'] as $artistData)
                            <tbody x-data="{ open: false }" class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr @click="open = !open" class="hover:bg-gray-50 dark:hover:bg-gray-700/20 cursor-pointer">
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 transition-transform w-4" :class="{'rotate-90': open}"></i>
                                            <span class="ml-2 text-sm font-semibold text-primary-700 dark:text-primary-400">{{ $artistData['artist_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">{{ $artistData['gigs_count'] }}</div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-blue-800 dark:text-blue-300 font-semibold">R$ {{ number_format($artistData['total_contract'], 2, ',', '.') }}</div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-teal-800 dark:text-teal-300 font-semibold">R$ {{ number_format($artistData['total_gross_cash'], 2, ',', '.') }}</div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-purple-800 dark:text-purple-300 font-semibold">R$ {{ number_format($artistData['total_net_payout'], 2, ',', '.') }}</div>
                                    </td>
                                </tr>
                                <tr x-show="open" x-transition style="display: none;">
                                <td colspan="5" class="p-0">
                                <div class="bg-gray-50 dark:bg-gray-800/60 overflow-x-auto">
                                <!-- Tabela Consolidada por Mês -->
                                <div class="p-4">
                                <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Resumo por Mês</h5>
                                <table class="min-w-full text-sm border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden mb-4">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="py-3 px-4 text-left">Mês</th>
                                    <th class="py-3 px-4 text-center">Qtd. Eventos</th>
                                        <th class="py-3 px-4 text-right">Valor Contrato</th>
                                            <th class="py-3 px-4 text-right">Cachê Bruto</th>
                                            <th class="py-3 px-4 text-right">Líquido Artista</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @foreach($artistData['gigs_by_month'] as $index => $monthData)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer month-row-{{ $index }}"
                                        x-data="{ monthOpen: false }"
                                        @click="monthOpen = !monthOpen"
                                        :class="monthOpen ? 'bg-blue-50 dark:bg-blue-900/20' : ''">
                                            <td class="py-3 px-4 font-medium">
                                                <div class="flex items-center">
                                                    <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 transition-transform w-4 mr-2" :class="monthOpen ? 'rotate-90' : ''"></i>
                                                    {{ $monthData['month_name'] }}
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-center">{{ $monthData['month_gigs_count'] }}</td>
                                            <td class="py-3 px-4 text-right text-blue-700 dark:text-blue-300 font-semibold">R$ {{ number_format($monthData['month_total_contract'], 2, ',', '.') }}</td>
                                        <td class="py-3 px-4 text-right text-teal-700 dark:text-teal-300 font-semibold">R$ {{ number_format($monthData['month_total_gross_cash'], 2, ',', '.') }}</td>
                                        <td class="py-3 px-4 text-right text-purple-700 dark:text-purple-300 font-semibold">R$ {{ number_format($monthData['month_total_net_payout'], 2, ',', '.') }}</td>
                                </tr>
                                <!-- Detalhes do Mês (Segundo nível de expansão) -->
                                <tr x-show="monthOpen" x-transition style="display: none;">
                                    <td colspan="5" class="p-0">
                                        <div class="bg-gray-100 dark:bg-gray-700/50 p-4 border-t border-gray-200 dark:border-gray-600">
                                    <table class="min-w-full text-xs table-fixed border border-gray-300 dark:border-gray-500 rounded-lg overflow-hidden">
                                        <thead class="bg-gray-200 dark:bg-gray-600">
                                                <tr>
                                                    <th class="py-2 px-2 text-left w-[12%]">Data Venda</th>
                                                <th class="py-2 px-2 text-left w-[12%]">Data Evento</th>
                                                    <th class="py-2 px-2 text-left w-[15%]">Booker</th>
                                               <th class="py-2 px-2 text-left w-[28%]">Local</th>
                                                       <th class="py-2 px-2 text-right w-[11%]">Contrato</th>
                                                    <th class="py-2 px-2 text-right w-[11%]">Cachê Bruto</th>
                                               <th class="py-2 px-2 text-right w-[11%]">Líquido</th>
                                               </tr>
                                               </thead>
                                               <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                                       @foreach($monthData['gigs'] as $gig)
                                                       <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                       <td class="py-2 px-2">{{ $gig['sale_date'] }}</td>
                                                   <td class="py-2 px-2">{{ $gig['gig_date'] }}</td>
                                                   <td class="py-2 px-2">{{ $gig['booker_name'] }}</td>
                                                   <td class="py-2 px-2">
                                                       <a href="{{ route('gigs.show', $gig['gig_id']) }}" class="text-primary-600 hover:underline" title="Ver detalhes da Gig">
                                                               Gig #{{ $gig['gig_id'] }}
                                                               </a>
                                                                   @if(!empty($gig['location_event_details']))
                                                                           <div class="text-gray-500 dark:text-gray-400 italic text-xxs whitespace-normal break-words -mt-1">
                                                                               {{ $gig['location_event_details'] }}
                                                                               </div>
                                                                                    @endif
                                                                           </td>
                                                                               <td class="py-2 px-2 text-right">R$ {{ number_format($gig['contract_value'], 2, ',', '.') }}</td>
                                                                                <td class="py-2 px-2 text-right font-semibold text-teal-700 dark:text-teal-400">R$ {{ number_format($gig['gross_cash_brl'], 2, ',', '.') }}</td>
                                                                                <td class="py-2 px-2 text-right font-semibold text-purple-700 dark:text-purple-400">R$ {{ number_format($gig['net_payout_brl'], 2, ',', '.') }}</td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center py-10 text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-chart-line fa-2x text-gray-400 mb-2"></i>
                                            <p>Nenhum dado de desempenho encontrado para os filtros selecionados.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-900/50 border-t-2 border-gray-300 dark:border-gray-600">
                            <tr class="font-bold">
                                <td class="px-6 py-3 text-left text-sm text-gray-800 dark:text-white">TOTAL GERAL</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-800 dark:text-white">{{ $performanceData['summaryCards']['total_gigs'] }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-800 dark:text-white">R$ {{ number_format($performanceData['summaryCards']['total_value'], 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-800 dark:text-white">R$ {{ number_format($performanceData['summaryCards']['total_gross_cash'], 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-800 dark:text-white">R$ {{ number_format($performanceData['summaryCards']['total_net_payout'], 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
