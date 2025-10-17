{{-- resources/views/projections/dashboard.blade.php --}}

<x-app-layout>
    {{-- Cabeçalho --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Projeções Financeiras') }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Visualize as previsões de receitas e despesas para os próximos períodos</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- FILTROS --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
                <form action="{{ route('projections.index') }}" method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        {{-- Período Predefinido --}}
                        <div>
                            <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Período Predefinido
                            </label>
                            <select name="period" id="period"
                                    class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    onchange="toggleCustomDates(this.value)">
                                <option value="">Selecione um período</option>
                                <option value="30_days" {{ $period == '30_days' ? 'selected' : '' }}>Próximos 30 dias</option>
                                <option value="60_days" {{ $period == '60_days' ? 'selected' : '' }}>Próximos 60 dias</option>
                                <option value="90_days" {{ $period == '90_days' ? 'selected' : '' }}>Próximos 90 dias</option>
                                <option value="next_semester" {{ $period == 'next_semester' ? 'selected' : '' }}>Próximo Semestre</option>
                                <option value="next_year" {{ $period == 'next_year' ? 'selected' : '' }}>Próximo Ano</option>
                                <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>Personalizado</option>
                            </select>
                        </div>

                        {{-- Data Inicial --}}
                        <div id="start_date_wrapper">
                            <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Data Inicial
                            </label>
                            <input type="date" name="start_date" id="start_date"
                                   value="{{ request('start_date', '') }}"
                                   class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        {{-- Data Final --}}
                        <div id="end_date_wrapper">
                            <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Data Final
                            </label>
                            <input type="date" name="end_date" id="end_date"
                                   value="{{ request('end_date', '') }}"
                                   class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>

                        {{-- Botão Filtrar --}}
                        <div class="flex items-end">
                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md shadow-sm transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                Filtrar
                            </button>
                        </div>
                    </div>

                    {{-- Botão Limpar --}}
                    <div class="flex justify-end">
                        <a href="{{ route('projections.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Limpar Filtros
                        </a>
                    </div>

                    {{-- Badge de Período Ativo (Destacado) --}}
                    @if(request('start_date') || request('end_date') || $period)
                        <div class="mt-6 flex items-center justify-center">
                            <div class="inline-flex items-center px-6 py-4 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 shadow-lg border-2 border-primary-400 dark:border-primary-700">
                                <svg class="w-7 h-7 mr-3 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium text-primary-100 uppercase tracking-wide">Período Selecionado</span>
                                    <span class="text-xl font-bold text-white">
                                        @if(request('start_date') && request('end_date'))
                                            {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }} até {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                                        @elseif(request('start_date'))
                                            A partir de {{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }}
                                        @elseif(request('end_date'))
                                            Até {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }}
                                        @elseif($period)
                                            @php
                                                $periodLabels = [
                                                    '30_days' => 'Próximos 30 dias',
                                                    '60_days' => 'Próximos 60 dias',
                                                    '90_days' => 'Próximos 90 dias',
                                                    'next_semester' => 'Próximo Semestre',
                                                    'next_year' => 'Próximo Ano',
                                                ];
                                            @endphp
                                            {{ $periodLabels[$period] ?? $period }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </form>
            </div>

    @push('scripts')
    <script>
        function toggleCustomDates(period) {
            const startDateWrapper = document.getElementById('start_date_wrapper');
            const endDateWrapper = document.getElementById('end_date_wrapper');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (period === 'custom') {
                startDateWrapper.style.display = 'block';
                endDateWrapper.style.display = 'block';
                startDateInput.required = true;
                endDateInput.required = true;
            } else if (period === '') {
                startDateWrapper.style.display = 'block';
                endDateWrapper.style.display = 'block';
                startDateInput.required = false;
                endDateInput.required = false;
            } else {
                startDateWrapper.style.display = 'none';
                endDateWrapper.style.display = 'none';
                startDateInput.required = false;
                endDateInput.required = false;
                startDateInput.value = '';
                endDateInput.value = '';
            }
        }

        // Inicializa o estado dos campos ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            const periodSelect = document.getElementById('period');
            toggleCustomDates(periodSelect.value);
        });
    </script>
    @endpush

            {{-- CARDS PRINCIPAIS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Card 1: Contas a Receber --}}
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-100 uppercase tracking-wide">Contas a Receber</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($accounts_receivable, 2, ',', '.') }}
                            </p>
                            <p class="text-sm text-green-100 mt-1">
                                Valores a receber de clientes
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Contas a Pagar Artistas --}}
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-red-100 uppercase tracking-wide">Pagar Artistas</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($accounts_payable_artists, 2, ',', '.') }}
                            </p>
                            <p class="text-sm text-red-100 mt-1">
                                Cachês pendentes
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Contas a Pagar Bookers --}}
                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-yellow-100 uppercase tracking-wide">Pagar Bookers</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($accounts_payable_bookers, 2, ',', '.') }}
                            </p>
                            <p class="text-sm text-yellow-100 mt-1">
                                Comissões pendentes
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Despesas Previstas --}}
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-orange-100 uppercase tracking-wide">Despesas Previstas</p>
                            <p class="text-3xl font-bold mt-2">
                                R$ {{ number_format($accounts_payable_expenses, 2, ',', '.') }}
                            </p>
                            <p class="text-sm text-orange-100 mt-1">
                                Custos operacionais
                            </p>
                        </div>
                        <div class="bg-white/20 rounded-full p-3">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card de Fluxo de Caixa Projetado (Destaque) --}}
            <div class="bg-gradient-to-br {{ $projected_cash_flow >= 0 ? 'from-blue-500 to-blue-600' : 'from-red-500 to-red-600' }} rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium {{ $projected_cash_flow >= 0 ? 'text-blue-100' : 'text-red-100' }} uppercase tracking-wide">
                            Fluxo de Caixa Projetado
                        </p>
                        <p class="text-4xl font-bold mt-2">
                            R$ {{ number_format($projected_cash_flow, 2, ',', '.') }}
                        </p>
                        <p class="text-sm {{ $projected_cash_flow >= 0 ? 'text-blue-100' : 'text-red-100' }} mt-1">
                            @if($projected_cash_flow >= 0)
                                Saldo positivo projetado
                            @else
                                Atenção: Saldo negativo projetado
                            @endif
                        </p>
                    </div>
                    <div class="bg-white/20 rounded-full p-4">
                        <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                            @if($projected_cash_flow >= 0)
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            @endif
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Tabela de Contas a Receber (Clientes) --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Contas a Receber (Clientes)
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Valores pendentes de recebimento
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição Parcela</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vencimento</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor (BRL)</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($upcoming_client_payments as $payment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $payment->inferred_status == 'vencido' ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('gigs.show', $payment->gig) }}" class="text-primary-600 dark:text-primary-400 hover:underline font-medium">
                                            {{ $payment->gig->contract_number ? 'Contrato #' . $payment->gig->contract_number : 'Gig #'.$payment->gig_id }}
                                        </a>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ optional($payment->gig->artist)->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $payment->description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $payment->due_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <x-status-badge :status="$payment->inferred_status" type="payment" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhuma conta a receber encontrada para o período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabela de Próximos Pagamentos a Artistas --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Próximos Pagamentos a Artistas
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Cachês pendentes de pagamento
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig (Contrato)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data do Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artista</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor NF (BRL)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($upcoming_artist_payments as $gig)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('gigs.show', $gig) }}" class="text-primary-600 dark:text-primary-400 hover:underline font-medium">
                                            {{ $gig->contract_number ?? 'Gig #'.$gig->id }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $gig->gig_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $gig->artist->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                        R$ {{ number_format($gig->calculated_artist_invoice_value_brl, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhum pagamento a artistas previsto para o período selecionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabela de Próximas Comissões (Bookers) --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Próximas Comissões a Bookers
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Comissões pendentes de pagamento
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig (Contrato)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data do Evento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Booker</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comissão (BRL)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($upcoming_booker_payments as $gig)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('gigs.show', $gig) }}" class="text-primary-600 dark:text-primary-400 hover:underline font-medium">
                                            {{ $gig->contract_number ?? 'Gig #'.$gig->id }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $gig->gig_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $gig->booker->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                        R$ {{ number_format($gig->calculated_booker_commission_brl, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhuma comissão a bookers prevista para o período selecionado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tabela de Despesas Previstas por Centro de Custo --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Despesas Previstas por Centro de Custo
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Custos operacionais projetados
                    </p>
                </div>
                <div class="overflow-x-auto">
                    @forelse ($projected_expenses_by_cost_center as $cost_center_group)
                        <div class="border-b border-gray-200 dark:border-gray-700 last:border-0">
                            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $cost_center_group['cost_center_name'] }}</h4>
                                <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                    Total: R$ {{ number_format($cost_center_group['total_brl'], 2, ',', '.') }}
                                </span>
                            </div>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gig / Artista</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição Despesa</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data Despesa</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor (BRL)</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Moeda</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($cost_center_group['expenses'] as $expense)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ $expense['currency'] != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('gigs.show', $expense['gig_id']) }}" class="text-primary-600 dark:text-primary-400 hover:underline font-medium">
                                                    {{ $expense['gig_contract_number'] }}
                                                </a>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $expense['gig_artist_name'] }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $expense['description'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                {{ $expense['expense_date_formatted'] }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                                R$ {{ number_format($expense['value_brl'], 2, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                    {{ $expense['currency'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Nenhuma despesa prevista para o período selecionado.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</x-app-layout>