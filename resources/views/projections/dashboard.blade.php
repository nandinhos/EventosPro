{{-- resources/views/projections/dashboard.blade.php --}}
{{-- Versão simplificada - apenas conteúdo de período com filtros --}}

<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
                    {{ __('Projeções Financeiras') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Análise de contas a receber e pagar por período
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
            <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $riskColors[$global_metrics['risk_level'] ?? 'low'] }}">
                {{ $riskLabels[$global_metrics['risk_level'] ?? 'low'] }}
            </span>
        </div>
    </x-slot>

    <div class="py-6" id="dashboard-top">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- FILTROS DE PERÍODO --}}
            <section class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <form method="GET" action="{{ route('projections.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Data Inicial
                        </label>
                        <input type="date" name="start_date" id="start_date"
                               value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Data Final
                        </label>
                        <input type="date" name="end_date" id="end_date"
                               value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Aplicar
                        </button>
                        <a href="{{ route('projections.index', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                            Mês Atual
                        </a>
                    </div>
                </form>
            </section>

            {{-- 4 CARDS DE MÉTRICAS NO ESTILO RELATÓRIOS (CLICÁVEIS) --}}
            <section>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Resumo Financeiro do Período
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Card 1: Contas a Receber (Verde) --}}
                    @php
                        $recTotal = $period_accounts_receivable['total_receivable'] ?? 0;
                        $recPast = $period_accounts_receivable['total_past'] ?? 0;
                        $recFuture = $period_accounts_receivable['total_future'] ?? 0;
                        $recPastPct = $recTotal > 0 ? round(($recPast / $recTotal) * 100) : 0;
                    @endphp
                    <a href="#tabela-receber" class="bg-green-100 dark:bg-green-900/20 p-4 rounded-lg hover:shadow-md transition-shadow cursor-pointer block border border-green-200 dark:border-green-800">
                        <h3 class="text-sm text-gray-500 dark:text-gray-400">Contas a Receber</h3>
                        <p class="text-xl font-semibold text-green-800 dark:text-green-300 mt-1">
                            R$ {{ number_format($recTotal, 2, ',', '.') }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-green-200 dark:border-green-700 text-xs space-y-1">
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>📅 Passado</span>
                                <span class="font-semibold text-green-700 dark:text-green-400">R$ {{ number_format($recPast, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>🔮 Futuro</span>
                                <span class="font-semibold text-green-700 dark:text-green-400">R$ {{ number_format($recFuture, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </a>

                    {{-- Card 2: Contas a Pagar Artistas (Vermelho) --}}
                    @php
                        $artTotal = $period_artist_payments['total_pending'] ?? 0;
                        $artPast = $period_artist_payments['total_past'] ?? 0;
                        $artFuture = $period_artist_payments['total_future'] ?? 0;
                        $artPastPct = $artTotal > 0 ? round(($artPast / $artTotal) * 100) : 0;
                    @endphp
                    <a href="#tabela-artistas" class="bg-red-100 dark:bg-red-900/20 p-4 rounded-lg hover:shadow-md transition-shadow cursor-pointer block border border-red-200 dark:border-red-800">
                        <h3 class="text-sm text-gray-500 dark:text-gray-400">Pagar Artistas</h3>
                        <p class="text-xl font-semibold text-red-800 dark:text-red-300 mt-1">
                            R$ {{ number_format($artTotal, 2, ',', '.') }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-red-200 dark:border-red-700 text-xs space-y-1">
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>📅 Passado</span>
                                <span class="font-semibold text-red-700 dark:text-red-400">R$ {{ number_format($artPast, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>🔮 Futuro</span>
                                <span class="font-semibold text-red-700 dark:text-red-400">R$ {{ number_format($artFuture, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </a>

                    {{-- Card 3: Contas a Pagar Bookers (Amarelo) --}}
                    @php
                        $booTotal = $period_booker_payments['total_pending'] ?? 0;
                        $booPast = $period_booker_payments['total_past'] ?? 0;
                        $booFuture = $period_booker_payments['total_future'] ?? 0;
                        $booPastPct = $booTotal > 0 ? round(($booPast / $booTotal) * 100) : 0;
                    @endphp
                    <a href="#tabela-bookers" class="bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg hover:shadow-md transition-shadow cursor-pointer block border border-yellow-200 dark:border-yellow-800">
                        <h3 class="text-sm text-gray-500 dark:text-gray-400">Pagar Bookers</h3>
                        <p class="text-xl font-semibold text-yellow-800 dark:text-yellow-300 mt-1">
                            R$ {{ number_format($booTotal, 2, ',', '.') }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-yellow-200 dark:border-yellow-700 text-xs space-y-1">
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>📅 Passado</span>
                                <span class="font-semibold text-yellow-700 dark:text-yellow-400">R$ {{ number_format($booPast, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>🔮 Futuro</span>
                                <span class="font-semibold text-yellow-700 dark:text-yellow-400">R$ {{ number_format($booFuture, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </a>

                    {{-- Card 4: Despesas de Eventos (Azul) --}}
                    @php
                        $expTotal = $period_expenses['total_expenses'] ?? 0;
                        $expPast = $period_expenses['total_past'] ?? 0;
                        $expFuture = $period_expenses['total_future'] ?? 0;
                        $expPastPct = $expTotal > 0 ? round(($expPast / $expTotal) * 100) : 0;
                    @endphp
                    <a href="#tabela-despesas" class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg hover:shadow-md transition-shadow cursor-pointer block border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm text-gray-500 dark:text-gray-400">Despesas de Eventos</h3>
                        <p class="text-xl font-semibold text-blue-800 dark:text-blue-300 mt-1">
                            R$ {{ number_format($expTotal, 2, ',', '.') }}
                        </p>
                        <div class="mt-2 pt-2 border-t border-blue-200 dark:border-blue-700 text-xs space-y-1">
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>📅 Passado</span>
                                <span class="font-semibold text-blue-700 dark:text-blue-400">R$ {{ number_format($expPast, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                <span>🔮 Futuro</span>
                                <span class="font-semibold text-blue-700 dark:text-blue-400">R$ {{ number_format($expFuture, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </a>
                </div>

            </section>

            {{-- RESUMO CONSOLIDADO --}}
            @php
                $totalReceivable = $period_accounts_receivable['total_receivable'] ?? 0;
                $totalPayable = ($period_artist_payments['total_pending'] ?? 0)
                              + ($period_booker_payments['total_pending'] ?? 0)
                              + ($period_expenses['total_expenses'] ?? 0);
                $netBalance = $totalReceivable - $totalPayable;
            @endphp
            <section class="bg-gradient-to-r {{ $netBalance >= 0 ? 'from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-green-200 dark:border-green-800' : 'from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-red-200 dark:border-red-800' }} rounded-lg shadow-sm p-6 border">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Saldo Projetado do Período</h3>
                        <p class="text-3xl font-bold {{ $netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-2">
                            R$ {{ number_format($netBalance, 2, ',', '.') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            @if($netBalance >= 0)
                                Saldo positivo - situação financeira saudável
                            @else
                                Atenção: Saldo negativo no período
                            @endif
                        </p>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">A Receber</p>
                            <p class="text-lg font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($totalReceivable, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">A Pagar</p>
                            <p class="text-lg font-semibold text-red-600 dark:text-red-400">R$ {{ number_format($totalPayable, 2, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Resultado</p>
                            <p class="text-lg font-semibold {{ $netBalance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $netBalance >= 0 ? '+' : '' }}{{ number_format($netBalance, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- TABELAS DETALHADAS COM EXPANDABLE-SECTION --}}
            <section class="space-y-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Detalhamento por Categoria
                </h3>

                {{-- Tabela 1: Contas a Receber --}}
                <div id="tabela-receber" class="scroll-mt-4">
                    <x-expandable-section
                        title="Contas a Receber"
                        :count="($period_accounts_receivable['payment_count'] ?? 0) . ' pagamentos'"
                        color="green"
                        :expanded="false"
                        :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z\' clip-rule=\'evenodd\' /></svg>'">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-green-50 dark:bg-green-900/20">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Vencimento</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Artista / Gig</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {{-- GRUPO: EVENTOS PASSADOS (Realizados) --}}
                                    @if(count($period_accounts_receivable['past_payments'] ?? []) > 0)
                                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                                            <td colspan="4" class="px-6 py-3 text-sm font-semibold text-amber-800 dark:text-amber-300">
                                                📅 Eventos Passados (Realizados) — {{ count($period_accounts_receivable['past_payments']) }} pagamentos
                                            </td>
                                        </tr>
                                        @foreach($period_accounts_receivable['past_payments'] ?? [] as $payment)
                                            <tr class="hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors border-l-4 border-amber-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $payment['due_date'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                                    @if(isset($payment['gig_id']))
                                                        <div class="flex items-center gap-2 text-xs">
                                                            <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400">{{ $payment['gig_contract'] ?? "Gig #{$payment['gig_id']}" }}</a>
                                                            <span class="text-gray-400">•</span>
                                                            <span class="text-gray-600 dark:text-gray-400">📅 {{ $payment['gig_date'] ?? 'N/A' }}</span>
                                                        </div>
                                                        @if(isset($payment['location']) && $payment['location'])
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate max-w-xs" title="{{ $payment['location'] }}">📍 {{ Str::limit($payment['location'], 40) }}</p>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-green-700 dark:text-green-300">R$ {{ number_format($payment['due_value_brl'] ?? 0, 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 whitespace-nowrap text-center">
                                                    @php $daysUntil = $payment['days_until_due'] ?? 0; @endphp
                                                    @if($daysUntil < 0)
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Vencido {{ abs($daysUntil) }}d</span>
                                                    @else
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ $daysUntil }}d</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-amber-100 dark:bg-amber-900/30">
                                            <td colspan="2" class="px-6 py-2 text-right text-xs font-semibold text-amber-700 dark:text-amber-300">Subtotal Passado:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-amber-800 dark:text-amber-200">R$ {{ number_format($period_accounts_receivable['total_past'] ?? 0, 2, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    @endif

                                    {{-- GRUPO: EVENTOS FUTUROS (Projetados) --}}
                                    @if(count($period_accounts_receivable['future_payments'] ?? []) > 0)
                                        <tr class="bg-emerald-50 dark:bg-emerald-900/20">
                                            <td colspan="4" class="px-6 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                                🔮 Eventos Futuros (Projetados) — {{ count($period_accounts_receivable['future_payments']) }} pagamentos
                                            </td>
                                        </tr>
                                        @foreach($period_accounts_receivable['future_payments'] ?? [] as $payment)
                                            <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-colors border-l-4 border-emerald-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $payment['due_date'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                                    @if(isset($payment['gig_id']))
                                                        <div class="flex items-center gap-2 text-xs">
                                                            <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400">{{ $payment['gig_contract'] ?? "Gig #{$payment['gig_id']}" }}</a>
                                                            <span class="text-gray-400">•</span>
                                                            <span class="text-gray-600 dark:text-gray-400">📅 {{ $payment['gig_date'] ?? 'N/A' }}</span>
                                                        </div>
                                                        @if(isset($payment['location']) && $payment['location'])
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate max-w-xs" title="{{ $payment['location'] }}">📍 {{ Str::limit($payment['location'], 40) }}</p>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-green-700 dark:text-green-300">R$ {{ number_format($payment['due_value_brl'] ?? 0, 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 whitespace-nowrap text-center">
                                                    @php $daysUntil = $payment['days_until_due'] ?? 0; @endphp
                                                    @if($daysUntil <= 7)
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ $daysUntil }}d</span>
                                                    @else
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ $daysUntil }}d</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-emerald-100 dark:bg-emerald-900/30">
                                            <td colspan="2" class="px-6 py-2 text-right text-xs font-semibold text-emerald-700 dark:text-emerald-300">Subtotal Futuro:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-emerald-800 dark:text-emerald-200">R$ {{ number_format($period_accounts_receivable['total_future'] ?? 0, 2, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    @endif

                                    @if(count($period_accounts_receivable['payments'] ?? []) == 0)
                                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum pagamento pendente no período</td></tr>
                                    @endif
                                </tbody>
                                @if(($period_accounts_receivable['payment_count'] ?? 0) > 0)
                                    <tfoot class="bg-green-100 dark:bg-green-900/30">
                                        <tr>
                                            <td colspan="2" class="px-6 py-4 text-right font-bold text-green-800 dark:text-green-200">TOTAL GERAL:</td>
                                            <td class="px-6 py-4 text-right font-bold text-lg text-green-900 dark:text-green-100">R$ {{ number_format($period_accounts_receivable['total_receivable'] ?? 0, 2, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/30 flex justify-end">
                            <a href="#dashboard-top" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                Voltar ao topo
                            </a>
                        </div>
                    </x-expandable-section>
                </div>


                {{-- Tabela 2: Pagamentos Artistas --}}
                <div id="tabela-artistas" class="scroll-mt-4">
                    <x-expandable-section
                        title="Pagamentos a Artistas"
                        :count="($period_artist_payments['gig_count'] ?? 0) . ' eventos'"
                        color="red"
                        :expanded="false"
                        :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path d=\'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z\' /></svg>'">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-red-50 dark:bg-red-900/20">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Data Evento</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Artista / Local</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Valor Total</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Pago</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-red-700 dark:text-red-300 uppercase tracking-wider">Pendente</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {{-- GRUPO: EVENTOS PASSADOS (Realizados) --}}
                                    @if(count($period_artist_payments['past_payments'] ?? []) > 0)
                                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-semibold text-amber-800 dark:text-amber-300">
                                                📅 Eventos Passados (Realizados) — {{ count($period_artist_payments['past_payments']) }} eventos
                                            </td>
                                        </tr>
                                        @foreach($period_artist_payments['past_payments'] ?? [] as $payment)
                                            <tr class="hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors border-l-4 border-amber-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $payment['gig_date'] }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                                    <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:underline text-xs">{{ $payment['gig_contract'] }}</a>
                                                    <p class="text-xs text-gray-500 mt-1">📍 {{ Str::limit($payment['location'] ?? 'N/A', 30) }}</p>
                                                </td>
                                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($payment['artist_payout_total'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm font-semibold text-red-700 dark:text-red-300">R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-amber-100 dark:bg-amber-900/30">
                                            <td colspan="4" class="px-6 py-2 text-right text-xs font-semibold text-amber-700 dark:text-amber-300">Subtotal Passado:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-amber-800 dark:text-amber-200">R$ {{ number_format($period_artist_payments['total_past'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    @endif

                                    {{-- GRUPO: EVENTOS FUTUROS (Projetados) --}}
                                    @if(count($period_artist_payments['future_payments'] ?? []) > 0)
                                        <tr class="bg-emerald-50 dark:bg-emerald-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                                🔮 Eventos Futuros (Projetados) — {{ count($period_artist_payments['future_payments']) }} eventos
                                            </td>
                                        </tr>
                                        @foreach($period_artist_payments['future_payments'] ?? [] as $payment)
                                            <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-colors border-l-4 border-emerald-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $payment['gig_date'] }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                                    <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:underline text-xs">{{ $payment['gig_contract'] }}</a>
                                                    <p class="text-xs text-gray-500 mt-1">📍 {{ Str::limit($payment['location'] ?? 'N/A', 30) }}</p>
                                                </td>
                                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($payment['artist_payout_total'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm font-semibold text-red-700 dark:text-red-300">R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-emerald-100 dark:bg-emerald-900/30">
                                            <td colspan="4" class="px-6 py-2 text-right text-xs font-semibold text-emerald-700 dark:text-emerald-300">Subtotal Futuro:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-emerald-800 dark:text-emerald-200">R$ {{ number_format($period_artist_payments['total_future'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    @endif

                                    @if(count($period_artist_payments['payments'] ?? []) == 0)
                                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhum pagamento pendente a artistas</td></tr>
                                    @endif
                                </tbody>
                                @if(($period_artist_payments['gig_count'] ?? 0) > 0)
                                    <tfoot class="bg-red-100 dark:bg-red-900/30">
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-right font-bold text-red-800 dark:text-red-200">TOTAL GERAL:</td>
                                            <td class="px-6 py-4 text-right font-bold text-lg text-red-900 dark:text-red-100">R$ {{ number_format($period_artist_payments['total_pending'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/30 flex justify-end">
                            <a href="#dashboard-top" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                Voltar ao topo
                            </a>
                        </div>
                    </x-expandable-section>
                </div>


                {{-- Tabela 3: Comissões Bookers --}}
                <div id="tabela-bookers" class="scroll-mt-4">
                    <x-expandable-section
                        title="Comissões Bookers"
                        :count="($period_booker_payments['gig_count'] ?? 0) . ' comissões'"
                        color="yellow"
                        :expanded="false"
                        :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z\' clip-rule=\'evenodd\' /></svg>'">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-yellow-50 dark:bg-yellow-900/20">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">Data Evento</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">Artista / Booker</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">Comissão</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">Pago</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">Pendente</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {{-- GRUPO: EVENTOS PASSADOS (Realizados) --}}
                                    @if(count($period_booker_payments['past_payments'] ?? []) > 0)
                                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-semibold text-amber-800 dark:text-amber-300">
                                                📅 Eventos Passados (Realizados) — {{ count($period_booker_payments['past_payments']) }} comissões
                                            </td>
                                        </tr>
                                        @foreach($period_booker_payments['past_payments'] ?? [] as $payment)
                                            <tr class="hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors border-l-4 border-amber-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $payment['gig_date'] }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                                    <p class="text-xs text-gray-500">Booker: {{ $payment['booker_name'] }}</p>
                                                    <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:underline text-xs">{{ $payment['gig_contract'] }}</a>
                                                </td>
                                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($payment['booker_commission_value'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm font-semibold text-yellow-700 dark:text-yellow-300">R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-amber-100 dark:bg-amber-900/30">
                                            <td colspan="4" class="px-6 py-2 text-right text-xs font-semibold text-amber-700 dark:text-amber-300">Subtotal Passado:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-amber-800 dark:text-amber-200">R$ {{ number_format($period_booker_payments['total_past'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    @endif

                                    {{-- GRUPO: EVENTOS FUTUROS (Projetados) --}}
                                    @if(count($period_booker_payments['future_payments'] ?? []) > 0)
                                        <tr class="bg-emerald-50 dark:bg-emerald-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                                🔮 Eventos Futuros (Projetados) — {{ count($period_booker_payments['future_payments']) }} comissões
                                            </td>
                                        </tr>
                                        @foreach($period_booker_payments['future_payments'] ?? [] as $payment)
                                            <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-colors border-l-4 border-emerald-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $payment['gig_date'] }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                                    <p class="text-xs text-gray-500">Booker: {{ $payment['booker_name'] }}</p>
                                                    <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:underline text-xs">{{ $payment['gig_contract'] }}</a>
                                                </td>
                                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300">R$ {{ number_format($payment['booker_commission_value'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm text-green-600 dark:text-green-400">R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 text-right text-sm font-semibold text-yellow-700 dark:text-yellow-300">R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-emerald-100 dark:bg-emerald-900/30">
                                            <td colspan="4" class="px-6 py-2 text-right text-xs font-semibold text-emerald-700 dark:text-emerald-300">Subtotal Futuro:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-emerald-800 dark:text-emerald-200">R$ {{ number_format($period_booker_payments['total_future'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    @endif

                                    @if(count($period_booker_payments['payments'] ?? []) == 0)
                                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma comissão pendente a bookers</td></tr>
                                    @endif
                                </tbody>
                                @if(($period_booker_payments['gig_count'] ?? 0) > 0)
                                    <tfoot class="bg-yellow-100 dark:bg-yellow-900/30">
                                        <tr>
                                            <td colspan="4" class="px-6 py-4 text-right font-bold text-yellow-800 dark:text-yellow-200">TOTAL GERAL:</td>
                                            <td class="px-6 py-4 text-right font-bold text-lg text-yellow-900 dark:text-yellow-100">R$ {{ number_format($period_booker_payments['total_pending'] ?? 0, 2, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/30 flex justify-end">
                            <a href="#dashboard-top" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                Voltar ao topo
                            </a>
                        </div>
                    </x-expandable-section>
                </div>


                {{-- Tabela 4: Despesas de Eventos --}}
                @php
                    $expenseCount = ($period_expenses['past_count'] ?? 0) + ($period_expenses['future_count'] ?? 0);
                @endphp
                <div id="tabela-despesas" class="scroll-mt-4">
                    <x-expandable-section
                        title="Despesas de Eventos"
                        :count="$expenseCount . ' despesas'"
                        color="blue"
                        :expanded="false"
                        :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z\' clip-rule=\'evenodd\' /></svg>'">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-blue-50 dark:bg-blue-900/20">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Data Evento</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Artista / Gig</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-blue-700 dark:text-blue-300 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {{-- GRUPO: EVENTOS PASSADOS (Realizados) --}}
                                    @if(count($period_expenses['past_expenses'] ?? []) > 0)
                                        <tr class="bg-amber-50 dark:bg-amber-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-semibold text-amber-800 dark:text-amber-300">
                                                📅 Eventos Passados (Realizados) — {{ count($period_expenses['past_expenses']) }} despesas
                                            </td>
                                        </tr>
                                        @foreach($period_expenses['past_expenses'] ?? [] as $expense)
                                            <tr class="hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors border-l-4 border-amber-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $expense['gig_date'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $expense['artist_name'] ?? 'N/A' }}</p>
                                                    @if(isset($expense['gig_id']))
                                                        <a href="{{ route('gigs.show', $expense['gig_id']) }}" class="text-primary-600 hover:underline text-xs">{{ $expense['gig_contract'] ?? "Gig #{$expense['gig_id']}" }}</a>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $expense['description'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-3 text-right text-sm font-semibold text-blue-700 dark:text-blue-300">R$ {{ number_format($expense['value_brl'] ?? 0, 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 whitespace-nowrap text-center">
                                                    @if($expense['is_confirmed'] ?? false)
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Confirmada</span>
                                                    @else
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pendente</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-amber-100 dark:bg-amber-900/30">
                                            <td colspan="3" class="px-6 py-2 text-right text-xs font-semibold text-amber-700 dark:text-amber-300">Subtotal Passado:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-amber-800 dark:text-amber-200">R$ {{ number_format($period_expenses['total_past'] ?? 0, 2, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    @endif

                                    {{-- GRUPO: EVENTOS FUTUROS (Projetados) --}}
                                    @if(count($period_expenses['future_expenses'] ?? []) > 0)
                                        <tr class="bg-emerald-50 dark:bg-emerald-900/20">
                                            <td colspan="5" class="px-6 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
                                                🔮 Eventos Futuros (Projetados) — {{ count($period_expenses['future_expenses']) }} despesas
                                            </td>
                                        </tr>
                                        @foreach($period_expenses['future_expenses'] ?? [] as $expense)
                                            <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-colors border-l-4 border-emerald-400">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $expense['gig_date'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-3 text-sm">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $expense['artist_name'] ?? 'N/A' }}</p>
                                                    @if(isset($expense['gig_id']))
                                                        <a href="{{ route('gigs.show', $expense['gig_id']) }}" class="text-primary-600 hover:underline text-xs">{{ $expense['gig_contract'] ?? "Gig #{$expense['gig_id']}" }}</a>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $expense['description'] ?? 'N/A' }}</td>
                                                <td class="px-6 py-3 text-right text-sm font-semibold text-blue-700 dark:text-blue-300">R$ {{ number_format($expense['value_brl'] ?? 0, 2, ',', '.') }}</td>
                                                <td class="px-6 py-3 whitespace-nowrap text-center">
                                                    @if($expense['is_confirmed'] ?? false)
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Confirmada</span>
                                                    @else
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pendente</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-emerald-100 dark:bg-emerald-900/30">
                                            <td colspan="3" class="px-6 py-2 text-right text-xs font-semibold text-emerald-700 dark:text-emerald-300">Subtotal Futuro:</td>
                                            <td class="px-6 py-2 text-right text-sm font-bold text-emerald-800 dark:text-emerald-200">R$ {{ number_format($period_expenses['total_future'] ?? 0, 2, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    @endif

                                    @if($expenseCount == 0)
                                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Nenhuma despesa de evento no período</td></tr>
                                    @endif
                                </tbody>
                                @if($expenseCount > 0)
                                    <tfoot class="bg-blue-100 dark:bg-blue-900/30">
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-right font-bold text-blue-800 dark:text-blue-200">TOTAL GERAL:</td>
                                            <td class="px-6 py-4 text-right font-bold text-lg text-blue-900 dark:text-blue-100">R$ {{ number_format($period_expenses['total_expenses'] ?? 0, 2, ',', '.') }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/30 flex justify-end">
                            <a href="#dashboard-top" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                Voltar ao topo
                            </a>
                        </div>
                    </x-expandable-section>
                </div>

            </section>

        </div>
    </div>

    {{-- Botão flutuante voltar ao topo --}}
    <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
            class="fixed bottom-6 right-6 bg-primary-600 hover:bg-primary-700 text-white p-3 rounded-full shadow-lg transition-all z-50"
            title="Voltar ao topo">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>
</x-app-layout>
