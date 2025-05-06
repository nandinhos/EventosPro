{{-- Recebe $artist, $tags, $selectedTags --}}
@php $currentSelectedTags = old('tags', $selectedTags ?? []); @endphp
<div class="p-6 space-y-6">
    {{-- Nome --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome do Artista <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $artist->name) }}" required
               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('name') border-red-500 dark:border-red-600 @enderror">
        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Informações de Contato --}}
    <div>
        <label for="contact_info" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Informações de Contato (Opcional)</label>
        <textarea id="contact_info" name="contact_info" rows="3"
                  class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contact_info') border-red-500 dark:border-red-600 @enderror"
        >{{ old('contact_info', $artist->contact_info) }}</textarea>
        @error('contact_info') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Tags --}}
    <div>
        <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (Opcional)</label>
         <select name="tags[]" id="tags" multiple
                 class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('tags.*') border-red-500 dark:border-red-600 @enderror">
            @foreach ($tags as $type => $tagGroup)
                 <optgroup label="{{ $type ? Str::ucfirst(str_replace('_', ' ', $type)) : 'Geral' }}">
                    @foreach ($tagGroup as $tag)
                        <option value="{{ $tag->id }}" @selected(in_array($tag->id, $currentSelectedTags))>
                            {{ $tag->name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
         </select>
         <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Segure Ctrl/Cmd para selecionar várias.</p>
         @error('tags.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
         @error('tags') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>