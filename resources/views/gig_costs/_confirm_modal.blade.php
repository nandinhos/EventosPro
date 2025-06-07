{{--
    Modal para confirmar uma despesa com data manual.
    Seu estado (show, confirmCostData) é controlado pelo Alpine component pai (costsManager).
--}}
<div x-show="showConfirmModal"
    class="fixed inset-0 z-[99] overflow-y-auto flex items-center justify-center backdrop-blur-sm"
    style="display: none;"
    x-trap.inert.noscroll="showConfirmModal">

    {{-- Overlay --}}
    <div class="fixed inset-0 bg-black bg-opacity-60" @click="showConfirmModal = false"></div>

    {{-- Container do Modal (com classes de largura e centralização) --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-sm mx-auto shadow-2xl" @click.away="showConfirmModal = false">
        <form @submit.prevent="submitConfirmForm()">
            <div class="p-6">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Confirmar Despesa</h3>
                    <button type="button" @click="showConfirmModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">&times;</button>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 my-3">
                    Qual a data de confirmação para a despesa "<strong x-text="confirmCostData.description"></strong>"?
                </p>
                <div>
                    <label for="confirmation_date_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data da Confirmação <span class="text-red-500">*</span></label>
                    <input type="date" id="confirmation_date_input" x-model="confirmCostData.date" x-ref="confirmation_date_input" required
                           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
            </div>
            <div class="flex justify-end space-x-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-b-lg">
                <button type="button" @click="showConfirmModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200 px-4 py-2 rounded-md text-sm">Cancelar</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">Confirmar</button>
            </div>
        </form>
    </div>
</div>