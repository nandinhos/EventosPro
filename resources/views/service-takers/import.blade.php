<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Importar Tomadores de Serviço') }}
            </h2>
            <a href="{{ route('service-takers.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white font-medium px-4 py-2 rounded-md flex items-center text-sm shadow-md transition">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>Formato do CSV
                        </h3>
                        <p class="text-sm text-blue-700 dark:text-blue-400 mb-2">
                            O arquivo CSV deve conter as seguintes colunas:
                        </p>
                        <code class="text-xs bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded">
                            organization, document, street, postal_code, city, state, country, company_phone, contact, email, phone
                        </code>
                    </div>

                    <form method="POST" action="{{ route('service-takers.import.process') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Arquivo CSV
                            </label>
                            <input type="file" name="file" id="file" accept=".csv,.txt" required
                                   class="block w-full text-sm text-gray-500 dark:text-gray-400
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-medium
                                          file:bg-primary-50 file:text-primary-700
                                          hover:file:bg-primary-100
                                          dark:file:bg-primary-900 dark:file:text-primary-300">
                            <p class="mt-1 text-xs text-gray-500">Tamanho máximo: 5MB</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-2 rounded-md shadow-md transition">
                                <i class="fas fa-file-import mr-2"></i> Importar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
