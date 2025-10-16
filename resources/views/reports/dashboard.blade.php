<x-app-layout>
    @php
        // Removido o 'use Illuminate\Support\Js;' daqui.

        $initialTab = request()->input('tab', 'overview');
        $tabs = [
            ['id' => 'overview', 'label' => 'Visão Geral'],
            ['id' => 'profitability', 'label' => 'Rentabilidade'],
            ['id' => 'cashflow', 'label' => 'Fluxo de Caixa'],
            ['id' => 'commissions', 'label' => 'Comissões Booker'],
            ['id' => 'artist_commissions', 'label' => 'Comissões Artistas'],
            ['id' => 'expenses', 'label' => 'Despesas']
        ];
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Relatórios Financeiros') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise detalhada de desempenho financeiro</p>
    </x-slot>

    {{-- Filtros --}}
    <div class="container mx-auto px-4 py-6">
        @include('reports.partials.filters', ['bookers' => $bookers, 'artists' => $artists])
    </div>

    {{-- Abas e Conteúdo com a Lógica Alpine Centralizada --}}
    <div class="container mx-auto px-4 py-6">
        {{-- A CORREÇÃO ESTÁ AQUI: Usamos \Illuminate\Support\Js::from() --}}
        <div x-data="reportsDashboard(
                '{{ $initialTab }}',
                {{ \Illuminate\Support\Js::from($profitabilityReport['chartData'] ?? []) }}
             )"
             class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">

            {{-- Navegação das Abas --}}
            <nav class="flex space-x-4 border-b border-gray-200 dark:border-gray-700 mb-4 px-4">
                @foreach ($tabs as $tab)
                    <button @click="activeTab = '{{ $tab['id'] }}'"
                       class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap focus:outline-none"
                       :class="activeTab === '{{ $tab['id'] }}' ? 'border-b-2 border-primary-500 text-primary-600 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </nav>

            {{-- Conteúdo Dinâmico das Abas --}}
            <div class="p-4 transition-opacity duration-300">
                <div x-show="activeTab === 'overview'" role="tabpanel">
                    {{-- O include para a Visão Geral --}}
                    @include('reports.partials.overview-table', ['detailedPerformanceReport' => $detailedPerformanceReport])
                </div>
                <div x-show="activeTab === 'profitability'" role="tabpanel">
                    {{-- O include para a Rentabilidade. Ele não precisa mais do @push('scripts') --}}
                    @include('reports.partials.profitability-table', ['profitabilityReport' => $profitabilityReport])
                </div>
                {{-- ... includes para outras abas (cashflow, commissions, expenses) ... --}}
                <div x-show="activeTab === 'cashflow'" role="tabpanel">
                    @include('reports.partials.cashflow-table', ['cashflowSummary' => $cashflowSummary ?? [], 'cashflowTable' => $cashflowTable ?? collect([])])
                </div>
                 <div x-show="activeTab === 'commissions'" role="tabpanel">
                    @include('reports.partials.commissions-table', ['commissionsReport' => $commissionsReport ?? []])
                </div>
                 <div x-show="activeTab === 'artist_commissions'" role="tabpanel">
                    @include('reports.partials.artist-commissions-table', ['artistCommissionsReport' => $artistCommissionsReport ?? []])
                </div>
                 <div x-show="activeTab === 'expenses'" role="tabpanel">
                    @include('reports.partials.expenses-table', ['expensesSummary' => $expensesSummary ?? [], 'expensesTable' => $expensesTable ?? collect([])])
                </div>
            </div>
        </div>
    </div>

    {{-- O script agora está aqui, e a função reportsDashboard será encontrada --}}
    @pushOnce('scripts')
    <script>
        function reportsDashboard(initialTab, profitabilityChartData) {
            return {
                activeTab: initialTab,
                profitabilityChartsInitialized: false, // Flag para inicializar só uma vez

                init() {
                    // Observa a mudança da aba ativa
                    this.$watch('activeTab', (newTab) => {
                        if (newTab === 'profitability') {
                            this.initProfitabilityCharts();
                        }
                        // Adicionar 'else if' para outras abas com gráficos no futuro
                    });

                    // Verifica se a página já carregou na aba de rentabilidade
                    if (this.activeTab === 'profitability') {
                        // $nextTick garante que o DOM da aba esteja visível antes de tentar desenhar
                        this.$nextTick(() => this.initProfitabilityCharts());
                    }
                },

                // Função para inicializar os gráficos da aba Rentabilidade
                initProfitabilityCharts() {
                    if (this.profitabilityChartsInitialized) return; // Não reinicializa se já foi feito
                    
                    const chartData = profitabilityChartData;

                    // Lógica para desenhar os 3 gráficos...
                    // (código dos 3 'new Chart(...)' da resposta anterior)

                    // Gráfico 1: Evolução da Comissão Líquida
                    const ctxNetCommission = document.getElementById('netAgencyCommissionChart');
                    if (ctxNetCommission && chartData.labels) {
                        new Chart(ctxNetCommission, {
                            type: 'line',
                            data: { labels: chartData.labels, datasets: [{ label: 'Comissão Líquida (R$)', data: chartData.netAgencyCommission, borderColor: 'rgb(59, 130, 246)', backgroundColor: 'rgba(59, 130, 246, 0.5)', fill: true, tension: 0.1 }] }
                        });
                    }

                    // Gráfico 2: Evolução da Margem Bruta
                    const ctxGrossMargin = document.getElementById('grossMarginChart');
                    if (ctxGrossMargin && chartData.labels) {
                         new Chart(ctxGrossMargin, {
                            type: 'bar',
                            data: { labels: chartData.labels, datasets: [{ label: 'Margem Bruta (%)', data: chartData.grossMarginPercentage, backgroundColor: 'rgba(234, 179, 8, 0.7)' }] },
                            options: { scales: { y: { ticks: { callback: (value) => `${value}%` } } } }
                        });
                    }

                    // Gráfico 3: Comissão por Booker
                    const ctxCommissionByBooker = document.getElementById('commissionByBookerChart');
                    if (ctxCommissionByBooker && chartData.commissionByBooker) {
                        new Chart(ctxCommissionByBooker, {
                            type: 'bar',
                            data: { labels: chartData.commissionByBooker.labels, datasets: [{ label: 'Comissão Líquida por Booker (R$)', data: chartData.commissionByBooker.data, backgroundColor: 'rgba(139, 92, 246, 0.7)' }] },
                            options: { indexAxis: 'y' } // Barras horizontais
                        });
                    }

                    this.profitabilityChartsInitialized = true;
                }
            };
        }
    </script>
    @endPushOnce
</x-app-layout>