<x-app-layout>
    @section('title', 'Fechamento Mensal')

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                    Fechamento Mensal
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Relatório executivo de performance e comissões
                </p>
            </div>
            <form action="{{ route('finance.monthly-closing.exportPdf') }}" method="GET" class="inline-block">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                @if($selectedBookerId)
                    <input type="hidden" name="booker_id" value="{{ $selectedBookerId }}">
                @endif
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-sm transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar PDF
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- FILTROS --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <form action="{{ route('finance.monthly-closing') }}" method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Mês
                            </label>
                            <select name="month" id="month" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($months as $value => $name)
                                    <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ano
                            </label>
                            <select name="year" id="year" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($years as $year => $yearLabel)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        {{ $yearLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Booker (Opcional)
                            </label>
                            <select name="booker_id" id="booker_id" class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">Todos os Bookers</option>
                                @foreach($bookers as $booker)
                                    <option value="{{ $booker->id }}" {{ $selectedBookerId == $booker->id ? 'selected' : '' }}>
                                        {{ $booker->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md shadow-sm transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- CARDS PRINCIPAIS --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Card 1: Faturamento --}}
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-100 uppercase tracking-wide">Faturamento</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($reportData['total_faturamento'] ?? 0, 2, ',', '.') }}
                            </p>
                            <p class="text-sm text-blue-100 mt-1">
                                {{ $reportData['total_gigs'] ?? 0 }} eventos realizados
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Comissão Agência --}}
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-indigo-100 uppercase tracking-wide">Comissão Agência</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($reportData['total_comissao_agencia'] ?? 0, 2, ',', '.') }}
                            </p>
                            @php
                                $percentAgencia = $reportData['total_faturamento'] > 0
                                    ? ($reportData['total_comissao_agencia'] / $reportData['total_faturamento']) * 100
                                    : 0;
                            @endphp
                            <p class="text-sm text-indigo-100 mt-1">
                                {{ number_format($percentAgencia, 1) }}% do faturamento
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Comissão Booker --}}
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-100 uppercase tracking-wide">Comissão Booker</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($reportData['total_comissao_booker'] ?? 0, 2, ',', '.') }}
                            </p>
                            @php
                                $percentBooker = $reportData['total_faturamento'] > 0
                                    ? ($reportData['total_comissao_booker'] / $reportData['total_faturamento']) * 100
                                    : 0;
                            @endphp
                            <p class="text-sm text-purple-100 mt-1">
                                {{ number_format($percentBooker, 1) }}% do faturamento
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABELA COMPARATIVA DE ARTISTAS (VERTICAL) --}}
            @if(count($reportData['artist_comparison'] ?? []) > 0)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Comparativo de Performance - Artistas
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Análise vertical de indicadores financeiros por artista
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Artista
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Vendas
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cachê Líquido
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comissão Agência
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comissão Líquida
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($reportData['artist_comparison'] as $artist)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0 bg-primary-500 rounded-full flex items-center justify-center">
                                                <span class="text-white font-bold text-sm">
                                                    {{ strtoupper(substr($artist['name'], 0, 2)) }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                                    {{ $artist['name'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold text-blue-700 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ $artist['vendas'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                        R$ {{ number_format($artist['cache_liquido'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                        R$ {{ number_format($artist['comissao_agencia'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-green-600 dark:text-green-400 whitespace-nowrap">
                                        R$ {{ number_format($artist['comissao_liquida'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Linha de Total --}}
                            @php
                                $totalVendas = collect($reportData['artist_comparison'])->sum('vendas');
                                $totalCacheLiquido = collect($reportData['artist_comparison'])->sum('cache_liquido');
                                $totalComissaoAgencia = collect($reportData['artist_comparison'])->sum('comissao_agencia');
                                $totalComissaoLiquida = collect($reportData['artist_comparison'])->sum('comissao_liquida');
                            @endphp
                            <tr class="bg-primary-600 dark:bg-primary-700 text-white font-bold">
                                <td class="px-6 py-4 text-sm uppercase">Total Geral</td>
                                <td class="px-6 py-4 text-center text-sm">{{ $totalVendas }}</td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">R$ {{ number_format($totalCacheLiquido, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">R$ {{ number_format($totalComissaoAgencia, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-sm whitespace-nowrap">R$ {{ number_format($totalComissaoLiquida, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- TABELA COMPARATIVA DE BOOKERS (HORIZONTAL) --}}
            @if(count($reportData['booker_comparison_events'] ?? []) > 0)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Eventos realizados no período - Bookers
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                    Eventos realizados por booker no período selecionado
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-600">
                                    Indicador
                                </th>
                                @foreach($reportData['booker_comparison_events'] as $booker)
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ $booker['name'] }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            {{-- Linha: Gigs --}}
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50">
                            Gigs
                            </td>
                            @foreach($reportData['booker_comparison_events'] as $booker)
                            <td class="px-6 py-4 text-right text-sm font-bold text-blue-600 dark:text-blue-400">
                            {{ $booker['gigs'] }}
                            </td>
                            @endforeach
                            </tr>

                            {{-- Linha: Cachê Bruto --}}
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50">
                                    Cachê Bruto
                                </td>
                                @foreach($reportData['booker_comparison_events'] as $booker)
                                <td class="px-6 py-4 text-right text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                R$ {{ number_format($booker['cache_bruto'], 2, ',', '.') }}
                                </td>
                                @endforeach
                            </tr>

                            {{-- Linha: Cachê Booker --}}
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50">
                                    Cachê Booker
                                </td>
                                @foreach($reportData['booker_comparison_events'] as $booker)
                                <td class="px-6 py-4 text-right text-sm font-bold text-purple-600 dark:text-purple-400">
                                R$ {{ number_format($booker['cache_booker'], 2, ',', '.') }}
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- TABELA COMPARATIVA DE DESEMPENHO - BOOKERS (HORIZONTAL) --}}
             @if(count($reportData['booker_comparison_sales'] ?? []) > 0)
             <div class=\"bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden\">
                 <div class=\"px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50\">
                     <h3 class=\"text-lg font-semibold text-gray-900 dark:text-white\">
                         Comparativo de Desempenho - Bookers
                     </h3>
                     <p class=\"text-sm text-gray-500 dark:text-gray-400\">
                         Comparativo de desempenho baseado no somatório de contratos celebrados no período
                     </p>
                 </div>
                 <div class=\"overflow-x-auto\">
                     <table class=\"min-w-full divide-y divide-gray-200 dark:divide-gray-700\">
                         <thead class=\"bg-gray-50 dark:bg-gray-700\">
                             <tr>
                                 <th class=\"px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider bg-gray-100 dark:bg-gray-600\">
                                     Indicador
                                 </th>
                                 @foreach($reportData['booker_comparison_sales'] as $booker)
                                 <th class=\"px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider\">
                                 {{ $booker['name'] }}
                                 </th>
                                 @endforeach
                             </tr>
                         </thead>
                         <tbody class=\"bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700\">
                             {{-- Linha: Contratos Celebrados --}}
                             <tr class=\"hover:bg-gray-50 dark:hover:bg-gray-700/30\">
                                 <td class=\"px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50\">
                                     Contratos Celebrados
                                 </td>
                                 @foreach($reportData['booker_comparison_sales'] as $booker)
                                 <td class=\"px-6 py-4 text-right text-sm font-bold text-blue-600 dark:text-blue-400\">
                                 {{ $booker['contratos'] }}
                                 </td>
                                 @endforeach
                             </tr>

                             {{-- Linha: Cachê Bruto --}}
                             <tr class=\"hover:bg-gray-50 dark:hover:bg-gray-700/30\">
                                 <td class=\"px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50\">
                                     Cachê Bruto
                                 </td>
                                 @foreach($reportData['booker_comparison_sales'] as $booker)
                                 <td class=\"px-6 py-4 text-right text-sm font-bold text-indigo-600 dark:text-indigo-400\">
                                 R$ {{ number_format($booker['cache_bruto'], 2, ',', '.') }}
                                 </td>
                                 @endforeach
                             </tr>

                             {{-- Linha: Cachê Booker --}}
                             <tr class=\"hover:bg-gray-50 dark:hover:bg-gray-700/30\">
                                 <td class=\"px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700/50\">
                                     Cachê Booker
                                 </td>
                                 @foreach($reportData['booker_comparison_sales'] as $booker)
                                 <td class=\"px-6 py-4 text-right text-sm font-bold text-purple-600 dark:text-purple-400\">
                                 R$ {{ number_format($booker['cache_booker'], 2, ',', '.') }}
                                 </td>
                                 @endforeach
                             </tr>
                         </tbody>
                     </table>
                 </div>
             </div>
             @endif

             {{-- TABELA 1: ANALÍTICA POR ARTISTA --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Relatório Analítico por Artista
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Detalhamento completo de eventos por artista
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Data | Local do Evento
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cachê Líquido
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comissão Agência
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comissão Booker
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comissão Líquida
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            @php
                                $totalGeralCache = 0;
                                $totalGeralAgencia = 0;
                                $totalGeralBooker = 0;
                                $totalGeralLiquida = 0;
                            @endphp

                            @forelse($reportData['artist_data'] ?? [] as $artistGroup)
                                @php
                                    $totalGeralCache += $artistGroup['cache_liquido'];
                                    $totalGeralAgencia += $artistGroup['comissao_agencia'];
                                    $totalGeralBooker += $artistGroup['comissao_booker'];
                                    $totalGeralLiquida += $artistGroup['comissao_liquida'];

                                    $percentual = $reportData['total_faturamento'] > 0
                                        ? ($artistGroup['cache_liquido'] / $reportData['total_faturamento']) * 100
                                        : 0;
                                @endphp

                                {{-- Cabeçalho do Artista --}}
                                <tr class="bg-primary-50 dark:bg-primary-900/20 border-t-2 border-primary-500">
                                    <td colspan="5" class="px-6 py-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0 bg-primary-500 rounded-full flex items-center justify-center">
                                                    <span class="text-white font-bold text-sm">
                                                        {{ strtoupper(substr($artistGroup['artist']->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-primary-900 dark:text-primary-100">
                                                        {{ $artistGroup['artist']->name }}
                                                    </div>
                                                    <div class="text-xs text-primary-700 dark:text-primary-300">
                                                        {{ $artistGroup['vendas'] }} {{ $artistGroup['vendas'] > 1 ? 'eventos' : 'evento' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Eventos do Artista --}}
                                @foreach($artistGroup['gigs_detailed'] as $gigDetail)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-start space-x-3">
                                                {{-- Link do Contrato --}}
                                                <a href="{{ route('gigs.show', $gigDetail['gig_id']) }}"
                                                   class="flex-shrink-0 inline-flex items-center px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold rounded-md hover:bg-primary-200 dark:hover:bg-primary-900/50 transition-colors"
                                                   title="Ver detalhes da Gig">
                                                    #{{ $gigDetail['contract_number'] ?? $gigDetail['gig_id'] }}
                                                </a>

                                                {{-- Detalhes do Evento --}}
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-2 mb-1">
                                                        <span class="inline-flex items-center text-sm font-semibold text-gray-900 dark:text-white">
                                                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ $gigDetail['date']->format('d/m/Y') }}
                                                        </span>
                                                        <span class="text-gray-400">|</span>
                                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            {{ $gigDetail['location'] }}
                                                        </span>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                            R$ {{ number_format($gigDetail['cache_liquido'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                            R$ {{ number_format($gigDetail['comissao_agencia'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                            R$ {{ number_format($gigDetail['comissao_booker'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-green-600 dark:text-green-400 whitespace-nowrap">
                                            R$ {{ number_format($gigDetail['comissao_liquida'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Subtotal do Artista --}}
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold border-b-2 border-gray-300 dark:border-gray-600">
                                    <td class="px-6 py-3 text-sm">
                                        <span class="text-gray-900 dark:text-white">SUBTOTAL {{ strtoupper($artistGroup['artist']->name) }}</span>
                                        <span class="ml-2 text-xs font-normal text-gray-600 dark:text-gray-400">
                                            ({{ number_format($percentual, 1) }}% do total)
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-blue-700 dark:text-blue-300">
                                        R$ {{ number_format($artistGroup['cache_liquido'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-indigo-700 dark:text-indigo-300">
                                        R$ {{ number_format($artistGroup['comissao_agencia'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-purple-700 dark:text-purple-300">
                                        R$ {{ number_format($artistGroup['comissao_booker'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-green-700 dark:text-green-300">
                                        R$ {{ number_format($artistGroup['comissao_liquida'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhum artista encontrado no período selecionado
                                    </td>
                                </tr>
                            @endforelse

                            @if(count($reportData['artist_data'] ?? []) > 0)
                                {{-- Total Geral --}}
                                <tr class="bg-primary-600 dark:bg-primary-700 text-white font-bold">
                                    <td class="px-6 py-4 text-sm">
                                        TOTAL GERAL - {{ $reportData['total_gigs'] }} EVENTOS
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        R$ {{ number_format($totalGeralCache, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        R$ {{ number_format($totalGeralAgencia, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        R$ {{ number_format($totalGeralBooker, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        R$ {{ number_format($totalGeralLiquida, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- GRÁFICO: Distribuição de Faturamento --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Distribuição de Faturamento
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Composição do faturamento total do período
                </p>
                <div class="relative" style="height: 300px;">
                    <canvas id="distributionChart"></canvas>
                </div>
            </div>

            {{-- TABELA 2: ANALÍTICA POR BOOKER --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Relatório Analítico por Booker
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Detalhamento completo de eventos por booker
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Data | Artista @ Local
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cachê Líquido
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comissão Booker
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            @php
                                $totalGeralBookerCache = 0;
                                $totalGeralBookerCom = 0;
                            @endphp

                            @forelse($reportData['booker_data'] ?? [] as $bookerGroup)
                                @php
                                    $totalGeralBookerCache += $bookerGroup['cache_liquido'];
                                    $totalGeralBookerCom += $bookerGroup['comissao_booker'];

                                    $percentual = $reportData['total_faturamento'] > 0
                                        ? ($bookerGroup['cache_liquido'] / $reportData['total_faturamento']) * 100
                                        : 0;
                                @endphp

                                {{-- Cabeçalho do Booker --}}
                                <tr class="bg-purple-50 dark:bg-purple-900/20 border-t-2 border-purple-500">
                                    <td colspan="3" class="px-6 py-3">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0 bg-purple-500 rounded-full flex items-center justify-center">
                                                    <span class="text-white font-bold text-sm">
                                                        {{ strtoupper(substr($bookerGroup['booker']->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-purple-900 dark:text-purple-100">
                                                        {{ $bookerGroup['booker']->name }}
                                                    </div>
                                                    <div class="text-xs text-purple-700 dark:text-purple-300">
                                                        {{ $bookerGroup['vendas'] }} {{ $bookerGroup['vendas'] > 1 ? 'eventos' : 'evento' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Eventos do Booker --}}
                                @foreach($bookerGroup['gigs_detailed'] as $gigDetail)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-start space-x-3">
                                                {{-- Link do Contrato --}}
                                                <a href="{{ route('gigs.show', $gigDetail['gig_id']) }}"
                                                   class="flex-shrink-0 inline-flex items-center px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-bold rounded-md hover:bg-primary-200 dark:hover:bg-primary-900/50 transition-colors"
                                                   title="Ver detalhes da Gig">
                                                    #{{ $gigDetail['contract_number'] ?? $gigDetail['gig_id'] }}
                                                </a>

                                                {{-- Detalhes do Evento --}}
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center space-x-2 mb-1">
                                                        <span class="inline-flex items-center text-sm font-semibold text-gray-900 dark:text-white">
                                                            <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ $gigDetail['date']->format('d/m/Y') }}
                                                        </span>
                                                        <span class="text-gray-400">|</span>
                                                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                                                            {{ $gigDetail['artist_name'] }}
                                                        </span>
                                                        <span class="text-gray-400">@</span>
                                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            {{ $gigDetail['location'] }}
                                                        </span>
                                                    </div>
                                                   
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                            R$ {{ number_format($gigDetail['cache_liquido'], 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-purple-600 dark:text-purple-400 whitespace-nowrap">
                                            R$ {{ number_format($gigDetail['comissao_booker'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Subtotal do Booker --}}
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold border-b-2 border-gray-300 dark:border-gray-600">
                                    <td class="px-6 py-3 text-sm">
                                        <span class="text-gray-900 dark:text-white">SUBTOTAL {{ strtoupper($bookerGroup['booker']->name) }}</span>
                                        <span class="ml-2 text-xs font-normal text-gray-600 dark:text-gray-400">
                                            ({{ number_format($percentual, 1) }}% do total)
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-blue-700 dark:text-blue-300">
                                        R$ {{ number_format($bookerGroup['cache_liquido'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-purple-700 dark:text-purple-300">
                                        R$ {{ number_format($bookerGroup['comissao_booker'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhum booker encontrado no período selecionado
                                    </td>
                                </tr>
                            @endforelse

                            @if(count($reportData['booker_data'] ?? []) > 0)
                                {{-- Total Geral --}}
                                <tr class="bg-purple-600 dark:bg-purple-700 text-white font-bold">
                                    <td class="px-6 py-4 text-sm">
                                        TOTAL GERAL - {{ $reportData['total_gigs'] }} EVENTOS
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        R$ {{ number_format($totalGeralBookerCache, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        R$ {{ number_format($totalGeralBookerCom, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- GRÁFICO: Top Bookers --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Top Bookers por Faturamento
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Ranking dos bookers com maior volume de vendas
                </p>
                <div class="relative" style="height: 350px;">
                    <canvas id="bookerChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de Distribuição
            const distributionData = @json($reportData['chart_distribution'] ?? []);
            if (distributionData.length > 0) {
                new Chart(document.getElementById('distributionChart'), {
                    type: 'doughnut',
                    data: {
                        labels: distributionData.map(d => d.label),
                        datasets: [{
                            data: distributionData.map(d => d.value),
                            backgroundColor: distributionData.map(d => d.color),
                            borderWidth: 2,
                            borderColor: '#fff'
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
                                    usePointStyle: true,
                                    font: { size: 12 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed || 0;
                                        return context.label + ': R$ ' + value.toLocaleString('pt-BR', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Bookers
            const bookerData = @json($reportData['chart_booker_comparison'] ?? []);
            if (bookerData.length > 0) {
                new Chart(document.getElementById('bookerChart'), {
                    type: 'bar',
                    data: {
                        labels: bookerData.map(d => d.label),
                        datasets: [{
                            label: 'Cachê Líquido',
                            data: bookerData.map(d => d.cache_liquido),
                            backgroundColor: '#3b82f6',
                            borderRadius: 6
                        }, {
                            label: 'Comissão Booker',
                            data: bookerData.map(d => d.comissao_booker),
                            backgroundColor: '#8b5cf6',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.parsed.y || 0;
                                        return context.dataset.label + ': R$ ' + value.toLocaleString('pt-BR', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
