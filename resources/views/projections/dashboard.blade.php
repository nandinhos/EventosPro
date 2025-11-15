{{-- resources/views/projections/dashboard.blade.php --}}
{{-- Versão refatorada com componentes reutilizáveis e layout minimalista --}}

<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
                    {{ __('Projeções Financeiras') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Contas a receber:
                    <span class="font-semibold text-red-600">R$ {{ number_format($accounts_receivable['total_overdue'] ?? 0, 2, ',', '.') }}</span> vencidas,
                    <span class="font-semibold text-green-600">R$ {{ number_format($accounts_receivable['total_future'] ?? 0, 2, ',', '.') }}</span> futuras
                </p>
            </div>

            @php
                $riskColors = [
                    'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                ];
                $riskLabels = [
                    'low' => 'Baixo Risco',
                    'medium' => 'Risco Moderado',
                    'high' => 'Alto Risco',
                ];
            @endphp
            <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $riskColors[$global_metrics['risk_level']] }}">
                {{ $riskLabels[$global_metrics['risk_level']] }}
            </span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- TABS NAVIGATION --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <a href="{{ route('projections.index') }}"
                           class="{{ !$period_metrics ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }} w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Métricas Gerais
                        </a>
                        <a href="{{ route('projections.index', ['start_date' => request('start_date', now()->startOfMonth()->format('Y-m-d')), 'end_date' => request('end_date', now()->endOfMonth()->format('Y-m-d'))]) }}"
                           class="{{ $period_metrics ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }} w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Por Período
                        </a>
                    </nav>
                </div>
            </div>

            {{-- ABA: MÉTRICAS GERAIS --}}
            @if(!$period_metrics)
            <div class="space-y-8">

                {{-- SEÇÃO 1: MÉTRICAS ESTRATÉGICAS --}}
                <section>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        Métricas Estratégicas
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-metrics.strategic-metric
                            title="Caixa Gerado (Eventos Passados)"
                            :value="'R$ ' . number_format($strategic_balance['generated_cash'], 2, ',', '.')"
                            subtitle="Balanço de operações concluídas"
                            color="blue"
                            tooltip="Total de receitas menos despesas de eventos já realizados. Representa o caixa efetivamente gerado."
                            :icon="'<svg class=\'w-6 h-6 text-blue-600\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' /></svg>'" />

                        <x-metrics.strategic-metric
                            title="Caixa Comprometido (Eventos Futuros)"
                            :value="'R$ ' . number_format($strategic_balance['committed_cash'], 2, ',', '.')"
                            subtitle="Saldo líquido de contratos futuros"
                            color="purple"
                            tooltip="Receitas esperadas menos despesas comprometidas para eventos futuros. Indica o caixa que será gerado."
                            :icon="'<svg class=\'w-6 h-6 text-purple-600\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z\' clip-rule=\'evenodd\' /></svg>'" />

                        <x-metrics.strategic-metric
                            :title="'Balanço Financeiro'"
                            :value="'R$ ' . number_format($strategic_balance['financial_balance'], 2, ',', '.')"
                            subtitle="Uso de caixa futuro no presente"
                            :color="$strategic_balance['financial_balance'] >= 0 ? 'green' : 'red'"
                            tooltip="Diferença entre o caixa gerado e o comprometido. Negativo indica que está usando caixa futuro para cobrir operações presentes."
                            :icon="'<svg class=\'w-6 h-6\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z\' clip-rule=\'evenodd\' /></svg>'" />
                    </div>
                </section>

                {{-- SEÇÃO 2: INDICADORES GERENCIAIS --}}
                <section>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Indicadores Gerenciais
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <x-metrics.kpi-card
                            title="Índice de Liquidez Global"
                            :value="number_format($global_metrics['liquidity_index'], 2, ',', '.')"
                            subtitle="Recebível / Total a Pagar"
                            :threshold="['good' => 1.2, 'warning' => 1.0]"
                            thresholdType="min"
                            tooltip="Indica a capacidade de pagar todas as obrigações com os recebíveis. Ideal: > 1.2" />

                        <x-metrics.kpi-card
                            title="Margem Operacional Global"
                            :value="number_format($global_metrics['operational_margin'], 1, ',', '.') . '%'"
                            subtitle="Fluxo / Recebível"
                            :threshold="['good' => 20, 'warning' => 10]"
                            thresholdType="min"
                            tooltip="Percentual do recebível que resta após pagar todas as obrigações. Ideal: > 20%"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z\' clip-rule=\'evenodd\' /></svg>'" />

                        <x-metrics.kpi-card
                            title="Comprometimento Global"
                            :value="number_format($global_metrics['commitment_rate'], 1, ',', '.') . '%'"
                            subtitle="Total a Pagar / Recebível"
                            :threshold="['good' => 70, 'warning' => 85]"
                            thresholdType="max"
                            tooltip="Percentual do recebível que está comprometido com pagamentos. Ideal: < 70%"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z\' clip-rule=\'evenodd\' /></svg>'" />
                    </div>
                </section>

                {{-- SEÇÃO 3: VALORES TOTAIS --}}
                <section>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Valores Globais
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {{-- Contas a Receber - Eventos Passados --}}
                        <x-metrics.value-card
                            title="Recebíveis de Eventos Passados"
                            :value="'R$ ' . number_format($accounts_receivable['total_overdue'] ?? 0, 2, ',', '.')"
                            :count="$accounts_receivable['overdue_count'] ?? 0"
                            subtitle="pagamentos"
                            color="red"
                            :badge="($accounts_receivable['overdue_count'] ?? 0) > 0 ? 'Ação necessária' : null"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z\' clip-rule=\'evenodd\' /></svg>'" />

                        {{-- Contas a Receber - Eventos Futuros --}}
                        <x-metrics.value-card
                            title="Recebíveis de Eventos Futuros"
                            :value="'R$ ' . number_format($accounts_receivable['total_future'] ?? 0, 2, ',', '.')"
                            :count="$accounts_receivable['future_count'] ?? 0"
                            subtitle="pagamentos"
                            color="green"
                            :badge="($accounts_receivable['future_count'] ?? 0) > 0 ? 'Próximos vencimentos' : null"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z\' clip-rule=\'evenodd\' /></svg>'" />

                        {{-- Contas a Pagar Artistas --}}
                        <x-metrics.value-card
                            title="Total Pagar Artistas"
                            :value="'R$ ' . number_format($global_metrics['total_payable_artists'], 2, ',', '.')"
                            subtitle="Cachês pendentes"
                            color="red"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z\' /></svg>'" />

                        {{-- Contas a Pagar Bookers --}}
                        <x-metrics.value-card
                            title="Total Pagar Bookers"
                            :value="'R$ ' . number_format($global_metrics['total_payable_bookers'], 2, ',', '.')"
                            subtitle="Comissões pendentes"
                            color="yellow"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z\' clip-rule=\'evenodd\' /></svg>'" />

                        {{-- Despesas de Eventos (GigCost) --}}
                        <x-metrics.value-card
                            title="Total Despesas de Eventos"
                            :value="'R$ ' . number_format($global_metrics['total_payable_expenses'], 2, ',', '.')"
                            subtitle="Despesas relacionadas aos eventos"
                            color="orange"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' /></svg>'" />

                        {{-- Custo Operacional Mensal (Fixo) --}}
                        <x-metrics.value-card
                            title="Custo Operacional Mensal"
                            :value="'R$ ' . number_format($global_metrics['operational_cost_monthly'] ?? 0, 2, ',', '.')"
                            :count="$global_metrics['operational_cost_count'] ?? 0"
                            subtitle="itens de custo fixo"
                            color="gray"
                            :link="route('agency-costs.index')"
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3 1a1 1 0 000 2h8a1 1 0 100-2H5z\' /></svg>'" />

                        {{-- NOVA MÉTRICA: Total a Pagar Consolidado --}}
                        <x-metrics.value-card
                            title="Total a Pagar Consolidado"
                            :value="'R$ ' . number_format($global_metrics['total_payable_consolidated'] ?? 0, 2, ',', '.')"
                            subtitle="Artistas + Bookers + Despesas + {{ $global_metrics['operational_cost_projected_months'] ?? 3 }} meses de custos op."
                            color="indigo"
                            :badge="'Projeção ' . ($global_metrics['operational_cost_projected_months'] ?? 3) . ' meses'"
                            tooltip="Soma de todas as obrigações financeiras: pagamentos aos artistas, comissões aos bookers, despesas de eventos e custos operacionais projetados para {{ $global_metrics['operational_cost_projected_months'] ?? 3 }} meses."
                            :icon="'<svg class=\'w-8 h-8\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' /></svg>'" />
                    </div>
                </section>

                {{-- SEÇÃO 3.5: CUSTOS OPERACIONAIS SEGREGADOS (NOVO) --}}
                @if(isset($operational_expenses_details) && $operational_expenses_details['expense_count'] > 0)
                <section>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Custos Operacionais Segregados ({{ $operational_expenses_details['expense_count'] }} itens)
                    </h3>

                    @php
                        $totalGig = 0;
                        $totalAgency = 0;
                        foreach ($operational_expenses_details['by_category'] as $category) {
                            foreach ($category['items'] as $item) {
                                if (isset($item['cost_type'])) {
                                    $costTypeValue = is_object($item['cost_type']) ? $item['cost_type']->value : $item['cost_type'];
                                    if ($costTypeValue === 'operacional') {
                                        $totalGig += $item['amount_monthly'];
                                    } else {
                                        $totalAgency += $item['amount_monthly'];
                                    }
                                }
                            }
                        }
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        {{-- Total Custos Operacionais (GIG) --}}
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-100 uppercase tracking-wide">Custos Operacionais (GIG)</p>
                                    <p class="text-3xl font-bold mt-2">R$ {{ number_format($totalGig, 2, ',', '.') }}</p>
                                    <p class="text-xs text-green-100 mt-1">Relacionados a eventos</p>
                                </div>
                                <div class="bg-white/20 rounded-full p-3">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Total Custos Administrativos (AGENCY) --}}
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-blue-100 uppercase tracking-wide">Custos Administrativos (AGENCY)</p>
                                    <p class="text-3xl font-bold mt-2">R$ {{ number_format($totalAgency, 2, ',', '.') }}</p>
                                    <p class="text-xs text-blue-100 mt-1">Overhead fixo</p>
                                </div>
                                <div class="bg-white/20 rounded-full p-3">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm1 2a1 1 0 000 2h6a1 1 0 100-2H7zm6 7a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1zm-3 3a1 1 0 100 2h.01a1 1 0 100-2H10zm-4 1a1 1 0 011-1h.01a1 1 0 110 2H7a1 1 0 01-1-1zm1-4a1 1 0 100 2h.01a1 1 0 100-2H7zm2 1a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm4-4a1 1 0 100 2h.01a1 1 0 100-2H13zM9 9a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zM7 8a1 1 0 000 2h.01a1 1 0 000-2H7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        {{-- Total Geral --}}
                        <div class="bg-gradient-to-br from-gray-700 to-gray-800 rounded-lg shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-300 uppercase tracking-wide">Total Mensal</p>
                                    <p class="text-3xl font-bold mt-2">R$ {{ number_format($operational_expenses_details['total_monthly'], 2, ',', '.') }}</p>
                                    <p class="text-xs text-gray-300 mt-1">{{ $operational_expenses_details['expense_count'] }} custos ativos</p>
                                </div>
                                <div class="bg-white/20 rounded-full p-3">
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Breakdown por Categoria --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Breakdown por Centro de Custo</h4>
                        <div class="space-y-3">
                            @foreach($operational_expenses_details['by_category'] as $category)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $category['category'] }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($category['items']) }} item(ns)</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">R$ {{ number_format($category['total'], 2, ',', '.') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">mensal</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @endif

                {{-- SEÇÃO 4: DETALHAMENTO (TABELAS EXPANSÍVEIS) --}}
                <section>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Detalhamento
                    </h3>

                    @include('projections.partials.receivables-tables', [
                        'accounts_receivable' => $accounts_receivable,
                        'artist_payment_details' => $artist_payment_details,
                        'booker_payment_details' => $booker_payment_details ?? [],
                    ])
                </section>

            </div>
            @endif

            {{-- ABA: POR PERÍODO --}}
            @if($period_metrics)
                @include('projections.partials.period-metrics', ['executive_summary' => $period_metrics['executive_summary'], 'period_metrics' => $period_metrics])
            @endif

        </div>
    </div>
</x-app-layout>
