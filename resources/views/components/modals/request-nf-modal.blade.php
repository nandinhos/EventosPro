{{-- resources/views/components/modals/request-nf-modal.blade.php --}}
<div x-data="{ open: false }" 
     x-show="open"
     @open-request-nf-modal.window="open = true"
     @keydown.escape.window="open = false"
     class="fixed inset-0 overflow-y-auto z-50"
     style="display: none;">

    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div class="fixed inset-0 transition-opacity" aria-hidden="true"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
        </div>

        {{-- Modal panel --}}
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
             x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            {{-- Modal content --}}
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-file-invoice text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            Solicitar Nota Fiscal
                        </h3>
                        <div class="mt-2">
                            <div class="space-y-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Deseja enviar uma solicitação de nota fiscal para o artista?
                                    Um e-mail será enviado com as instruções e valores para emissão.
                                </p>

                                {{-- Detalhamento das Despesas --}}
                                <div class="mt-4 space-y-2">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Despesas por Centro de Custo:</h4>
                                    <div class="space-y-2 text-sm bg-gray-50 dark:bg-gray-700/20 rounded-md p-3">
                                        {{-- Alimentação --}}
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-utensils text-gray-400 mr-1"></i> Alimentação:
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gig->confirmed_expenses_food_brl ?? 0, 2, ',', '.') }}</span>
                                        </div>

                                        {{-- Hospedagem --}}
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-bed text-gray-400 mr-1"></i> Hospedagem:
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gig->confirmed_expenses_accommodation_brl ?? 0, 2, ',', '.') }}</span>
                                        </div>

                                        {{-- Logística --}}
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-truck text-gray-400 mr-1"></i> Logística:
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gig->confirmed_expenses_logistics_brl ?? 0, 2, ',', '.') }}</span>
                                        </div>

                                        {{-- Total --}}
                                        <div class="pt-2 mt-2 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between font-medium">
                                            <span class="text-gray-700 dark:text-gray-300">Total Despesas:</span>
                                            <span class="text-gray-800 dark:text-gray-200">R$ {{ number_format(($gig->confirmed_expenses_food_brl ?? 0) + ($gig->confirmed_expenses_accommodation_brl ?? 0) + ($gig->confirmed_expenses_logistics_brl ?? 0), 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal actions --}}
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="open = false">
                    Confirmar
                </button>
                <button type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="open = false">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>