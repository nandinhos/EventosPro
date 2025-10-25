{{-- Métricas do Período (apenas Cards de KPIs) --}}
<div class="space-y-6">
    {{-- Cards de Métricas Gerenciais --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Índice de Liquidez --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 {{ $executive_summary['liquidity_index'] >= 1.2 ? 'border-green-500' : ($executive_summary['liquidity_index'] >= 1.0 ? 'border-yellow-500' : 'border-red-500') }}">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Índice de Liquidez</p>
                    <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                        {{ number_format($executive_summary['liquidity_index'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Recebível / Total a Pagar
                    </p>
                </div>
                <div class="ml-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $executive_summary['liquidity_index'] >= 1.2 ? 'bg-green-100 dark:bg-green-900' : ($executive_summary['liquidity_index'] >= 1.0 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                        <svg class="w-8 h-8 {{ $executive_summary['liquidity_index'] >= 1.2 ? 'text-green-600 dark:text-green-400' : ($executive_summary['liquidity_index'] >= 1.0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Margem Operacional --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 {{ $executive_summary['operational_margin'] >= 20 ? 'border-green-500' : ($executive_summary['operational_margin'] >= 10 ? 'border-yellow-500' : 'border-red-500') }}">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Margem Operacional</p>
                    <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                        {{ number_format($executive_summary['operational_margin'], 1, ',', '.') }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Fluxo / Recebível
                    </p>
                </div>
                <div class="ml-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $executive_summary['operational_margin'] >= 20 ? 'bg-green-100 dark:bg-green-900' : ($executive_summary['operational_margin'] >= 10 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                        <svg class="w-8 h-8 {{ $executive_summary['operational_margin'] >= 20 ? 'text-green-600 dark:text-green-400' : ($executive_summary['operational_margin'] >= 10 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grau de Comprometimento --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 {{ $executive_summary['commitment_rate'] <= 70 ? 'border-green-500' : ($executive_summary['commitment_rate'] <= 85 ? 'border-yellow-500' : 'border-red-500') }}">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Comprometimento</p>
                    <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                        {{ number_format($executive_summary['commitment_rate'], 1, ',', '.') }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Total a Pagar / Recebível
                    </p>
                </div>
                <div class="ml-4">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $executive_summary['commitment_rate'] <= 70 ? 'bg-green-100 dark:bg-green-900' : ($executive_summary['commitment_rate'] <= 85 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                        <svg class="w-8 h-8 {{ $executive_summary['commitment_rate'] <= 70 ? 'text-green-600 dark:text-green-400' : ($executive_summary['commitment_rate'] <= 85 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fluxo de Caixa Projetado (Destaque) --}}
    <div class="bg-gradient-to-br {{ $executive_summary['cash_flow'] >= 0 ? 'from-blue-500 to-blue-600' : 'from-red-500 to-red-600' }} rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium {{ $executive_summary['cash_flow'] >= 0 ? 'text-blue-100' : 'text-red-100' }} uppercase tracking-wide">
                    Fluxo de Caixa Projetado
                </p>
                <p class="text-4xl font-bold mt-2">
                    R$ {{ number_format($executive_summary['cash_flow'], 2, ',', '.') }}
                </p>
                <p class="text-sm {{ $executive_summary['cash_flow'] >= 0 ? 'text-blue-100' : 'text-red-100' }} mt-1">
                    @if($executive_summary['cash_flow'] >= 0)
                        Saldo positivo no período
                    @else
                        Atenção: Saldo negativo no período
                    @endif
                </p>
            </div>
            <div class="ml-4">
                <div class="bg-white/20 rounded-full p-4">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                        @if($executive_summary['cash_flow'] >= 0)
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        @else
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        @endif
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Análise de Eventos Futuros (se houver eventos no período) --}}
    @if(isset($future_events_analysis) && $future_events_analysis['total_events'] > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>
                Análise de Eventos Futuros
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total de Eventos</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $future_events_analysis['total_events'] }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Receita Projetada</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($future_events_analysis['total_projected_revenue'], 2, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Custos Projetados</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">R$ {{ number_format($future_events_analysis['total_projected_costs'], 2, ',', '.') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Receita Líquida</p>
                    <p class="text-3xl font-bold {{ $future_events_analysis['projected_net_revenue'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        R$ {{ number_format($future_events_analysis['projected_net_revenue'], 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
