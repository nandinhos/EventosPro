{{-- resources/views/projections/dashboard.blade.php --}}

<x-app-layout>
    {{-- Cabeçalho --}}
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                    {{ __('Projeções Financeiras') }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Análise gerencial e projeções de fluxo de caixa</p>
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

    <div class="py-6" x-data="{ activeTab: 'global' }">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- TABS NAVIGATION --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button
                            @click="activeTab = 'global'"
                            :class="activeTab === 'global' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors"
                        >
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Métricas Gerais
                        </button>
                        <button
                            @click="activeTab = 'period'"
                            :class="activeTab === 'period' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors"
                        >
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Por Período
                        </button>
                    </nav>
                </div>
            </div>

            {{-- ABA: MÉTRICAS GERAIS --}}
            <div x-show="activeTab === 'global'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                {{-- MÉTRICAS GERENCIAIS GLOBAIS --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Índice de Liquidez --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 {{ $global_metrics['liquidity_index'] >= 1.2 ? 'border-green-500' : ($global_metrics['liquidity_index'] >= 1 ? 'border-yellow-500' : 'border-red-500') }}">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Índice de Liquidez Global</p>
                                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                                    {{ number_format($global_metrics['liquidity_index'], 2, ',', '.') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Recebível / Total a Pagar
                                </p>
                            </div>
                            <div class="ml-4">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $global_metrics['liquidity_index'] >= 1.2 ? 'bg-green-100 dark:bg-green-900' : ($global_metrics['liquidity_index'] >= 1 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                                    <svg class="w-8 h-8 {{ $global_metrics['liquidity_index'] >= 1.2 ? 'text-green-600 dark:text-green-400' : ($global_metrics['liquidity_index'] >= 1 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Margem Operacional --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 {{ $global_metrics['operational_margin'] >= 20 ? 'border-green-500' : ($global_metrics['operational_margin'] >= 10 ? 'border-yellow-500' : 'border-red-500') }}">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Margem Operacional Global</p>
                                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                                    {{ number_format($global_metrics['operational_margin'], 1, ',', '.') }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Fluxo / Recebível
                                </p>
                            </div>
                            <div class="ml-4">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $global_metrics['operational_margin'] >= 20 ? 'bg-green-100 dark:bg-green-900' : ($global_metrics['operational_margin'] >= 10 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                                    <svg class="w-8 h-8 {{ $global_metrics['operational_margin'] >= 20 ? 'text-green-600 dark:text-green-400' : ($global_metrics['operational_margin'] >= 10 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Grau de Comprometimento --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-l-4 {{ $global_metrics['commitment_rate'] <= 70 ? 'border-green-500' : ($global_metrics['commitment_rate'] <= 85 ? 'border-yellow-500' : 'border-red-500') }}">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Comprometimento Global</p>
                                <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                                    {{ number_format($global_metrics['commitment_rate'], 1, ',', '.') }}%
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Total a Pagar / Recebível
                                </p>
                            </div>
                            <div class="ml-4">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $global_metrics['commitment_rate'] <= 70 ? 'bg-green-100 dark:bg-green-900' : ($global_metrics['commitment_rate'] <= 85 ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900') }}">
                                    <svg class="w-8 h-8 {{ $global_metrics['commitment_rate'] <= 70 ? 'text-green-600 dark:text-green-400' : ($global_metrics['commitment_rate'] <= 85 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CARDS PRINCIPAIS DE VALORES GLOBAIS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
                    {{-- Card 1: Contas a Receber --}}
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white transform transition hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-100 uppercase tracking-wide">Total a Receber</p>
                                <p class="text-3xl font-bold mt-2">
                                    R$ {{ number_format($global_metrics['total_receivable'], 2, ',', '.') }}
                                </p>
                                <p class="text-sm text-green-100 mt-1">
                                    Todas as contas
                                </p>
                                @if($global_metrics['overdue_analysis']['overdue_count'] > 0)
                                    <div class="mt-2 bg-red-500/30 rounded px-2 py-1 text-xs">
                                        <span class="font-semibold">{{ $global_metrics['overdue_analysis']['overdue_count'] }} vencidas</span>
                                    </div>
                                @endif
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
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white transform transition hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-100 uppercase tracking-wide">Total Pagar Artistas</p>
                                <p class="text-3xl font-bold mt-2">
                                    R$ {{ number_format($global_metrics['total_payable_artists'], 2, ',', '.') }}
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
                    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white transform transition hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-100 uppercase tracking-wide">Total Pagar Bookers</p>
                                <p class="text-3xl font-bold mt-2">
                                    R$ {{ number_format($global_metrics['total_payable_bookers'], 2, ',', '.') }}
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
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white transform transition hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-100 uppercase tracking-wide">Total Despesas</p>
                                <p class="text-3xl font-bold mt-2">
                                    R$ {{ number_format($global_metrics['total_payable_expenses'], 2, ',', '.') }}
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

                {{-- Card de Fluxo de Caixa Global (Destaque) --}}
                <div class="bg-gradient-to-br {{ $global_metrics['total_cash_flow'] >= 0 ? 'from-blue-500 to-blue-600' : 'from-red-500 to-red-600' }} rounded-lg shadow-lg p-6 text-white mt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium {{ $global_metrics['total_cash_flow'] >= 0 ? 'text-blue-100' : 'text-red-100' }} uppercase tracking-wide">
                                Fluxo de Caixa Global
                            </p>
                            <p class="text-4xl font-bold mt-2">
                                R$ {{ number_format($global_metrics['total_cash_flow'], 2, ',', '.') }}
                            </p>
                            <p class="text-sm {{ $global_metrics['total_cash_flow'] >= 0 ? 'text-blue-100' : 'text-red-100' }} mt-1">
                                @if($global_metrics['total_cash_flow'] >= 0)
                                    Saldo positivo
                                @else
                                    Atenção: Saldo negativo
                                @endif
                            </p>
                        </div>
                        <div class="ml-4">
                            <div class="bg-white/20 rounded-full p-4">
                                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                                    @if($global_metrics['total_cash_flow'] >= 0)
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    @else
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    @endif
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ANÁLISE DE INADIMPLÊNCIA GLOBAL --}}
                @if($global_metrics['overdue_analysis']['overdue_count'] > 0)
                    <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg shadow-lg p-6 mt-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">
                                    Alerta de Inadimplência
                                </h3>
                                <p class="mt-2 text-sm text-red-700 dark:text-red-300">
                                    Existem <strong>{{ $global_metrics['overdue_analysis']['overdue_count'] }} parcelas vencidas</strong> totalizando
                                    <strong>R$ {{ number_format($global_metrics['overdue_analysis']['total_overdue'], 2, ',', '.') }}</strong>
                                </p>
                                <div class="mt-4 grid grid-cols-4 gap-3">
                                    @foreach($global_metrics['overdue_analysis']['overdue_by_period'] as $period => $value)
                                        @if($value > 0)
                                            <div class="bg-white dark:bg-gray-800 rounded px-3 py-2">
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $period }} dias</p>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    R$ {{ number_format($value, 0, ',', '.') }}
                                                </p>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ABA: POR PERÍODO --}}
            <div x-show="activeTab === 'period'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak>

                {{-- FILTROS --}}
                <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 mb-6">
                    <form action="{{ route('projections.index') }}" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {{-- Data Inicial --}}
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Data Inicial
                                </label>
                                <input type="date" name="start_date" id="start_date"
                                       value="{{ $start_date }}"
                                       class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>

                            {{-- Data Final --}}
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Data Final
                                </label>
                                <input type="date" name="end_date" id="end_date"
                                       value="{{ $end_date }}"
                                       class="block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>

                            {{-- Botão Filtrar --}}
                            <div class="flex items-end">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-md shadow-sm transition">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Filtrar Período
                                </button>
                            </div>

                            {{-- Botão Ver Tudo (Global) --}}
                            <div class="flex items-end">
                                <button type="submit" name="show_global" value="1" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm transition">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Ver Tudo
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
                    </form>
                </div>

                {{-- CONTEÚDO POR PERÍODO --}}
                @if($period_metrics && $period_listings)
                    {{-- Badge de Período Ativo --}}
                    <div class="mb-6 flex items-center justify-center">
                        <div class="inline-flex items-center px-6 py-4 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 shadow-lg border-2 border-primary-400 dark:border-primary-700">
                            <svg class="w-7 h-7 mr-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-primary-100 uppercase tracking-wide">Período Analisado</span>
                                <span class="text-xl font-bold text-white">
                                    @if($show_global)
                                        Global (Todos os Registros)
                                    @else
                                        {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Cards de Resumo --}}
                    @include('projections.partials.period-summary-cards', [
                        'summary' => $period_listings,
                    ])

                    {{-- Métricas Gerenciais (KPIs) --}}
                    @include('projections.partials.period-metrics', [
                        'executive_summary' => $period_metrics['executive_summary'],
                        'future_events_analysis' => $period_metrics['future_events_analysis'],
                        'comparative_analysis' => $period_metrics['comparative_analysis'],
                    ])

                    {{-- Tabelas Agrupadas com Subtotais --}}
                    @include('projections.partials.period-tables-grouped', [
                        'summary' => $period_listings,
                    ])
                @else
                    {{-- Mensagem: Selecione um Período --}}
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Selecione um Período</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Escolha as datas inicial e final para visualizar as projeções financeiras do período,<br>
                            ou clique em "Ver Tudo" para listar todos os registros.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @endpush

</x-app-layout>
