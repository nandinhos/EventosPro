@extends('layouts.app')

@section('title', 'Despesas Reembolsáveis')

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-receipt mr-2 text-primary-500"></i>
                Despesas Reembolsáveis
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Rastreie comprovantes e status de reembolso das despesas de artistas
            </p>
        </div>

        {{-- Cards de Métricas por Estágio --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Aguardando Comprovante --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'aguardando_comprovante']) }}"
               class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-lg transition-shadow border-l-4 border-gray-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aguardando</p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-300">{{ $metrics['aguardando_comprovante']['count'] }}</p>
                        <p class="text-xs text-gray-500">R$ {{ number_format($metrics['aguardando_comprovante']['value'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-700">
                        <i class="fas fa-clock text-gray-500 text-xl"></i>
                    </div>
                </div>
            </a>

            {{-- Comprovante Recebido --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'comprovante_recebido']) }}"
               class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-lg transition-shadow border-l-4 border-yellow-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-yellow-600 dark:text-yellow-400 uppercase">Recebido</p>
                        <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $metrics['comprovante_recebido']['count'] }}</p>
                        <p class="text-xs text-gray-500">R$ {{ number_format($metrics['comprovante_recebido']['value'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/40">
                        <i class="fas fa-file-alt text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </a>

            {{-- Conferido --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'conferido']) }}"
               class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-lg transition-shadow border-l-4 border-blue-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">Conferido</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $metrics['conferido']['count'] }}</p>
                        <p class="text-xs text-gray-500">R$ {{ number_format($metrics['conferido']['value'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/40">
                        <i class="fas fa-check-double text-blue-500 text-xl"></i>
                    </div>
                </div>
            </a>

            {{-- Reembolsado --}}
            <a href="{{ route('expenses.reimbursements.index', ['stage' => 'reembolsado']) }}"
               class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-lg transition-shadow border-l-4 border-green-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-green-600 dark:text-green-400 uppercase">Reembolsado</p>
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $metrics['reembolsado']['count'] }}</p>
                        <p class="text-xs text-gray-500">R$ {{ number_format($metrics['reembolsado']['value'], 2, ',', '.') }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/40">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                </div>
            </a>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <form method="GET" action="{{ route('expenses.reimbursements.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Busca --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Descrição, evento, centro..."
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                {{-- Estágio --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Estágio</label>
                    <select name="stage" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="all">Todos</option>
                        @foreach(\App\Models\GigCost::REIMBURSEMENT_STAGES as $key => $label)
                            <option value="{{ $key }}" @selected(request('stage') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Artista --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Artista</label>
                    <select name="artist_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Todos</option>
                        @foreach($artists as $id => $name)
                            <option value="{{ $id }}" @selected(request('artist_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Botões --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 text-sm font-medium">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <a href="{{ route('expenses.reimbursements.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabela de Despesas --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estágio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Artista</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Evento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Centro de Custo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Descrição</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Valor</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <x-reimbursement-badge :cost="$expense" size="sm" />
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $expense->expense_date?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $expense->gig?->artist?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[200px] truncate" title="{{ $expense->gig?->location_event_details }}">
                                    <a href="{{ route('gigs.show', $expense->gig_id) }}" class="hover:text-primary-600">
                                        {{ Str::limit($expense->gig?->location_event_details, 30) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $expense->costCenter?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[150px] truncate" title="{{ $expense->description }}">
                                    {{ $expense->description ?? '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                    R$ {{ number_format($expense->value, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    @include('expenses.reimbursements._actions', ['expense' => $expense])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>Nenhuma despesa reembolsável encontrada.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            @if($expenses->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    {{ $expenses->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
