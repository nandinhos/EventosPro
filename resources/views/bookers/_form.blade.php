{{-- Recebe $booker --}}
<div class="p-6 space-y-6">
    {{-- Nome --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Booker <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $booker->name) }}" required
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('name') border-red-500 dark:border-red-600 @enderror">
        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Comissão Padrão --}}
    <div>
        <label for="default_commission_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comissão Padrão (%) (Opcional)</label>
        <input type="number" step="0.01" id="default_commission_rate" name="default_commission_rate"
               value="{{ old('default_commission_rate', $booker->default_commission_rate) }}"
               placeholder="Ex: 5.00"
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('default_commission_rate') border-red-500 dark:border-red-600 @enderror">
         @error('default_commission_rate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>