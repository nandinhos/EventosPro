<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Relatório de Vencimentos (Pendentes)
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Acompanhe os pagamentos a vencer e vencidos.</p>
    </x-slot>
    
    <div class="py-8 max-w-full mx-auto sm:px-6 lg:px-8">
        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-md mb-6">
            <form id="dueDateFiltersForm" method="GET" action="{{ route('reports.due-dates') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                    {{-- Período de Vencimento --}}
                    <div class="lg:col-span-2">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento (De)</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <div class="lg:col-span-2">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento (Até)</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" 
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                    </div>

                    {{-- Filtros Adicionais --}}
                    <div class="lg:col-span-2">
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" id="status" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Todos (Pendentes)</option>
                            <option value="a_vencer" {{ request('status') === 'a_vencer' ? 'selected' : '' }}>A Vencer</option>
                            <option value="vencido" {{ request('status') === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                        </select>
                    </div>
                    
                    <div class="lg:col-span-2">
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moeda</label>
                        <select name="currency" id="currency" 
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Todas</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency }}" {{ request('currency') === $currency ? 'selected' : '' }}>{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ações --}}
                    <div class="lg:col-span-4 flex flex-col sm:flex-row gap-2 pt-1">
                        <button type="submit" 
                                class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="fas fa-filter mr-2"></i> Filtrar
                        </button>
                        
                        <a href="{{ route('reports.due-dates') }}" 
                           class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-undo-alt mr-2"></i> Limpar
                        </a>
                        
                        <button type="button" 
                                onclick="exportToPdf(this)" 
                                class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-medium text-sm text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            <span class="button-content flex items-center">
                                <i class="fas fa-file-pdf mr-2"></i> PDF
                            </span>
                            <span class="loading-spinner hidden ml-2">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Gerando...
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Cards de Resumo --}}
        @php
            $statuses = [
                'vencido' => ['title' => 'Vencidos', 'colorClasses' => 'border-red-500', 'iconColor' => 'text-red-500', 'icon' => 'fa-exclamation-circle'],
                'a_vencer' => ['title' => 'A Vencer', 'colorClasses' => 'border-yellow-500', 'iconColor' => 'text-yellow-500', 'icon' => 'fa-clock'],
            ];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @foreach($statuses as $statusKey => $statusInfo)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 {{ $statusInfo['colorClasses'] }} p-5">
                    <div class="flex items-center">
                        <div class="{{ $statusInfo['iconColor'] }} text-3xl">
                            <i class="fas {{ $statusInfo['icon'] }}"></i>
                        </div>
                        <div class="ml-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ $statusInfo['title'] }}</dt>
                            <dd class="mt-1 flex items-baseline">
                                <span class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $totals[$statusKey]['count'] ?? 0 }}</span>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">parcelas</span>
                            </dd>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">
                                Total: R$ {{ number_format($totals[$statusKey]['amount_brl'] ?? 0, 2, ',', '.') }}
                            </dd>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tabela de Vencimentos --}}
        @include('partials.table-vencimentos', ['payments' => $payments])
    </div>
    @push('scripts')
{{-- Script Refatorado e Mais Seguro --}}
<script>
    function exportToPdf(button) {
        const buttonContent = button.querySelector('.button-content');
        const loadingSpinner = button.querySelector('.loading-spinner');
        
        // Verifica se os elementos existem antes de manipulá-los
        if (!buttonContent || !loadingSpinner) {
            console.error("Estrutura do botão de exportação inválida. Classes '.button-content' ou '.loading-spinner' não encontradas.");
            return; // Interrompe a execução se a estrutura do botão estiver errada
        }

        // Mostra o spinner e desabilita o botão
        buttonContent.style.display = 'none';
        loadingSpinner.style.display = 'inline-flex';
        button.disabled = true;

        const form = document.getElementById('dueDateFiltersForm');
        const formData = new FormData(form);
        const queryString = new URLSearchParams(formData).toString();
        
        // Abre a URL de exportação em uma nova aba com os filtros
        // window.open(`{{ route('reports.due-dates.exportPdf') }}?${queryString}`, '_blank');
        
        // Alteração: Em vez de abrir uma nova aba, redireciona a página atual para iniciar o download
        // Isso é mais compatível com bloqueadores de pop-up.
        window.location.href = `{{ route('reports.due-dates.exportPdf') }}?${queryString}`;

        // Reativa o botão após um tempo para permitir uma nova tentativa se algo der errado
        setTimeout(() => {
            buttonContent.style.display = 'flex';
            loadingSpinner.style.display = 'none';
            button.disabled = false;
        }, 8000); // Aumenta o tempo para 8 segundos para dar tempo de o servidor processar
    }
</script>
@endpush
</x-app-layout>