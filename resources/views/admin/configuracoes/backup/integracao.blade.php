<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Integração Sistema Legado Coral
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Gerenciador de Integração</h1>
                    <p class="mt-2 text-sm text-gray-600">Importe e valide dados do sistema Coral ou extraia dados para análise</p>
                </div>
                <div class="flex flex-col items-end space-y-2">
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.backup.integracao.export-mock') }}" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Extrair Tudo ({{ ($totals['contracts'] ?? 0) + ($totals['receivables'] ?? 0) + ($totals['payables'] ?? 0) }} itens)
                        </a>
                        <a href="{{ route('admin.backup.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                            Voltar
                        </a>
                    </div>
                    @if(isset($totals))
                    <div class="flex gap-4 text-xs font-mono text-gray-500">
                        <span>Contratos: <strong>{{ $totals['contracts'] }}</strong></span>
                        <span>Recebíveis: <strong>{{ $totals['receivables'] }}</strong></span>
                        <span>Pagamentos: <strong>{{ $totals['payables'] }}</strong></span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Mensagens --}}
            @if(session('success'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-400 p-4">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            @if(session('import_errors'))
                <div class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <h3 class="text-sm font-medium text-yellow-800">Erros durante a importação:</h3>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Formulário de Upload para Preview --}}
            @if(!$preview)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Carregar Arquivo para Validação</h3>
                    <form action="{{ route('admin.backup.integracao.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Dado</label>
                                <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="contracts">Contratos (Contracts)</option>
                                    <option value="receivables">Recebíveis (Receivables)</option>
                                    <option value="payables">Pagamentos (Payables)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo JSON</label>
                                <input type="file" name="file" accept=".json" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Analisar Arquivo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Tabela de Preview --}}
            @if($preview)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-2 border-indigo-500">
                <div class="p-6 border-b border-gray-200 bg-indigo-50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-indigo-900 uppercase">Preview de Importação: {{ $type }}</h3>
                        <p class="text-sm text-indigo-700">Verifique as inconsistências antes de confirmar o processamento.</p>
                    </div>
                    <div class="flex space-x-2">
                        <form action="{{ route('admin.backup.integracao.import') }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            <input type="hidden" name="data" value="{{ $rawData }}">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                Confirmar Importação
                            </button>
                        </form>
                        <a href="{{ route('admin.backup.integracao') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition ease-in-out duration-150">
                            Cancelar
                        </a>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref/Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalhes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vínculo Detectado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensagem</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($preview as $row)
                                <tr class="{{ $row['status'] === 'error' ? 'bg-red-50' : ($row['status'] === 'warning' ? 'bg-yellow-50' : '') }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        @if($type === 'contracts') {{ $row['numero_contrato'] }}
                                        @elseif($type === 'receivables') {{ $row['contrato_ref'] }} ({{ $row['parcela'] }})
                                        @else {{ $row['ctr_ref'] }} @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        @if($type === 'contracts') {{ $row['artista'] }} ({{ $row['data']['data_evento'] }})
                                        @elseif($type === 'receivables') Valor: {{ number_format($row['valor'], 2, ',', '.') }}
                                        @else {{ $row['descricao'] }} ({{ number_format($row['valor'], 2, ',', '.') }}) @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold {{ ($row['contract_found'] ?? null) || ($row['artist_found'] ?? null) ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ $row['artist_found'] ?? $row['contract_found'] ?? 'Não Encontrado' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $row['status'] === 'valid' ? 'bg-green-100 text-green-800' : ($row['status'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ strtoupper($row['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm {{ $row['status'] === 'error' ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                        {{ $row['message'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Documentação Rápida --}}
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Como funciona a integração?</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Contratos:</strong> O sistema busca o Artista pelo nome. Se não existir, a importação da linha falha.</li>
                                <li><strong>Recebíveis:</strong> Devem referenciar um <code>numero_contrato</code> já importado anteriormente.</li>
                                <li><strong>Pagamentos:</strong> Se o contrato não for encontrado, o custo é importado como "Geral".</li>
                                <li>Todos os dados importados são marcados como <strong>Legacy</strong> e vinculados à entidade <strong>Coral</strong>.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
