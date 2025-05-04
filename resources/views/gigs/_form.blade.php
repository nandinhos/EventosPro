{{--
    Formulário parcial para Gigs (usado em create e edit).
    Recebe as variáveis: $gig, $artists, $bookers, $tags, $selectedTags
--}}
<div class="p-6 space-y-6">

    {{-- Linha 1: Artista, Booker, Data Evento --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista <span class="text-red-500">*</span></label>
            <select id="artist_id" name="artist_id" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('artist_id') border-red-500 @enderror">
                <option value="">Selecione...</option>
                @foreach($artists as $id => $name)
                    <option value="{{ $id }}" {{ old('artist_id', $gig->artist_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            @error('artist_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker</label>
            <select id="booker_id" name="booker_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_id') border-red-500 @enderror">
                <option value="">Agência / Sem Booker</option> {{-- Opção para Nulo --}}
                @foreach($bookers as $id => $name)
                     {{-- Excluir 'CORAL' se ele representa a agência e não um booker selecionável --}}
                     {{-- @if(strtoupper($name) === 'CORAL') @continue @endif  --}}
                     <option value="{{ $id }}" {{ old('booker_id', $gig->booker_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
             @error('booker_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="gig_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento <span class="text-red-500">*</span></label>
            <input type="date" id="gig_date" name="gig_date" value="{{ old('gig_date', $gig->gig_date?->format('Y-m-d')) }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('gig_date') border-red-500 @enderror">
             @error('gig_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Linha 2: Local/Evento Detalhes --}}
     <div>
        <label for="location_event_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local / Evento <span class="text-red-500">*</span></label>
        <textarea id="location_event_details" name="location_event_details" rows="2" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('location_event_details') border-red-500 @enderror">{{ old('location_event_details', $gig->location_event_details) }}</textarea>
        @error('location_event_details') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Linha 3: Financeiro (Cachê) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <div>
            <label for="cache_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cachê Bruto <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" id="cache_value" name="cache_value" value="{{ old('cache_value', $gig->cache_value) }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('cache_value') border-red-500 @enderror">
            @error('cache_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda <span class="text-red-500">*</span></label>
            <select id="currency" name="currency" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('currency') border-red-500 @enderror">
                <option value="BRL" {{ old('currency', $gig->currency ?? 'BRL') == 'BRL' ? 'selected' : '' }}>BRL</option>
                <option value="USD" {{ old('currency', $gig->currency) == 'USD' ? 'selected' : '' }}>USD</option>
                <option value="EUR" {{ old('currency', $gig->currency) == 'EUR' ? 'selected' : '' }}>EUR</option>
                <option value="GPB" {{ old('currency', $gig->currency) == 'GPB' ? 'selected' : '' }}>GBP</option>
            </select>
             @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="exchange_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Câmbio (se não BRL)</label>
            <input type="number" step="0.000001" id="exchange_rate" name="exchange_rate" value="{{ old('exchange_rate', $gig->exchange_rate) }}" placeholder="Ex: 5.251234" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('exchange_rate') border-red-500 @enderror">
             @error('exchange_rate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
         <div>
            <label for="expenses_value_brl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Despesas (BRL)</label>
            <input type="number" step="0.01" id="expenses_value_brl" name="expenses_value_brl" value="{{ old('expenses_value_brl', $gig->expenses_value_brl ?? '0.00') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('expenses_value_brl') border-red-500 @enderror">
             @error('expenses_value_brl') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

     {{-- Linha 4: Comissões --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- Comissão Booker --}}
        <div>
             <label for="booker_commission_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo Comissão Booker</label>
            <select id="booker_commission_type" name="booker_commission_type" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_type') border-red-500 @enderror">
                <option value="percent" {{ old('booker_commission_type', $gig->booker_commission_type ?? 'percent') == 'percent' ? 'selected' : '' }}>Percentual (%)</option>
                <option value="fixed" {{ old('booker_commission_type', $gig->booker_commission_type) == 'fixed' ? 'selected' : '' }}>Valor Fixo (BRL)</option>
            </select>
        </div>
         <div>
            <label for="booker_commission_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor/Taxa Comissão Booker</label>
            <input type="number" step="0.01" id="booker_commission_value" name="booker_commission_value" value="{{ old('booker_commission_value', $gig->booker_commission_value) }}" placeholder="Ex: 5.00 (%) ou 250.00 (fixo)" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_value') border-red-500 @enderror">
             @error('booker_commission_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Comissão Agência (Similar, se necessário separar do Booker) --}}
        {{-- <div>
            <label for="agency_commission_type">Tipo Comissão Agência</label> ...
        </div>
         <div>
            <label for="agency_commission_value">Valor/Taxa Comissão Agência</label> ...
        </div> --}}
    </div>

    {{-- Linha 5: Status e Contrato Formal --}}
     <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label for="contract_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Contrato</label>
             <select id="contract_status" name="contract_status" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_status') border-red-500 @enderror">
                <option value="n/a" {{ old('contract_status', $gig->contract_status ?? 'n/a') == 'n/a' ? 'selected' : '' }}>N/A (Sem Contrato)</option>
                <option value="para_assinatura" {{ old('contract_status', $gig->contract_status) == 'para_assinatura' ? 'selected' : '' }}>Para Assinatura</option>
                <option value="assinado" {{ old('contract_status', $gig->contract_status) == 'assinado' ? 'selected' : '' }}>Assinado</option>
                <option value="concluido" {{ old('contract_status', $gig->contract_status) == 'concluido' ? 'selected' : '' }}>Concluído</option>
                <option value="expirado" {{ old('contract_status', $gig->contract_status) == 'expirado' ? 'selected' : '' }}>Expirado</option>
                <option value="cancelado" {{ old('contract_status', $gig->contract_status) == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
             @error('contract_status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
             <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número Contrato (Opcional)</label>
             <input type="text" id="contract_number" name="contract_number" value="{{ old('contract_number', $gig->contract_number) }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_number') border-red-500 @enderror">
             @error('contract_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
         <div>
            <label for="contract_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Contrato (Opcional)</label>
            <input type="date" id="contract_date" name="contract_date" value="{{ old('contract_date', $gig->contract_date?->format('Y-m-d')) }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_date') border-red-500 @enderror">
             @error('contract_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Linha 6: Tags e Notas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
         {{-- Tags (Select Múltiplo) --}}
        <div>
            <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (Opcional)</label>
            {{-- Usar biblioteca JS como TomSelect/Select2 é altamente recomendado para UX aqui --}}
             <select name="tags[]" id="tags" multiple class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('tags.*') border-red-500 @enderror">
                @foreach ($tags as $type => $tagGroup)
                     <optgroup label="{{ Str::ucfirst($type ?? 'Geral') }}">
                        @foreach ($tagGroup as $tag)
                            <option value="{{ $tag->id }}" {{ in_array($tag->id, $selectedTags) ? 'selected' : '' }}>
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

         {{-- Notas --}}
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
            <textarea id="notes" name="notes" rows="4" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('notes') border-red-500 @enderror">{{ old('notes', $gig->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

</div>