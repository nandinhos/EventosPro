<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 space-y-6">
                    {{-- Cabeçalho com Botão de Voltar --}}
                    <div class="flex justify-between items-center mb-6">
                        <button
                            onclick="history.back()"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            <i class="fas fa-arrow-left mr-2"></i>
                            Voltar
                        </button>
                        <button
                            onclick="window.print()"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150 print:hidden"
                        >
                            <i class="fas fa-print mr-2"></i>
                            Imprimir
                        </button>
                    </div>

                    {{-- Conteúdo Principal --}}
                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-8 space-y-6 print:bg-white print:dark:bg-white print:text-black">
                        {{-- Cabeçalho do Documento --}}
                        <div class="text-center mb-8">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white print:text-black">Solicitação de Nota Fiscal</h1>
                            <p class="text-xl text-primary-600 dark:text-primary-400 mt-2 print:text-primary-600" x-text="artistName"></p>
                        </div>

                        {{-- Informações do Evento --}}
                        <div class="space-y-4 mb-8">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-calendar-alt text-primary-600 w-6"></i>
                                <span class="text-gray-600 dark:text-gray-400 print:text-gray-600 w-40">Nome do Evento:</span>
                                <span class="font-semibold text-gray-800 dark:text-white print:text-black" x-text="eventName"></span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-clock text-primary-600 w-6"></i>
                                <span class="text-gray-600 dark:text-gray-400 print:text-gray-600 w-40">Data do Evento:</span>
                                <span class="font-semibold text-gray-800 dark:text-white print:text-black" x-text="eventDate"></span>
                            </div>
                        </div>

                        {{-- Detalhamento Financeiro --}}
                        <div class="space-y-4">
                            {{-- Valor do Contrato --}}
                            <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg print:bg-gray-100">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-file-invoice-dollar text-primary-600"></i>
                                    <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Valor do Contrato:</span>
                                </div>
                                <span class="font-semibold text-gray-800 dark:text-white print:text-black" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(contractValue)}`"></span>
                            </div>

                            {{-- Despesas --}}
                            <div class="border-t border-gray-200 dark:border-gray-600 pt-4 space-y-3">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-receipt text-primary-600"></i>
                                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">(-) Despesas:</span>
                                    </div>
                                    <span class="font-semibold text-red-600 dark:text-red-400 print:text-red-600" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(expenses)}`"></span>
                                </div>

                                {{-- Detalhamento das Despesas por Categoria --}}
                                <div class="ml-8 space-y-2 bg-gray-50 dark:bg-gray-700/20 rounded-md p-4 print:bg-gray-50">
                                    <template x-if="expensesByCategory?.Alimentação > 0">
                                        <div class="flex justify-between items-center text-sm">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-utensils text-gray-400"></i>
                                                <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Alimentação:</span>
                                            </div>
                                            <span class="text-red-500 dark:text-red-400 print:text-red-500" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(expensesByCategory?.Alimentação)}`"></span>
                                        </div>
                                    </template>

                                    <template x-if="expensesByCategory?.Hospedagem > 0">
                                        <div class="flex justify-between items-center text-sm">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-bed text-gray-400"></i>
                                                <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Hospedagem:</span>
                                            </div>
                                            <span class="text-red-500 dark:text-red-400 print:text-red-500" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(expensesByCategory?.Hospedagem)}`"></span>
                                        </div>
                                    </template>

                                    <template x-if="expensesByCategory?.Logística > 0">
                                        <div class="flex justify-between items-center text-sm">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-truck text-gray-400"></i>
                                                <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Logística:</span>
                                            </div>
                                            <span class="text-red-500 dark:text-red-400 print:text-red-500" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(expensesByCategory?.Logística)}`"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Reembolsos NF --}}
                            <template x-if="invoiceExpenses > 0">
                                <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg print:bg-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <i class="fas fa-file-invoice text-primary-600"></i>
                                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">(+) Reembolsos NF:</span>
                                    </div>
                                    <span class="font-semibold text-green-600 dark:text-green-400 print:text-green-600" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(invoiceExpenses)}`"></span>
                                </div>
                            </template>

                            {{-- Valor Cachê Bruto --}}
                            <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg print:bg-gray-100">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-wallet text-primary-600"></i>
                                    <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">= Valor Cachê Bruto:</span>
                                </div>
                                <span class="font-semibold text-gray-800 dark:text-white print:text-black" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(netCache)}`"></span>
                            </div>

                            {{-- Outros (Despesas com NF) --}}
                            <div class="flex justify-between items-center p-3 bg-white dark:bg-gray-700 rounded-lg print:bg-gray-100">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-file-invoice text-primary-600"></i>
                                    <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">(-) Outros (Despesas NF):</span>
                                </div>
                                <span class="font-semibold text-red-600 dark:text-red-400 print:text-red-600" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(invoiceExpenses)}`"></span>
                            </div>

                            {{-- Valor Cachê Líquido (NF) --}}
                            <div class="flex justify-between items-center p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border-2 border-primary-200 dark:border-primary-700 print:bg-primary-50 print:border-primary-200">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-hand-holding-usd text-green-600"></i>
                                    <span class="font-medium text-gray-700 dark:text-gray-300 print:text-gray-700">= Valor Cachê Líquido (NF):</span>
                                </div>
                                <span class="font-bold text-2xl text-green-600 dark:text-green-400 print:text-green-600" x-text="`R$ ${new Intl.NumberFormat('pt-BR').format(netArtistCacheToReceive)}`"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos para Impressão --}}
    <style>
        @media print {
            @page {
                size: A4;
                margin: 2cm;
            }
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .print\:hidden {
                display: none !important;
            }
        }
    </style>

    {{-- Scripts --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('requestNfData', () => ({
                // ... seus dados Alpine aqui
            }))
        })
    </script>
</x-app-layout>