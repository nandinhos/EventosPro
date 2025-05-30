<x-app-layout>

<x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Relatórios Financeiros') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise detalhada de desempenho financeiro</p>
    </x-slot>

        <!-- Filtros -->
        <div >
    <div class="flex justify-end space-x-4 mb-4">
    <a href="{{ route('reports.export', ['type' => 'overview', 'format' => 'xlsx'] + (array) $filters) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
        <i class="fas fa-file-excel mr-2"></i> Exportar para Excel
    </a>
    <a href="{{ route('reports.export', ['type' => 'overview', 'format' => 'pdf'] + (array) $filters) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center">
        <i class="fas fa-file-pdf mr-2"></i> Imprimir/Exportar PDF
    </a>
</a>
    </div>
    @include('reports.partials.filters')
</div>

    <!-- Abas -->
    <div x-data="{ activeTab: 'overview' }" class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <x-tab-nav :tabs="[
            ['id' => 'overview', 'label' => 'Visão Geral'],
            ['id' => 'profitability', 'label' => 'Rentabilidade'],
            ['id' => 'cashflow', 'label' => 'Fluxo de Caixa'],
            ['id' => 'commissions', 'label' => 'Comissões'],
            ['id' => 'expenses', 'label' => 'Despesas']
        ]" />

        <!-- Conteúdo das Abas -->
        <div class="p-4">
            <!-- Visão Geral -->
            <div x-show="activeTab === 'overview'">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Faturamento Total</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($overviewSummary['total_revenue'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissões</h3>
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">R$ {{ number_format($overviewSummary['total_commissions'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-orange-100 dark:bg-orange-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Despesas</h3>
            <p class="text-lg font-semibold text-orange-800 dark:text-orange-300">R$ {{ number_format($overviewSummary['total_expenses'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Lucro Líquido</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">R$ {{ number_format($overviewSummary['net_profit'], 2, ',', '.') }}</p>
        </div>
    </div>
    @include('reports.partials.overview-table')
</div>

            <!-- Rentabilidade -->
            <div x-show="activeTab === 'profitability'">
                
                @include('reports.partials.profitability-table')
            </div>

            <!-- Fluxo de Caixa -->
            <div x-show="activeTab === 'cashflow'">
                
                @include('reports.partials.cashflow-table')
            </div>

            <!-- Comissões -->
            <div x-show="activeTab === 'commissions'">
                @include('reports.partials.commissions-table')
            </div>

            <!-- Despesas -->
            <div x-show="activeTab === 'expenses'">
                @include('reports.partials.expenses-table')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</x-app-layout>