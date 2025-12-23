<x-app-layout>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Preview da Importação</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Revise os dados antes de confirmar a importação</p>
        </div>
        <a href="{{ route('gigs.import.form') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md flex items-center text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    {{-- Resumo --}}
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 flex items-center">
            <div class="bg-blue-100 dark:bg-blue-900/50 rounded-full p-3 mr-4">
                <i class="fas fa-file-alt text-blue-500 dark:text-blue-400 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total de Linhas</p>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $summary['total'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 flex items-center">
            <div class="bg-green-100 dark:bg-green-900/50 rounded-full p-3 mr-4">
                <i class="fas fa-check-circle text-green-500 dark:text-green-400 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Válidas</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $summary['valid'] }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 flex items-center">
            <div class="bg-red-100 dark:bg-red-900/50 rounded-full p-3 mr-4">
                <i class="fas fa-times-circle text-red-500 dark:text-red-400 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Com Erros</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $summary['invalid'] }}</p>
            </div>
        </div>
    </div>

    {{-- Erros Globais --}}
    @if(count($errors) > 0)
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300">
            <p class="font-semibold mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>Erros encontrados:</p>
            <ul class="list-disc list-inside text-sm space-y-1 max-h-40 overflow-y-auto">
                @foreach($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tabela de Preview --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">Status</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">Linha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booker</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Evento</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local / Evento</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Moeda</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Despesas</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $row)
                        <tr class="{{ !$row['is_valid'] ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                            <td class="px-3 py-2 text-center">
                                @if($row['is_valid'])
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/50">
                                        <i class="fas fa-check text-green-600 dark:text-green-400 text-xs"></i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/50" title="{{ implode(', ', $row['errors']) }}">
                                        <i class="fas fa-times text-red-600 dark:text-red-400 text-xs"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-400 font-mono">
                                {{ $row['row_number'] }}
                            </td>
                            <td class="px-3 py-2 text-gray-800 dark:text-gray-200 font-medium">
                                {{ $row['data']['artista'] ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400">
                                {{ $row['data']['booker'] ?? 'Agência' }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                {{ $row['data']['data_evento'] ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400 max-w-xs truncate" title="{{ $row['data']['local_evento'] ?? '' }}">
                                {{ $row['data']['local_evento'] ?? '-' }}
                            </td>
                            <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 font-mono">
                                {{ $row['data']['valor_contrato'] ?? '0' }}
                            </td>
                            <td class="px-3 py-2 text-center text-gray-600 dark:text-gray-400">
                                {{ strtoupper($row['data']['moeda'] ?? 'BRL') }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @php
                                    $expenseCount = 0;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if (!empty($row['data']["despesa_{$i}_centro_custo"])) {
                                            $expenseCount++;
                                        }
                                    }
                                @endphp
                                @if($expenseCount > 0)
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 text-xs font-medium">
                                        {{ $expenseCount }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                        {{-- Linha de erros se houver --}}
                        @if(!$row['is_valid'])
                            <tr class="bg-red-50 dark:bg-red-900/10">
                                <td colspan="9" class="px-6 py-2 text-red-600 dark:text-red-400 text-xs">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ implode(' | ', $row['errors']) }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Nenhum dado encontrado no arquivo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Ações --}}
    <div class="flex justify-end gap-3">
        <a href="{{ route('gigs.import.form') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md flex items-center text-sm">
            <i class="fas fa-times mr-2"></i> Cancelar
        </a>
        
        @if($summary['valid'] > 0)
            <form action="{{ route('gigs.import') }}" method="POST">
                @csrf
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm" onclick="return confirm('Confirma a importação de {{ $summary['valid'] }} gig(s)?')">
                    <i class="fas fa-check mr-2"></i> Confirmar Importação ({{ $summary['valid'] }} gigs)
                </button>
            </form>
        @else
            <button disabled class="bg-gray-400 text-white px-4 py-2 rounded-md flex items-center text-sm cursor-not-allowed">
                <i class="fas fa-check mr-2"></i> Nenhuma Gig Válida
            </button>
        @endif
    </div>
</x-app-layout>
