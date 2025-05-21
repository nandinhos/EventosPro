<div class="space-y-4">
    <!-- Tags -->
    <div>
        <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (Segure Ctrl/Cmd para selecionar várias)</label>
        <select name="tags[]" id="tags" multiple class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            @foreach ($tags as $tag)
                <option value="{{ $tag->id }}" {{ is_array(old('tags')) && in_array($tag->id, old('tags')) ? 'selected' : '' }}>
                    {{ $tag->name }}
                </option>
            @endforeach
        </select>
        @error('tags')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Notas -->
    <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
        <textarea name="notes" id="notes" rows="3" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">{{ old('notes') }}</textarea>
        @error('notes')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>
</div>