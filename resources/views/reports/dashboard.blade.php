<x-app-layout>
    @php
        // Removido o 'use Illuminate\Support\Js;' daqui.

        $initialTab = request()->input('tab', 'overview');
        $tabs = [
            ['id' => 'overview', 'label' => 'Visão Geral'],
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
        <div x-data="{ activeTab: '{{ $initialTab }}' }"
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
</x-app-layout>