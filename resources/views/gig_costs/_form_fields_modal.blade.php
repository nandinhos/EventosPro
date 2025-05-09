{{-- Parcial para os campos do formulário de GigCost dentro de um modal Alpine --}}
{{-- Espera x-data com costFormData e costCenters --}}

{{-- Centro de Custo --}}
<div>
    <label for="modal_cost_center_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Centro de Custo <span class="text-red-500">*</span></label>
    <select id="modal_cost_center_id" x-model="costFormData.cost_center_id" x-ref="costCenterSelect" required
            class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        <option value="">Selecione...</option>
        <template x-for="(name, id) in costCenters" :key="id">
            <option :value="id" x-text="name"></option>
        </template>
    </select>
    {{-- TODO: Exibir erro de validação para este campo --}}
</div>

{{-- Descrição --}}
<div>
    <label for="modal_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descrição <span class="text-red-500">*</span></label>
    <input type="text" id="modal_description" x-model="costFormData.description" required
           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
</div>

{{-- Valor e Moeda --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <label for="modal_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" id="modal_value" x-model.number="costFormData.value" required
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
    </div>
    <div>
        <label for="modal_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda <span class="text-red-500">*</span></label>
        <select id="modal_currency" x-model="costFormData.currency" required
                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="BRL">BRL</option> <option value="USD">USD</option> <option value="EUR">EUR</option> <option value="GPB">GBP</option>
        </select>
    </div>
</div>

{{-- Pagador --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="modal_payer_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pagador <span class="text-red-500">*</span></label>
        <select id="modal_payer_type" x-model="costFormData.payer_type" required
                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="agencia">Agência</option> <option value="artista">Artista</option> <option value="cliente">Cliente</option> <option value="outro">Outro</option>
        </select>
    </div>
    <div x-show="costFormData.payer_type === 'outro'">
        <label for="modal_payer_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Detalhes do Pagador (se "Outro")</label>
        <input type="text" id="modal_payer_details" x-model="costFormData.payer_details" :required="costFormData.payer_type === 'outro'"
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
    </div>
</div>

{{-- Data da Despesa --}}
<div>
    <label for="modal_expense_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data da Despesa</label>
    <input type="date" id="modal_expense_date" x-model="costFormData.expense_date"
           class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
</div>

{{-- Notas --}}
<div>
    <label for="modal_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
    <textarea id="modal_notes" x-model="costFormData.notes" rows="2"
              class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
</div>