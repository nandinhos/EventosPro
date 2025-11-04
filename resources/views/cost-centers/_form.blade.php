<div class="space-y-6">
    <!-- Name -->
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome *</label>
        <input type="text" name="name" id="name" value="{{ old('name', $costCenter->name ?? '') }}"
               required maxlength="255"
               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Description -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
        <textarea name="description" id="description" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('description', $costCenter->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Is Active -->
    <div class="flex items-center">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               {{ old('is_active', $costCenter->is_active ?? true) ? 'checked' : '' }}
               class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
        <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Centro de Custo Ativo</label>
    </div>

    <!-- Color with Toggle -->
    <div x-data="{ useCustomColor: {{ old('use_custom_color', isset($costCenter) && $costCenter->color ? 'true' : 'false') }} }">
        <!-- Toggle Checkbox -->
        <div class="block">
            <label for="use_custom_color" class="inline-flex items-center">
                <input type="checkbox" id="use_custom_color"
                       name="use_custom_color"
                       x-model="useCustomColor"
                       value="1"
                       class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                    Usar cor personalizada
                </span>
            </label>
        </div>

        <!-- Color Picker (only visible when toggle is ON) -->
        <div x-show="useCustomColor" x-transition class="mt-3">
            <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Escolha a cor
            </label>
            <div class="flex items-center gap-3">
                <input type="color"
                       name="color"
                       id="color"
                       value="{{ old('color', $costCenter->color ?? '#6366f1') }}"
                       :disabled="!useCustomColor"
                       class="h-10 w-20 rounded border-gray-300 dark:border-gray-700">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Cor para identificação visual nas tabelas
                </span>
            </div>
            @error('color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('cost-centers.index') }}"
           class="bg-gray-500 hover:bg-gray-600 text-white font-medium px-5 py-2 rounded-md text-sm transition">
            Cancelar
        </a>
        <button type="submit"
                class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2 rounded-md text-sm shadow-md transition flex items-center">
            <i class="fas fa-save mr-2"></i>
            {{ isset($costCenter) ? 'Atualizar' : 'Criar' }}
        </button>
    </div>
</div>
