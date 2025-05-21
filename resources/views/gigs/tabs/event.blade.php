<div class="space-y-4">
    <!-- Artista -->
    <div>
        <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista <span class="text-red-500">*</span></label>
        <select name="artist_id" id="artist_id" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="">Selecione...</option>
            @foreach ($artists as $id => $name)
                <option value="{{ $id }}" {{ old('artist_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @error('artist_id')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Booker -->
    <div>
        <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker</label>
        <select name="booker_id" id="booker_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="">Selecione...</option>
            @foreach ($bookers as $id => $name)
                <option value="{{ $id }}" {{ old('booker_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        @error('booker_id')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Data do Evento -->
    <div>
        <label for="gig_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Evento <span class="text-red-500">*</span></label>
        <input type="date" name="gig_date" id="gig_date" value="{{ old('gig_date') }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('gig_date')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Local / Detalhes -->
    <div>
        <label for="location_event_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local / Detalhes do Evento <span class="text-red-500">*</span></label>
        <input type="text" name="location_event_details" id="location_event_details" value="{{ old('location_event_details') }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('location_event_details')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Cachê Bruto -->
    <div>
        <label for="cache_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cachê Bruto <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" name="cache_value" id="cache_value" value="{{ old('cache_value') }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('cache_value')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Moeda -->
    <div>
        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda <span class="text-red-500">*</span></label>
        <select name="currency" id="currency" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="">Selecione...</option>
            <option value="BRL" {{ old('currency') == 'BRL' ? 'selected' : '' }}>BRL</option>
            <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD</option>
            <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
            <option value="GBP" {{ old('currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
        </select>
        @error('currency')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>

    <!-- Taxa de Câmbio -->
    <div>
        <label for="exchange_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taxa de Câmbio (BRL)</label>
        <input type="number" step="0.0001" name="exchange_rate" id="exchange_rate" value="{{ old('exchange_rate') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
        @error('exchange_rate')
        <span class="text-xs text-red-500">{{ $message }}</span>
        @enderror
    </div>
</div>