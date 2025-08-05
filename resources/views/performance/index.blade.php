<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Relatório de Desempenho
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise de vendas por Booker.</p>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 print:max-w-none print:p-0">

            {{-- Formulário de Filtros (Oculto na impressão) --}}
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md print:hidden">
                <form method="GET" action="{{ route('reports.performance.index') }}">
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
                            <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Booker</label>
                            <select name="booker_id" id="booker_id" class="mt-1 block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Todos</option>
                                @foreach ($bookers as $booker)
                                    <option value="{{ $booker->id }}" @selected(request('booker_id') == $booker->id)>{{ $booker->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-md shadow w-full transition-colors duration-200"><i class="fas fa-filter mr-2"></i>Filtrar</button>
                            <a href="{{ route('reports.performance.export', request()->query()) }}" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-md shadow w-full transition-colors duration-200"><i class="fas fa-file-pdf mr-2"></i>PDF</a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Cards de Resumo (Ocultos na impressão) --}}
           <div class="grid grid-cols-1 md:grid-cols-3 gap-6 print:hidden w-full">
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
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Cachês Brutos (Base de Cálculo)</h3>
                <p class="text-3xl font-semibold text-red-800 dark:text-red-300 mt-1">
                    R$ {{ number_format($performanceData['summaryCards']['total_gross_cash'], 2, ',', '.') }}
                </p>
            </div>
        </div>



            {{-- Tabela Dinâmica --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            {{-- ***** CABEÇALHO REORDENADO E COM NOVAS COLUNAS ***** --}}
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/6">Booker</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Qtd</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/6">Total Contrato</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/6">Total Cachê (Bruto)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($performanceData['tableData'] as $bookerData)
                            <tbody x-data="{ open: false }" class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr @click="open = !open" class="hover:bg-gray-50 dark:hover:bg-gray-700/20 cursor-pointer">
                                    {{-- ***** LINHA SINTÉTICA REORDENADA ***** --}}
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 transition-transform w-4" :class="{'rotate-90': open}"></i>
                                            <span class="ml-2 text-sm font-semibold text-primary-700 dark:text-primary-400">{{ $bookerData['booker_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">{{ $bookerData['gigs_count'] }}</div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-blue-800 dark:text-blue-300 font-semibold">R$ {{ number_format($bookerData['total_contract'], 2, ',', '.') }}</div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="text-sm text-teal-800 dark:text-teal-300 font-semibold">R$ {{ number_format($bookerData['total_gross_cash'], 2, ',', '.') }}</div>
                                    </td>
                                </tr>
                                <tr x-show="open" x-transition style="display: none;">
                                    <td colspan="4" class="p-0">
                                        <div class="bg-gray-50 dark:bg-gray-800/60 p-4 overflow-x-auto">
                                            <table class="min-w-full mt-2 text-xs table-fixed">
                                                <thead class="text-gray-500 dark:text-gray-400">
                                                    <tr>
                                                        {{-- ***** SUB-CABEÇALHO ATUALIZADO ***** --}}
                                                        <th class="py-2 px-2 text-left w-[15%]">Data Venda</th>
                                                        <th class="py-2 px-2 text-left w-[15%]">Data Evento</th>
                                                        <th class="py-2 px-2 text-left w-[40%]">Artista - Local</th>
                                                        <th class="py-2 px-2 text-right w-[15%]">Valor Contrato</th>
                                                        <th class="py-2 px-2 text-right w-[15%]">Cachê Bruto</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                    @foreach($bookerData['gigs'] as $gig)
                                                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <td class="py-2 px-2">{{ $gig['sale_date'] }}</td>
                                                        <td class="py-2 px-2">{{ $gig['gig_date'] }}</td>
                                                        <td class="py-2 px-2 truncate">{{ $gig['artist_local'] }}</td>
                                                        <td class="py-2 px-2 text-right">R$ {{ number_format($gig['contract_value'], 2, ',', '.') }}</td>
                                                        {{-- ***** VARIÁVEL CORRETA USADA AQUI ***** --}}
                                                        <td class="py-2 px-2 text-right font-semibold text-teal-700 dark:text-teal-400">
                                                            R$ {{ number_format($gig['gross_cash_brl'], 2, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                    </td>
                                </tr>
                            </tbody>
                            @empty
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center py-10 text-gray-500 dark:text-gray-400">
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
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>