<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mt-6">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-4">Fechamento Financeiro do Artista</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
            <h4 class="font-medium text-gray-600 dark:text-gray-300">Resumo do Período</h4>
            <dl class="mt-2 space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Total de Gigs Realizadas:</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $realizedGigs->count() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Cachê Bruto Total:</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">R$ {{ number_format($metrics['totalGrossFee'], 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Líquido Pago:</dt>
                    <dd class="font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($metrics['cache_received_brl'], 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Líquido Pendente:</dt>
                    <dd class="font-semibold text-yellow-600 dark:text-yellow-400">R$ {{ number_format($metrics['cache_pending_brl'], 2, ',', '.') }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg flex flex-col justify-center">
            <h4 class="font-medium text-gray-600 dark:text-gray-300">Ações de Fechamento</h4>
            <div class="mt-4 space-y-3">
                <button type="button" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
                    <i class="fas fa-file-pdf mr-2"></i>
                    Gerar Relatório PDF
                </button>
                <button type="button" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    <i class="fas fa-marker mr-2"></i>
                    Marcar Gigs como Pagas
                </button>
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-lg font-medium text-gray-800 dark:text-white mb-3">Gigs Pendentes de Pagamento</h4>
        @include('artists.components.gigs-table', ['gigs' => $realizedGigs->where('is_paid_to_artist', false)])
    </div>
</div>