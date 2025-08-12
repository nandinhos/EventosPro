<x-app-layout>
    @section('title', 'Fechamento Mensal')
    @php
        $months = [
            '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
            '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
            '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
            '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
        ];
        
        $currentYear = date('Y');
        $years = array_combine(range($currentYear, $currentYear - 5), range($currentYear, $currentYear - 5));
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Fechamento Mensal') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Relatório consolidado de vendas, comissões e cachês por período</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6">
            <form action="{{ route('finance.monthly-closing') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="month" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mês</label>
                        <select name="month" id="month" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            @foreach($months as $value => $name)
                                <option value="{{ $value }}" {{ $selectedMonth == $value ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ano</label>
                        <select name="year" id="year" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            @foreach($years as $year => $yearLabel)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $yearLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker (opcional)</label>
                        <select name="booker_id" id="booker_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                            <option value="">Todos os Bookers</option>
                            @foreach($bookers as $booker)
                                <option value="{{ $booker->id }}" {{ $selectedBookerId == $booker->id ? 'selected' : '' }}>{{ $booker->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filtrar
                        </button>
                        @if(request()->hasAny(['month', 'year', 'booker_id']))
                            <a href="{{ route('finance.monthly-closing') }}" class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Limpar
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Cartões de Totalizadores -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Card Azul: Total de Gigs -->
            <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Gigs</h3>
                <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">
                    {{ $reportData['total_gigs'] ?? 0 }}
                </p>
            </div>

            <!-- Card Verde: Cachê Bruto -->
            <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Cachê Bruto</h3>
                <p class="text-lg font-semibold text-green-800 dark:text-green-300">
                    R$ {{ isset($reportData['total_cache_brl']) ? number_format($reportData['total_cache_brl'], 2, ',', '.') : '0,00' }}
                </p>
            </div>

            <!-- Card Roxo: Comissão Booker -->
            <div class="bg-purple-100 dark:bg-purple-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Booker</h3>
                <p class="text-lg font-semibold text-purple-800 dark:text-purple-300">
                    R$ {{ isset($reportData['total_booker_commission']) ? number_format($reportData['total_booker_commission'], 2, ',', '.') : '0,00' }}
                </p>
            </div>

            <!-- Card Índigo: Comissão Agência -->
            <div class="bg-indigo-100 dark:bg-indigo-900/20 p-4 rounded-lg">
                <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Agência</h3>
                <p class="text-lg font-semibold text-indigo-800 dark:text-indigo-300">
                    R$ {{ isset($reportData['total_agency_commission']) ? number_format($reportData['total_agency_commission'], 2, ',', '.') : '0,00' }}
                </p>
            </div>
        </div>

        <!-- Gráfico de Faturamento por Booker -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Faturamento por Booker</h3>
            <div class="relative" style="height: 400px;">
                <canvas id="bookerRevenueChart"></canvas>
                <div id="noRevenueData" class="hidden absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-800 rounded-lg">
                    <p class="text-gray-500 dark:text-gray-400 text-center p-4">Nenhum dado disponível para o período selecionado</p>
                </div>
            </div>
        </div>

        <!-- Tabela de Faturamento por Booker -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Faturamento por Booker</h3>
                    <a href="{{ route('finance.monthly-closing.exportPdf', ['month' => $selectedMonth, 'year' => $selectedYear, 'booker_id' => $selectedBookerId]) }}" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="-ml-0.5 mr-1.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.5a2 2 0 00-2-2h-2.5l-2-3h-3L9.5 7.5H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        PDF
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">BOOKER</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">CACHÊ</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">COMISSÃO BOOKER</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">GIGS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $totalGigs = 0;
                            $totalCacheLiquido = 0;
                            $totalBookerCommission = 0;
                        @endphp
                        
                        @forelse(($reportData['booker_data'] ?? []) as $booker)
                            @php
                                $totalGigs += $booker['total_gigs'];
                                $totalCacheLiquido += $booker['cache_liquido_base'];
                                $totalBookerCommission += $booker['total_booker_commission'];
                            @endphp
                            
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $booker['booker']->name }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-right {{ $booker['cache_liquido_base'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $booker['cache_liquido_base'] < 0 ? '-' : '' }}R$ {{ number_format(abs($booker['cache_liquido_base']), 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-right {{ $booker['total_booker_commission'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-purple-600 dark:text-purple-400' }}">
                                    {{ $booker['total_booker_commission'] < 0 ? '-' : '' }}R$ {{ number_format(abs($booker['total_booker_commission']), 2, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-300">
                                    {{ $booker['total_gigs'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum dado disponível para o período selecionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-700 font-semibold">
                        <tr>
                            <td class="px-3 py-3 text-left text-sm font-medium text-gray-900 dark:text-white">TOTAL</td>
                            <td class="px-3 py-3 text-right text-sm font-semibold {{ $totalCacheLiquido < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $totalCacheLiquido < 0 ? '-' : '' }}R$ {{ number_format(abs($totalCacheLiquido), 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-right text-sm font-semibold {{ $totalBookerCommission < 0 ? 'text-red-600 dark:text-red-400' : 'text-purple-600 dark:text-purple-400' }}">
                                {{ $totalBookerCommission < 0 ? '-' : '' }}R$ {{ number_format(abs($totalBookerCommission), 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-center text-sm text-gray-500 dark:text-gray-300">
                                {{ $totalGigs }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Gráfico de Pizza por Booker -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Distribuição por Booker</h3>
                <div class="relative h-64">
                    <canvas id="bookerDistributionChart"></canvas>
                    <div id="noPieData" class="hidden absolute inset-0 flex items-center justify-center">
                        <p class="text-gray-500 dark:text-gray-400 text-center">Nenhum dado disponível para exibir o gráfico</p>
                    </div>
                </div>
            </div>

            <!-- Tabela de Top Artistas -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Top Artistas</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Ranking por valor de cachê</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gigs</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Cachê</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($reportData['artist_data'] ?? collect([]))->sortByDesc('total_cache_brl')->take(5) as $artist)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $artist['artist']->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    {{ $artist['total_gigs'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200 text-right">
                                    R$ {{ number_format($artist['total_cache_brl'], 2, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum dado disponível
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Lista de Gigs -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden mb-6">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Detalhes das Gigs</h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Lista completa de eventos no período</p>
                    </div>
                    <a href="{{ route('finance.monthly-closing.exportPdf', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Exportar PDF
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cachê Bruto</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-purple-500 dark:text-purple-300 uppercase tracking-wider">Comissão Booker</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-indigo-500 dark:text-indigo-300 uppercase tracking-wider">Comissão Agência</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @if(isset($reportData['artist_gigs']) && $reportData['artist_gigs']->count() > 0)
                            @foreach($reportData['artist_gigs'] as $artistData)
                                @php
                                    $artist = $artistData['artist'];
                                    $gigs = $artistData['gigs'];
                                    $isFirstGig = true;
                                @endphp
                                
                                @foreach($gigs as $gig)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    @if($isFirstGig)
                                        <td rowspan="{{ $gigs->count() }}" class="px-6 py-4 whitespace-nowrap border-r-2 border-gray-200 dark:border-gray-700">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <span class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-medium">
                                                        {{ substr($artist->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                                        {{ $artist->name }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $gigs->count() }} {{ $gigs->count() > 1 ? 'gigs' : 'gig' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        @php $isFirstGig = false; @endphp
                                    @endif
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $gig->gig_date->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $gig->gig_date->locale('pt-BR')->dayName }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white font-medium">{{ $gig->location_event_details }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $gig->location_city }}/{{ $gig->location_state }}</div>
                                        @if($gig->venue_name)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $gig->venue_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="font-medium {{ $gig->cache_value_brl < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                            {{ $gig->cache_value_brl < 0 ? '-' : '' }}R$ {{ number_format(abs($gig->cache_value_brl), 2, ',', '.') }}
                                        </div>
                                        @if($gig->cache_value_original_currency && $gig->cache_value_original_currency !== 'BRL')
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $gig->cache_value_original_currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="font-medium text-purple-600 dark:text-purple-400">
                                            {{ $gig->booker_commission_value < 0 ? '-' : '' }}R$ {{ number_format(abs($gig->booker_commission_value), 2, ',', '.') }}
                                        </div>
                                        @if($gig->booker_commission_percentage)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ number_format($gig->booker_commission_percentage, 2, ',', '.') }}%
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="font-medium text-indigo-600 dark:text-indigo-400">
                                            {{ $gig->agency_commission_value < 0 ? '-' : '' }}R$ {{ number_format(abs($gig->agency_commission_value), 2, ',', '.') }}
                                        </div>
                                        @if($gig->agency_commission_percentage)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ number_format($gig->agency_commission_percentage, 2, ',', '.') }}%
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                
                                <!-- Linha de totais do artista -->
                                <tr class="bg-gray-50 dark:bg-gray-700 font-medium">
                                    <td colspan="3" class="px-6 py-3 text-right text-sm text-gray-900 dark:text-white">
                                        Total {{ $artist->name }}:
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-900 dark:text-white">
                                        R$ {{ number_format($artistData['total_cache_brl'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-purple-600 dark:text-purple-300">
                                        R$ {{ number_format($artistData['total_booker_commission'], 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-3 text-right text-sm text-indigo-600 dark:text-indigo-300">
                                        R$ {{ number_format($artistData['total_agency_commission'], 2, ',', '.') }}
                                    </td>
                                </tr>
                                
                                <!-- Linha em branco para separar artistas -->
                                <tr>
                                    <td colspan="6" class="h-4"></td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma gig encontrada para o período selecionado
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    @push('scripts')
<script>
function loadChartJS(callback) {
    if (window.Chart) {
        console.log('Chart.js já está carregado');
        if (callback) callback();
        return;
    }

    console.log('Carregando Chart.js...');
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
    script.crossOrigin = 'anonymous';
    
    script.onload = function() {
        console.log('Chart.js carregado com sucesso');
        if (callback) callback();
    };
    
    script.onerror = function() {
        console.error('Falha ao carregar Chart.js');
        const fallbackScript = document.createElement('script');
        fallbackScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js';
        fallbackScript.crossOrigin = 'anonymous';
        fallbackScript.onload = function() {
            console.log('Chart.js carregado via fallback');
            if (callback) callback();
        };
        fallbackScript.onerror = function() {
            console.error('Falha ao carregar Chart.js via fallback');
        };
        document.head.appendChild(fallbackScript);
    };
    
    document.head.appendChild(script);
}

if (!window.monthlyClosingScriptExecuted) {
    window.monthlyClosingScriptExecuted = true;

    loadChartJS(function() {
        window.revenueChart = null;
        window.pieChart = null;

        function initializeCharts() {
            console.log('Iniciando script do relatório de fechamento mensal');

            if (typeof Chart === 'undefined') {
                console.error('Chart.js não foi carregado corretamente');
                return;
            }

            // === GRÁFICO DE BARRAS ===
            const bookerRevenueData = @json($bookerRevenueData ?? []);
            const revenueCtx = document.getElementById('bookerRevenueChart');
            const noRevenueData = document.getElementById('noRevenueData');

            console.log('Dados para o gráfico de barras:', bookerRevenueData);

            if (revenueCtx) {
                if (window.revenueChart?.destroy) {
                    console.log('Destruindo gráfico de barras existente...');
                    window.revenueChart.destroy();
                }

                if (bookerRevenueData.length > 0) {
                    noRevenueData?.classList.add('hidden');
                    try {
                        window.revenueChart = new Chart(revenueCtx, {
                            type: 'bar',
                            data: {
                                labels: bookerRevenueData.map(item => item.label),
                                datasets: [
                                    {
                                        label: 'Faturamento Bruto',
                                        data: bookerRevenueData.map(item => item.revenue || 0),
                                        backgroundColor: bookerRevenueData.map(item => item.color || '#3b82f6'),
                                        borderColor: bookerRevenueData.map(item => item.color || '#3b82f6'),
                                        borderWidth: 1,
                                        borderRadius: 4,
                                        barPercentage: 0.8,
                                        categoryPercentage: 0.8
                                    },
                                    {
                                        label: 'Comissão Booker',
                                        data: bookerRevenueData.map(item => item.booker_commission || 0),
                                        backgroundColor: 'rgba(147, 51, 234, 0.7)',
                                        borderColor: 'rgba(147, 51, 234, 1)',
                                        borderWidth: 1,
                                        borderRadius: 4,
                                        barPercentage: 0.8,
                                        categoryPercentage: 0.8
                                    },
                                    {
                                        label: 'Comissão Agência',
                                        data: bookerRevenueData.map(item => item.agency_commission || 0),
                                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                                        borderColor: 'rgba(99, 102, 241, 1)',
                                        borderWidth: 1,
                                        borderRadius: 4,
                                        barPercentage: 0.8,
                                        categoryPercentage: 0.8
                                    },
                                    {
                                        label: 'Valor Líquido',
                                        data: bookerRevenueData.map(item => item.net_value || 0),
                                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                        borderColor: 'rgba(16, 185, 129, 1)',
                                        borderWidth: 1,
                                        borderRadius: 4,
                                        barPercentage: 0.8,
                                        categoryPercentage: 0.8
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    } catch (error) {
                        console.error('Erro ao criar gráfico de barras:', error);
                        noRevenueData?.classList.remove('hidden');
                    }
                } else {
                    noRevenueData?.classList.remove('hidden');
                }
            }

            // === GRÁFICO DE PIZZA ===
            const bookerPieData = @json($bookerPieData ?? []);
            const pieCtx = document.getElementById('bookerDistributionChart');
            const noPieData = document.getElementById('noPieData');

            console.log('Dados para o gráfico de pizza:', bookerPieData);

            if (pieCtx) {
                if (window.pieChart?.destroy) {
                    console.log('Destruindo gráfico de pizza existente...');
                    window.pieChart.destroy();
                }

                if (bookerPieData.length > 0) {
                    noPieData?.classList.add('hidden');
                    const filteredData = bookerPieData.filter(item => item.value > 0);

                    if (filteredData.length > 0) {
                        window.pieChart = new Chart(pieCtx, {
                            type: 'pie',
                            data: {
                                labels: filteredData.map(item => item.label),
                                datasets: [{
                                    data: filteredData.map(item => item.value),
                                    backgroundColor: filteredData.map(item => item.color || '#cccccc'),
                                    borderWidth: 1,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false
                            }
                        });
                    } else {
                        noPieData?.classList.remove('hidden');
                    }
                } else {
                    noPieData?.classList.remove('hidden');
                }
            }
        }

        // Inicializar SOMENTE quando tudo (incluindo CSS) estiver carregado
        window.addEventListener('load', function() {
            setTimeout(initializeCharts, 100);
        });
    });
}
</script>
@endpush

</x-app-layout>