{{-- Projeções Financeiras por Período --}}
{{-- 4 Cards: Contas a Receber, Contas a Pagar (Artistas), Contas a Pagar (Bookers), Despesas Previstas --}}

<div class="space-y-8">

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
            <div>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Aplicar Filtro
                </button>
            </div>
            <div>
                <a href="{{ route('projections.index', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition">
                    Mês Atual
                </a>
            </div>
        </form>
    </section>

    {{-- 4 CARDS DE MÉTRICAS FINANCEIRAS --}}
    <section>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Projeções Financeiras do Período
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Card 1: Contas a Receber --}}
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-100 uppercase tracking-wide">Contas a Receber</p>
                        <p class="text-3xl font-bold mt-2">
                            R$ {{ number_format($period_accounts_receivable['total_receivable'] ?? 0, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-green-100 mt-1">
                            {{ $period_accounts_receivable['payment_count'] ?? 0 }} pagamentos pendentes
                        </p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Card 2: Contas a Pagar (Artistas) --}}
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-red-100 uppercase tracking-wide">Pagar Artistas</p>
                        <p class="text-3xl font-bold mt-2">
                            R$ {{ number_format($period_artist_payments['total_pending'] ?? 0, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-red-100 mt-1">
                            {{ $period_artist_payments['gig_count'] ?? 0 }} eventos pendentes
                        </p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Card 3: Contas a Pagar (Bookers) --}}
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-yellow-100 uppercase tracking-wide">Pagar Bookers</p>
                        <p class="text-3xl font-bold mt-2">
                            R$ {{ number_format($period_booker_payments['total_pending'] ?? 0, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-yellow-100 mt-1">
                            {{ $period_booker_payments['gig_count'] ?? 0 }} comissões pendentes
                        </p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Card 4: Despesas de Eventos --}}
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-orange-100 uppercase tracking-wide">Despesas de Eventos</p>
                        <p class="text-3xl font-bold mt-2">
                            R$ {{ number_format($period_expenses['total_expenses'] ?? 0, 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-orange-100 mt-1">
                            {{ ($period_expenses['pending_count'] ?? 0) + ($period_expenses['confirmed_count'] ?? 0) }} despesas
                        </p>
                    </div>
                    <div class="bg-white/20 rounded-full p-3">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- RESUMO CONSOLIDADO --}}
    <section>
        @php
            $totalReceivable = $period_accounts_receivable['total_receivable'] ?? 0;
            $totalPayable = ($period_artist_payments['total_pending'] ?? 0)
                          + ($period_booker_payments['total_pending'] ?? 0)
                          + ($period_expenses['total_expenses'] ?? 0);
            $netBalance = $totalReceivable - $totalPayable;
        @endphp

        <div class="bg-gradient-to-br {{ $netBalance >= 0 ? 'from-blue-500 to-blue-600' : 'from-red-500 to-red-600' }} rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium {{ $netBalance >= 0 ? 'text-blue-100' : 'text-red-100' }} uppercase tracking-wide">
                        Saldo Projetado do Período
                    </p>
                    <p class="text-4xl font-bold mt-2">
                        R$ {{ number_format($netBalance, 2, ',', '.') }}
                    </p>
                    <p class="text-sm {{ $netBalance >= 0 ? 'text-blue-100' : 'text-red-100' }} mt-1">
                        @if($netBalance >= 0)
                            Saldo positivo - situação financeira saudável
                        @else
                            Atenção: Saldo negativo no período
                        @endif
                    </p>
                </div>
                <div class="ml-4">
                    <div class="bg-white/20 rounded-full p-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            @if($netBalance >= 0)
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            @endif
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- TABELAS DETALHADAS RETRÁTEIS --}}
    <section>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Detalhamento por Categoria
        </h3>

        {{-- Tabela 1: Contas a Receber --}}
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
                        @forelse($period_accounts_receivable['payments'] ?? [] as $payment)
                            <tr class="hover:bg-green-50/50 dark:hover:bg-green-900/10 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    {{ $payment['due_date'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                    @if(isset($payment['gig_id']))
                                        <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-xs">
                                            {{ $payment['gig_contract'] ?? "Gig #{$payment['gig_id']}" }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-green-700 dark:text-green-300">
                                    R$ {{ number_format($payment['due_value_brl'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php $daysUntil = $payment['days_until_due'] ?? 0; @endphp
                                    @if($daysUntil < 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Vencido {{ abs($daysUntil) }}d
                                        </span>
                                    @elseif($daysUntil <= 7)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            {{ $daysUntil }}d restantes
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ $daysUntil }}d restantes
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum pagamento pendente no período
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(($period_accounts_receivable['payment_count'] ?? 0) > 0)
                        <tfoot class="bg-green-50 dark:bg-green-900/20">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right text-sm font-semibold text-green-700 dark:text-green-300">Total:</td>
                                <td class="px-6 py-4 text-right text-base font-bold text-green-800 dark:text-green-200">
                                    R$ {{ number_format($period_accounts_receivable['total_receivable'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-expandable-section>

        {{-- Tabela 2: Contas a Pagar (Artistas) --}}
        <x-expandable-section
            title="Contas a Pagar - Artistas"
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
                        @forelse($period_artist_payments['payments'] ?? [] as $payment)
                            <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    {{ $payment['gig_date'] }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                    <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-xs">
                                        {{ $payment['gig_contract'] }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">📍 {{ Str::limit($payment['location'] ?? 'N/A', 30) }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                                    R$ {{ number_format($payment['artist_payout_total'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400">
                                    R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-red-700 dark:text-red-300">
                                    R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhum pagamento pendente a artistas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(($period_artist_payments['gig_count'] ?? 0) > 0)
                        <tfoot class="bg-red-50 dark:bg-red-900/20">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-semibold text-red-700 dark:text-red-300">Total Pendente:</td>
                                <td class="px-6 py-4 text-right text-base font-bold text-red-800 dark:text-red-200">
                                    R$ {{ number_format($period_artist_payments['total_pending'] ?? 0, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-expandable-section>

        {{-- Tabela 3: Contas a Pagar (Bookers) --}}
        <x-expandable-section
            title="Contas a Pagar - Bookers"
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
                        @forelse($period_booker_payments['payments'] ?? [] as $payment)
                            <tr class="hover:bg-yellow-50/50 dark:hover:bg-yellow-900/10 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    {{ $payment['gig_date'] }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $payment['artist_name'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Booker: {{ $payment['booker_name'] }}</p>
                                    <a href="{{ route('gigs.show', $payment['gig_id']) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-xs">
                                        {{ $payment['gig_contract'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 dark:text-gray-300">
                                    R$ {{ number_format($payment['booker_commission_value'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400">
                                    R$ {{ number_format($payment['amount_paid'], 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-yellow-700 dark:text-yellow-300">
                                    R$ {{ number_format($payment['amount_pending'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma comissão pendente a bookers
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(($period_booker_payments['gig_count'] ?? 0) > 0)
                        <tfoot class="bg-yellow-50 dark:bg-yellow-900/20">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-semibold text-yellow-700 dark:text-yellow-300">Total Pendente:</td>
                                <td class="px-6 py-4 text-right text-base font-bold text-yellow-800 dark:text-yellow-200">
                                    R$ {{ number_format($period_booker_payments['total_pending'] ?? 0, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-expandable-section>

        {{-- Tabela 4: Despesas de Eventos --}}
        @php
            $allExpenses = collect($period_expenses['pending'] ?? [])->merge($period_expenses['confirmed'] ?? [])->sortBy('gig_date_raw');
            $expenseCount = ($period_expenses['pending_count'] ?? 0) + ($period_expenses['confirmed_count'] ?? 0);
        @endphp
        <x-expandable-section
            title="Despesas de Eventos"
            :count="$expenseCount . ' despesas'"
            color="orange"
            :expanded="false"
            :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'><path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm3 5a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm0 3a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z\' clip-rule=\'evenodd\' /></svg>'">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-orange-50 dark:bg-orange-900/20">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wider">Data Evento</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wider">Artista / Gig</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($allExpenses as $expense)
                            <tr class="hover:bg-orange-50/50 dark:hover:bg-orange-900/10 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                    {{ $expense['gig_date'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $expense['artist_name'] ?? 'N/A' }}</p>
                                    @if(isset($expense['gig_id']))
                                        <a href="{{ route('gigs.show', $expense['gig_id']) }}" class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-xs">
                                            {{ $expense['gig_contract'] ?? "Gig #{$expense['gig_id']}" }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $expense['description'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-orange-700 dark:text-orange-300">
                                    R$ {{ number_format($expense['value_brl'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($expense['is_confirmed'] ?? false)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Confirmada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            Pendente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Nenhuma despesa de evento cadastrada
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($expenseCount > 0)
                        <tfoot class="bg-orange-50 dark:bg-orange-900/20">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-sm font-semibold text-orange-700 dark:text-orange-300">
                                    Total:
                                </td>
                                <td class="px-6 py-4 text-right text-base font-bold text-orange-800 dark:text-orange-200">
                                    R$ {{ number_format($period_expenses['total_expenses'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-expandable-section>
    </section>

</div>
