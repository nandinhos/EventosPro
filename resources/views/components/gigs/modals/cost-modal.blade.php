<!-- Modal de Edição/Adição de Despesa -->
<div x-data="{ showModal: false }" x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-full max-w-md mx-auto p-6 relative">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Nova Despesa</h3>
            <!-- Formulário da despesa -->
            <form action="#" method="POST" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label for="modal_cost_center_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Centro de Custo</label>
                    <select id="modal_cost_center_id" name="cost_center_id" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Selecione...</option>
                        @foreach ($costCenters as $id => $name)
                            <option :value="id" x-text="name" value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="modal_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição</label>
                    <input type="text" id="modal_description" name="description" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="modal_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor</label>
                        <input type="number" step="0.01" id="modal_value" name="value" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label for="modal_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda</label>
                        <select id="modal_currency" name="currency" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="BRL">BRL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" @click="showModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm">Cancelar</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>