{{-- Cards de Resumo do Período --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    {{-- Card: Contas a Receber --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-green-500 p-5">
        <div class="flex items-center">
            <div class="text-green-500 text-3xl">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="ml-4 flex-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Contas a Receber</dt>
                <dd class="mt-1 flex items-baseline">
                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $summary['receivable']['count'] }}</span>
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">parcelas</span>
                </dd>
                <dd class="text-lg font-bold text-green-600 dark:text-green-400">
                    R$ {{ number_format($summary['receivable']['total'], 2, ',', '.') }}
                </dd>
            </div>
        </div>
    </div>

    {{-- Card: Pagamentos a Artistas --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-red-500 p-5">
        <div class="flex items-center">
            <div class="text-red-500 text-3xl">
                <i class="fas fa-users"></i>
            </div>
            <div class="ml-4 flex-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pagar Artistas</dt>
                <dd class="mt-1 flex items-baseline">
                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $summary['artists']['count'] }}</span>
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">gigs</span>
                </dd>
                <dd class="text-lg font-bold text-red-600 dark:text-red-400">
                    R$ {{ number_format($summary['artists']['total'], 2, ',', '.') }}
                </dd>
            </div>
        </div>
    </div>

    {{-- Card: Comissões Bookers --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-orange-500 p-5">
        <div class="flex items-center">
            <div class="text-orange-500 text-3xl">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="ml-4 flex-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Comissões Bookers</dt>
                <dd class="mt-1 flex items-baseline">
                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $summary['bookers']['count'] }}</span>
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">gigs</span>
                </dd>
                <dd class="text-lg font-bold text-orange-600 dark:text-orange-400">
                    R$ {{ number_format($summary['bookers']['total'], 2, ',', '.') }}
                </dd>
            </div>
        </div>
    </div>

    {{-- Card: Despesas Previstas --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border-l-4 border-purple-500 p-5">
        <div class="flex items-center">
            <div class="text-purple-500 text-3xl">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="ml-4 flex-1">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Despesas Previstas</dt>
                <dd class="mt-1 flex items-baseline">
                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $summary['expenses']['count'] }}</span>
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">itens</span>
                </dd>
                <dd class="text-lg font-bold text-purple-600 dark:text-purple-400">
                    R$ {{ number_format($summary['expenses']['total'], 2, ',', '.') }}
                </dd>
            </div>
        </div>
    </div>
</div>
