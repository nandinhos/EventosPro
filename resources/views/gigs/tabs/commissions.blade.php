<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Tipo de Comissão da Agência -->
    <div>
        <label for="agency_commission_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Comissão da Agência</label>
        <select name="agency_commission_type" id="agency_commission_type" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="fixa" {{ old('agency_commission_type') == 'fixa' ? 'selected' : '' }}>Fixa</option>
            <option value="porcentagem" {{ old('agency_commission_type') == 'porcentagem' ? 'selected' : '' }}>Porcentagem</option>
        </select>
        @error('agency_commission_type')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Valor/Taxa da Comissão da Agência -->
    <div>
        <label for="agency_commission_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor/Taxa da Comissão da Agência</label>
        <input type="number" step="0.01" name="agency_commission_value" id="agency_commission_value" value="{{ old('agency_commission_value') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('agency_commission_value')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Tipo de Comissão do Booker -->
    <div>
        <label for="booker_commission_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Comissão do Booker</label>
        <select name="booker_commission_type" id="booker_commission_type" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="fixa" {{ old('booker_commission_type') == 'fixa' ? 'selected' : '' }}>Fixa</option>
            <option value="porcentagem" {{ old('booker_commission_type') == 'porcentagem' ? 'selected' : '' }}>Porcentagem</option>
        </select>
        @error('booker_commission_type')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Valor/Taxa da Comissão do Booker -->
    <div>
        <label for="booker_commission_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor/Taxa da Comissão do Booker</label>
        <input type="number" step="0.01" name="booker_commission_value" id="booker_commission_value" value="{{ old('booker_commission_value') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('booker_commission_value')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>
</div>