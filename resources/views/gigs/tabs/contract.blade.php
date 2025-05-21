<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Contract Status -->
    <div>
        <label for="contract_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status do Contrato</label>
        <select name="contract_status" id="contract_status" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="">Selecione...</option>
            <option value="assinado" {{ old('contract_status') == 'assinado' ? 'selected' : '' }}>Assinado</option>
            <option value="para_assinatura" {{ old('contract_status') == 'para_assinatura' ? 'selected' : '' }}>Para Assinatura</option>
            <option value="n/a" {{ old('contract_status') == 'n/a' ? 'selected' : '' }}>N/A</option>
        </select>
        @error('contract_status')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Contract Number -->
    <div>
        <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Contrato</label>
        <input type="text" name="contract_number" id="contract_number" value="{{ old('contract_number') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('contract_number')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Contract Date -->
    <div>
        <label for="contract_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Contrato</label>
        <input type="date" name="contract_date" id="contract_date" value="{{ old('contract_date') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('contract_date')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>
</div>