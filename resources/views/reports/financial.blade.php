<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Relatório Financeiro') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
            <form action="{{ route('reports.financial') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="start_date" value="Data Inicial" />
                    <x-form.date
                        id="start_date"
                        name="start_date"
                        value="{{ request('start_date') }}"
                        class="block mt-1 w-full"
                    />
                </div>
                <div>
                    <x-input-label for="end_date" value="Data Final" />
                    <x-form.date
                        id="end_date"
                        name="end_date"
                        value="{{ request('end_date') }}"
                        class="block mt-1 w-full"
                    />
                </div>
                <div class="flex items-end">
                    <x-primary-button type="submit" class="w-full justify-center">
                        <i class="fas fa-search mr-2"></i> Filtrar
                    </x-primary-button>
                </div>
            </form>
        </div>

        <!-- Gráficos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Gráfico de Pizza - Faturamento por Booker -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Faturamento por Booker</h3>
                <div id="bookerRevenueChart" class="h-80"></div>
            </div>

            <!-- Gráfico de Barras - Comparativo de Comissões -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Comparativo de Comissões</h3>
                <div id="commissionsChart" class="h-80"></div>
            </div>
        </div>

        <!-- Tabela Detalhada -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Detalhamento de Eventos por Artista</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Local/Booker</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cachê</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comissão Booker</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comissão Agência</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($events as $artist => $artistEvents)
                            <!-- Cabeçalho do Artista -->
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <td colspan="5" class="px-6 py-3">
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">{{ $artist }}</h4>
                                </td>
                            </tr>
                            @foreach($artistEvents as $event)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $event->date }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ $event->location }}</div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 italic">{{ $event->booker }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">R$ {{ number_format($event->cache, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">R$ {{ number_format($event->booker_commission, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">R$ {{ number_format($event->agency_commission, 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            <!-- Totais do Artista -->
                            <tr class="bg-gray-100 dark:bg-gray-700/50">
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300" colspan="2">Total do Artista</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">R$ {{ number_format($artistEvents->sum('cache'), 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">R$ {{ number_format($artistEvents->sum('booker_commission'), 2, ',', '.') }}</td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">R$ {{ number_format($artistEvents->sum('agency_commission'), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Dados para os gráficos (serão substituídos por dados dinâmicos do backend)
        const bookerRevenueData = @json($bookerRevenueData);
        const commissionsData = @json($commissionsData);

        // Gráfico de Pizza - Faturamento por Booker
        const bookerRevenueChart = new ApexCharts(document.querySelector("#bookerRevenueChart"), {
            series: bookerRevenueData.values,
            labels: bookerRevenueData.labels,
            chart: {
                type: 'pie',
                height: 320
            },
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        });
        bookerRevenueChart.render();

        // Gráfico de Barras - Comparativo de Comissões
        const commissionsChart = new ApexCharts(document.querySelector("#commissionsChart"), {
            series: [{
                name: 'Comissão Booker',
                data: commissionsData.booker
            }, {
                name: 'Comissão Agência',
                data: commissionsData.agency
            }],
            chart: {
                type: 'bar',
                height: 320,
                stacked: true
            },
            plotOptions: {
                bar: {
                    horizontal: false
                }
            },
            xaxis: {
                categories: commissionsData.labels
            },
            theme: {
                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
            },
            legend: {
                position: 'top'
            }
        });
        commissionsChart.render();
    </script>
    @endpush
</x-app-layout>