<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Relatórios Financeiros') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise detalhada de desempenho financeiro</p>
    </x-slot>

    <!-- Filtros -->
    <div class="container mx-auto px-4 py-6">
        @include('reports.partials.filters', ['bookers' => $bookers, 'artists' => $artists])
    </div>

    <!-- Abas e Conteúdo -->
    @php
        $initialTab = request()->input('tab', 'overview');
        $tabs = [
            ['id' => 'overview', 'label' => 'Visão Geral'],
            ['id' => 'profitability', 'label' => 'Rentabilidade'],
            ['id' => 'cashflow', 'label' => 'Fluxo de Caixa'],
            ['id' => 'commissions', 'label' => 'Comissões'],
            ['id' => 'expenses', 'label' => 'Despesas']
        ];
    @endphp

    <div class="container mx-auto px-4 py-6">
        <div x-data="{ activeTab: '{{ $initialTab }}' }" 
             class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
            <x-tab-nav :tabs="$tabs" />

            <!-- Conteúdo Dinâmico das Abas -->
            <div class="p-4 transition-opacity duration-300">
                <template x-if="activeTab === 'overview'">
                    <div>
                        @include('reports.partials.overview-table', ['filters' => $filters])
                    </div>
                </template>
                <template x-if="activeTab === 'profitability'">
                    <div>
                        @include('reports.partials.profitability-table', ['filters' => $filters])
                    </div>
                </template>
                <template x-if="activeTab === 'cashflow'">
                    <div>
                        @include('reports.partials.cashflow-table', ['filters' => $filters])
                    </div>
                </template>
                <template x-if="activeTab === 'commissions'">
                    <div>
                        @include('reports.partials.commissions-table', ['filters' => $filters])
                    </div>
                </template>
                <template x-if="activeTab === 'expenses'">
                    <div>
                        @include('reports.partials.expenses-table', ['filters' => $filters])
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</x-app-layout>