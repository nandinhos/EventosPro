<x-app-layout>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Importar Gigs em Massa</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Faça upload de um arquivo Excel para importar múltiplas gigs de uma vez</p>
        </div>
        <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md flex items-center text-sm">
            <i class="fas fa-arrow-left mr-2"></i> Voltar para Lista
        </a>
    </div>

    {{-- Alertas --}}
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded-lg text-red-700 dark:text-red-300">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    @if(session('import_errors'))
        <div class="mb-4 p-4 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-700 rounded-lg text-yellow-700 dark:text-yellow-300">
            <p class="font-semibold mb-2"><i class="fas fa-exclamation-circle mr-2"></i>Erros na importação:</p>
            <ul class="list-disc list-inside text-sm space-y-1 max-h-40 overflow-y-auto">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulário de Upload --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-upload mr-2 text-primary-500"></i>
                    Upload do Arquivo
                </h3>

                <form action="{{ route('gigs.import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center hover:border-primary-500 dark:hover:border-primary-400 transition-colors">
                        <div class="mb-4">
                            <i class="fas fa-file-excel text-5xl text-green-500 dark:text-green-400"></i>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Arraste e solte seu arquivo aqui, ou clique para selecionar
                        </p>
                        <input 
                            type="file" 
                            name="file" 
                            id="file" 
                            accept=".xlsx,.xls,.csv"
                            class="block w-full text-sm text-gray-900 dark:text-gray-100 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 dark:file:bg-primary-900 file:text-primary-700 dark:file:text-primary-300 hover:file:bg-primary-100 dark:hover:file:bg-primary-800 cursor-pointer"
                            required
                        >
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            Formatos aceitos: .xlsx, .xls, .csv (máx. 5MB)
                        </p>
                    </div>

                    @error('file')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('gigs.import.template') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
                            <i class="fas fa-download mr-2"></i> Baixar Template
                        </a>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm">
                            <i class="fas fa-eye mr-2"></i> Visualizar Dados
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Instruções --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <h3 class="text-md font-semibold text-gray-800 dark:text-white mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    Instruções
                </h3>

                <ol class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-start">
                        <span class="bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full w-6 h-6 flex items-center justify-center mr-3 shrink-0 text-xs font-bold">1</span>
                        <span>Baixe o <strong>template de exemplo</strong> para ver o formato correto</span>
                    </li>
                    <li class="flex items-start">
                        <span class="bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full w-6 h-6 flex items-center justify-center mr-3 shrink-0 text-xs font-bold">2</span>
                        <span>Preencha os dados das gigs na planilha</span>
                    </li>
                    <li class="flex items-start">
                        <span class="bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full w-6 h-6 flex items-center justify-center mr-3 shrink-0 text-xs font-bold">3</span>
                        <span>Faça upload e visualize os dados antes de confirmar</span>
                    </li>
                    <li class="flex items-start">
                        <span class="bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded-full w-6 h-6 flex items-center justify-center mr-3 shrink-0 text-xs font-bold">4</span>
                        <span>Confirme a importação para criar as gigs</span>
                    </li>
                </ol>

                <div class="mt-6 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-sm">
                    <p class="text-yellow-700 dark:text-yellow-400">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Dica:</strong> Os nomes de Artistas, Bookers e Centros de Custo devem corresponder exatamente aos cadastrados no sistema.
                    </p>
                </div>
            </div>

            {{-- Campos Esperados (Colapsável) --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mt-4">
                <details class="group">
                    <summary class="cursor-pointer list-none flex items-center justify-between">
                        <h3 class="text-md font-semibold text-gray-800 dark:text-white">
                            <i class="fas fa-columns mr-2 text-purple-500"></i>
                            Colunas Esperadas
                        </h3>
                        <i class="fas fa-chevron-down text-gray-400 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="mt-4 max-h-64 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="text-left py-1 px-2 text-gray-600 dark:text-gray-300">Coluna</th>
                                    <th class="text-left py-1 px-2 text-gray-600 dark:text-gray-300">Descrição</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($expectedColumns as $column => $description)
                                    <tr>
                                        <td class="py-1 px-2 font-mono text-primary-600 dark:text-primary-400">{{ $column }}</td>
                                        <td class="py-1 px-2 text-gray-600 dark:text-gray-400">{{ $description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>
    </div>
</x-app-layout>
