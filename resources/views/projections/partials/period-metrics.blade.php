{{-- Métricas do Período --}}
<div class="space-y-6">
    {{-- Cards de Métricas Gerenciais --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Índice de Liquidez --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Índice de Liquidez</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($executive_summary['liquidity_index'], 2) }}
                    </p>
                    @if(isset($comparative_analysis['liquidity_variation']))
                        <p class="mt-1 text-sm {{ $comparative_analysis['liquidity_variation'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $comparative_analysis['liquidity_variation'] >= 0 ? '↑' : '↓' }}
                            {{ number_format(abs($comparative_analysis['liquidity_variation']), 1) }}% vs período anterior
                        </p>
                    @endif
                </div>
                <div class="p-3 rounded-full {{ $executive_summary['liquidity_index'] >= 1.2 ? 'bg-green-100 dark:bg-green-900' : ($executive_summary['liquidity_index'] >= 1.0 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                    <svg class="w-8 h-8 {{ $executive_summary['liquidity_index'] >= 1.2 ? 'text-green-600 dark:text-green-400' : ($executive_summary['liquidity_index'] >= 1.0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                A Receber / Total a Pagar (ideal ≥ 1.2)
            </p>
        </div>

        {{-- Margem Operacional --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Margem Operacional</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($executive_summary['operational_margin'], 1) }}%
                    </p>
                    @if(isset($comparative_analysis['margin_variation']))
                        <p class="mt-1 text-sm {{ $comparative_analysis['margin_variation'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $comparative_analysis['margin_variation'] >= 0 ? '↑' : '↓' }}
                            {{ number_format(abs($comparative_analysis['margin_variation']), 1) }}pp vs período anterior
                        </p>
                    @endif
                </div>
                <div class="p-3 rounded-full {{ $executive_summary['operational_margin'] >= 20 ? 'bg-green-100 dark:bg-green-900' : ($executive_summary['operational_margin'] >= 10 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                    <svg class="w-8 h-8 {{ $executive_summary['operational_margin'] >= 20 ? 'text-green-600 dark:text-green-400' : ($executive_summary['operational_margin'] >= 10 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Fluxo Projetado / Contas a Receber
            </p>
        </div>

        {{-- Grau de Comprometimento --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Comprometimento</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($executive_summary['commitment_rate'], 1) }}%
                    </p>
                    @if(isset($comparative_analysis['commitment_variation']))
                        <p class="mt-1 text-sm {{ $comparative_analysis['commitment_variation'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $comparative_analysis['commitment_variation'] <= 0 ? '↓' : '↑' }}
                            {{ number_format(abs($comparative_analysis['commitment_variation']), 1) }}pp vs período anterior
                        </p>
                    @endif
                </div>
                <div class="p-3 rounded-full {{ $executive_summary['commitment_rate'] <= 70 ? 'bg-green-100 dark:bg-green-900' : ($executive_summary['commitment_rate'] <= 85 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                    <svg class="w-8 h-8 {{ $executive_summary['commitment_rate'] <= 70 ? 'text-green-600 dark:text-green-400' : ($executive_summary['commitment_rate'] <= 85 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Total a Pagar / Contas a Receber (ideal ≤ 70%)
            </p>
        </div>
    </div>

    {{-- Cards de Valores --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- Contas a Receber --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Contas a Receber</p>
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                R$ {{ number_format($executive_summary['receivable'], 2, ',', '.') }}
            </p>
        </div>

        {{-- Contas a Pagar - Artistas --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">A Pagar - Artistas</p>
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                R$ {{ number_format($executive_summary['breakdown']['payable_artists'], 2, ',', '.') }}
            </p>
        </div>

        {{-- Contas a Pagar - Bookers --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">A Pagar - Bookers</p>
                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                R$ {{ number_format($executive_summary['breakdown']['payable_bookers'], 2, ',', '.') }}
            </p>
        </div>

        {{-- Despesas Previstas --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Despesas Previstas</p>
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                R$ {{ number_format($executive_summary['breakdown']['payable_expenses'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Fluxo de Caixa Projetado --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Fluxo de Caixa Projetado</p>
                <p class="mt-2 text-4xl font-bold {{ $executive_summary['cash_flow'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    R$ {{ number_format($executive_summary['cash_flow'], 2, ',', '.') }}
                </p>
                @if(isset($comparative_analysis['cashflow_variation']))
                    <p class="mt-1 text-sm {{ $comparative_analysis['cashflow_variation'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $comparative_analysis['cashflow_variation'] >= 0 ? '↑' : '↓' }}
                        {{ number_format(abs($comparative_analysis['cashflow_variation']), 1) }}% vs período anterior
                    </p>
                @endif
            </div>
            <div class="p-4 rounded-full {{ $executive_summary['cash_flow'] >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }}">
                <svg class="w-10 h-10 {{ $executive_summary['cash_flow'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Análise de Eventos Futuros (se houver eventos no período) --}}
    @if(isset($future_events_analysis) && $future_events_analysis['total_events'] > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Análise de Eventos Futuros</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total de Eventos</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $future_events_analysis['total_events'] }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Receita Projetada</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($future_events_analysis['total_projected_revenue'], 2, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Custos Projetados</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">R$ {{ number_format($future_events_analysis['total_projected_costs'], 2, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Receita Líquida</p>
                    <p class="text-2xl font-bold {{ $future_events_analysis['projected_net_revenue'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        R$ {{ number_format($future_events_analysis['projected_net_revenue'], 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabelas Detalhadas --}}
    <div class="space-y-6">
        {{-- Pagamentos de Clientes --}}
        @if(isset($period_listings['upcoming_client_payments']) && count($period_listings['upcoming_client_payments']) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pagamentos de Clientes a Receber</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vencimento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($period_listings['upcoming_client_payments'] as $payment)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $payment->gig->event_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $payment->gig->artist->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $payment->due_date ? \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($payment->confirmed_at)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Confirmado
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                Pendente
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pagamentos a Artistas --}}
        @if(isset($period_listings['upcoming_artist_payments']) && count($period_listings['upcoming_artist_payments']) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pagamentos a Artistas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data do Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($period_listings['upcoming_artist_payments'] as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item['artist_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item['event_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($item['gig_date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        R$ {{ number_format($item['amount'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pagamentos a Bookers --}}
        @if(isset($period_listings['upcoming_booker_payments']) && count($period_listings['upcoming_booker_payments']) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Comissões de Bookers</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Booker</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data do Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($period_listings['upcoming_booker_payments'] as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item['booker_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $item['event_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($item['gig_date'])->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        R$ {{ number_format($item['amount'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Despesas por Centro de Custo --}}
        @if(isset($period_listings['projected_expenses_by_cost_center']) && count($period_listings['projected_expenses_by_cost_center']) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Despesas Previstas por Centro de Custo</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Centro de Custo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantidade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($period_listings['projected_expenses_by_cost_center'] as $expense)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $expense['cost_center_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ count($expense['expenses']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        R$ {{ number_format($expense['total_brl'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
