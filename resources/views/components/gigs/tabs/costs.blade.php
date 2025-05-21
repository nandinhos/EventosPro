<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-md border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Despesas por Centro de Custo</h3>

        <!-- Lista Dinâmica de Despesas -->
        <div id="cost-list" class="space-y-3">
            <div class="flex items-start space-x-3">
                <div class="w-1/3">
                    <label for="cost_center_id" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Centro de Custo</label>
                    <select name="cost_center_id[]" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Selecione...</option>
                        @foreach ($costCenters as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-1/3">
                    <label for="description" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Descrição</label>
                    <input type="text" name="description[]" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div class="w-1/4">
                    <label for="value" class="block text-xs font-medium text-gray-600 dark:text-gray-400">Valor ({{ old('currency') ?: 'BRL' }})</label>
                    <input type="number" step="0.01" name="value[]" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div class="pt-5">
                    <button type="button" class="text-red-500 hover:text-red-700" onclick="removeCost(this)">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Botão Adicionar Despesa -->
        <div class="mt-3">
            <button type="button" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center"
                    onclick="addCost()">
                <i class="fas fa-plus-circle mr-1"></i> Adicionar Despesa
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function addCost() {
        const container = document.getElementById('cost-list');
        const newCost = document.createElement('div');
        newCost.className = 'flex items-start space-x-3';
        newCost.innerHTML = `
            <div class="w-1/3">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Centro de Custo</label>
                <select name="cost_center_id[]" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Selecione...</option>
                    @foreach ($costCenters as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-1/3">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Descrição</label>
                <input type="text" name="description[]" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="w-1/4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">Valor ({{ old('currency') ?: 'BRL' }})</label>
                <input type="number" step="0.01" name="value[]" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            </div>
            <div class="pt-5">
                <button type="button" class="text-red-500 hover:text-red-700" onclick="removeCost(this)">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        container.appendChild(newCost);
    }

    function removeCost(button) {
        button.closest('.flex').remove();
    }
</script>
@endpush