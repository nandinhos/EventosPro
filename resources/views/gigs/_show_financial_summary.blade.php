{{-- resources/views/gigs/_show_financial_summary.blade.php --}}
@props(['gig', 'financialData'])

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Resumo Financeiro</h3>
    </div>
    <div class="p-6 space-y-3 text-sm">
        <div>
            <span class="text-gray-500 dark:text-gray-400">Valor Contrato:</span>
            <span class="font-semibold text-gray-900 dark:text-white ml-2">
                {{ $gig->currency }} {{ number_format($gig->cache_value ?? 0, 2, ',', '.') }}
            </span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Total Recebido:</span>
            <span class="font-semibold text-green-600 dark:text-green-400 ml-2">
                {{ $gig->currency }} {{ number_format($financialData['totalReceivedInOriginalCurrency'], 2, ',', '.') }}
            </span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Saldo Pendente:</span>
            <span class="font-semibold {{ ($financialData['pendingBalanceInOriginalCurrency'] ?? 0) <= 0.01 ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400' }} ml-2">
                {{ $gig->currency }} {{ number_format($financialData['pendingBalanceInOriginalCurrency'], 2, ',', '.') }}
            </span>
        </div>
        <hr class="my-3 border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            <div><strong class="text-gray-500 dark:text-gray-400">Pgto Cliente:</strong> <x-status-badge :status="$gig->payment_status" type="payment" class="ml-1"/></div>
            <div><strong class="text-gray-500 dark:text-gray-400">Pgto Artista:</strong> <x-status-badge :status="$gig->artist_payment_status" type="payment-internal" class="ml-1"/></div>
            <div><strong class="text-gray-500 dark:text-gray-400">Pgto Booker:</strong> <x-status-badge :status="$gig->booker_payment_status" type="payment-internal" class="ml-1"/></div>
        </div>
    </div>
</div>