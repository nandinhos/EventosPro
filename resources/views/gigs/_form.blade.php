{{-- resources/views/gigs/_form.blade.php --}}
{{--
    Formulário parcial para Gigs (usado em create e edit).
    Recebe as variáveis:
    - $gig (Objeto Gig, vazio para create, preenchido para edit)
    - $artists (Array/Collection de artistas para select)
    - $bookers (Array/Collection de bookers para select)
    - $tags (Array/Collection agrupada de tags para select)
    - $selectedTags (Array de IDs das tags selecionadas para edit/old)
--}}
@php
    // Define as tags selecionadas atuais, priorizando 'old' input em caso de erro de validação
    $currentSelectedTags = old('tags', $selectedTags ?? []);

    // Pegar valores iniciais para Alpine (prioriza 'old', depois $gig)
    // Usa ?? 'percent' como fallback seguro se $gig for novo e não tiver tipo
    $initialBookerType = old('booker_commission_type', $gig->booker_commission_type ?? 'percent');
    // Pega a taxa % salva ou null
    $initialBookerRate = old('booker_commission_rate', $gig->booker_commission_rate);
    // Pega o valor fixo salvo APENAS se o tipo salvo era 'fixed', ou null
    $initialBookerFixedValue = old('booker_commission_value', $initialBookerType === 'fixed' ? $gig->booker_commission_value : null);

    // Determina qual valor mostrar inicialmente no input com base no TIPO inicial
    // Se tipo for percent, mostra a taxa; senão (fixed ou novo), mostra o valor fixo (ou nada se for novo e tipo percent)
    $initialBookerDisplayValue = $initialBookerType === 'percent' ? $initialBookerRate : $initialBookerFixedValue;

    // Pegar valores base para cálculo (prioriza old)
    $initialCacheValue = old('cache_value', $gig->cache_value);
    $initialExpensesValue = old('expenses_value_brl', $gig->expenses_value_brl ?? '0.00');

@endphp

{{-- Adiciona x-data para controlar a lógica da comissão --}}
<div class="p-6 space-y-6"
     x-data="{
        commissionType: '{{ $initialBookerType }}',
        commissionRate: {{ $initialBookerRate ?? 'null' }}, // Armazena a taxa % original ou vinda do old() ou null
        commissionFixedValue: {{ $initialBookerFixedValue ?? 'null' }}, // Armazena o valor fixo original ou vindo do old() ou null
        commissionDisplayValue: {{ $initialBookerDisplayValue ?? 'null' }}, // Valor que vai no input number - inicializado pelo PHP
        baseCacheValue: {{ $initialCacheValue ?? 0 }},
        expensesValue: {{ $initialExpensesValue ?? 0 }},

        // Função para calcular o valor monetário a partir da taxa
        calculateValueFromRate(rate, base, expenses) {
            if (rate === null || base === null) return null;
            const numericBase = parseFloat(base) || 0;
            const numericExpenses = parseFloat(expenses) || 0;
            const numericRate = parseFloat(rate) || 0;
            const commissionBase = Math.max(0, numericBase - numericExpenses);
            return (commissionBase * (numericRate / 100)).toFixed(2); // Arredonda para 2 casas decimais
        },

        // Função para calcular a taxa a partir do valor monetário
        calculateRateFromValue(value, base, expenses) {
            if (value === null || base === null) return null;
            const numericBase = parseFloat(base) || 0;
            const numericExpenses = parseFloat(expenses) || 0;
            const numericValue = parseFloat(value) || 0;
            const commissionBase = Math.max(0, numericBase - numericExpenses);
            if (commissionBase <= 0) return 0.00; // Evita divisão por zero, retorna 0%
            // Arredonda para 2 casas decimais
            return Math.round(((numericValue / commissionBase) * 100) * 100) / 100;
        },

        // Observa mudanças no TIPO de comissão selecionado
        watchTypeChange(newType) {
            console.log('Tipo mudou para:', newType);
            if (newType === 'percent') {
                // Mudou para Percentual: Tenta calcular a TAXA a partir do VALOR FIXO (original ou atual)
                // Se não conseguir, usa a taxa original (se existir), senão deixa vazio.
                this.commissionDisplayValue = this.calculateRateFromValue(this.commissionFixedValue ?? this.commissionDisplayValue, this.baseCacheValue, this.expensesValue) ?? this.commissionRate ?? '';
                console.log('Novo Display Value (percent):', this.commissionDisplayValue);
            } else { // Mudou para Fixo
                // Mudou para Fixo: Tenta calcular o VALOR MONETÁRIO a partir da TAXA (original ou atual)
                // Se não conseguir, usa o valor fixo original (se existir), senão deixa vazio.
                this.commissionDisplayValue = this.calculateValueFromRate(this.commissionRate ?? this.commissionDisplayValue, this.baseCacheValue, this.expensesValue) ?? this.commissionFixedValue ?? '';
                console.log('Novo Display Value (fixed):', this.commissionDisplayValue);
            }
        },

         // Observa mudanças nos valores base (Cachê, Despesas) para recalcular o valor exibido SE FOR FIXO
         watchBaseValuesChange() {
              if (this.commissionType === 'fixed') {
                  // Recalcula o valor fixo exibido baseado na taxa original salva (se existir)
                  const recalculatedFixedValue = this.calculateValueFromRate(this.commissionRate, this.baseCacheValue, this.expensesValue);
                  if (recalculatedFixedValue !== null) {
                       this.commissionDisplayValue = recalculatedFixedValue;
                  }
                  // Se não houver taxa original, mantém o valor fixo digitado.
                  // console.log('Base mudou, novo Display Value (fixed):', this.commissionDisplayValue);
              }
             // Se o tipo for 'percent', o valor no input JÁ É a taxa, não precisa mudar aqui.
         }

     }"
     x-init="
        // Inicialização já feita pelo PHP no atributo 'value'
        // Apenas define os watchers
        $watch('commissionType', (newType, oldType) => { if(newType !== oldType) watchTypeChange(newType) });
        $watch('baseCacheValue', () => watchBaseValuesChange());
        $watch('expensesValue', () => watchBaseValuesChange());
     "
>

    {{-- Linha 1: Artista, Booker, Data Evento --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Artista --}}
        <div>
            <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista <span class="text-red-500">*</span></label>
            <select id="artist_id" name="artist_id" required
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('artist_id') border-red-500 dark:border-red-600 @enderror">
                <option value="">Selecione...</option>
                @foreach($artists as $id => $name)
                    <option value="{{ $id }}" @selected(old('artist_id', $gig->artist_id) == $id)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
            @error('artist_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Booker --}}
        <div>
            <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker</label>
            <select id="booker_id" name="booker_id"
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_id') border-red-500 dark:border-red-600 @enderror">
                <option value="">Agência / Sem Booker</option>
                @foreach($bookers as $id => $name)
                     <option value="{{ $id }}" @selected(old('booker_id', $gig->booker_id) == $id)>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
             @error('booker_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Data Evento --}}
        <div>
            <label for="gig_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento <span class="text-red-500">*</span></label>
            <input type="date" id="gig_date" name="gig_date" required
                   value="{{ old('gig_date', $gig->gig_date?->format('Y-m-d')) }}"
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('gig_date') border-red-500 dark:border-red-600 @enderror">
             @error('gig_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Linha 2: Local/Evento Detalhes --}}
     <div>
        <label for="location_event_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local / Evento <span class="text-red-500">*</span></label>
        <textarea id="location_event_details" name="location_event_details" rows="2" required
                  class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('location_event_details') border-red-500 dark:border-red-600 @enderror"
        >{{ old('location_event_details', $gig->location_event_details) }}</textarea>
        @error('location_event_details') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Linha 3: Financeiro (Cachê) --}}
    {{-- Linha 3: Financeiro (Cachê) - SEM CAMPO CÂMBIO --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6"> {{-- Ajustado para 3 colunas --}}
        {{-- Cachê Bruto --}}
        <div>
            <label for="cache_value" class="block text-sm...">Cachê Bruto <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" id="cache_value" name="cache_value" required
                   x-model.number="baseCacheValue"
                   class="w-full text-sm rounded-md ...">
            @error('cache_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Moeda --}}
        <div>
            <label for="currency" class="block text-sm...">Moeda <span class="text-red-500">*</span></label>
            <select id="currency" name="currency" required class="w-full text-sm rounded-md ...">
                <option value="BRL" @selected(old('currency', $gig->currency ?? 'BRL') == 'BRL')>BRL</option>
                <option value="USD" @selected(old('currency', $gig->currency) == 'USD')>USD</option>
                <option value="EUR" @selected(old('currency', $gig->currency) == 'EUR')>EUR</option>
                <option value="GPB" @selected(old('currency', $gig->currency) == 'GPB')>GBP</option>
            </select>
             @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Despesas (BRL) --}}
         <div>
            <label for="expenses_value_brl" class="block text-sm...">Despesas (BRL)</label>
            <input type="number" step="0.01" id="expenses_value_brl" name="expenses_value_brl"
                   x-model.number="expensesValue"
                   class="w-full text-sm rounded-md ...">
             @error('expenses_value_brl') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

     {{-- Linha 4: Comissões --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        {{-- Tipo Comissão Booker --}}
        <div>
             <label for="booker_commission_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo Comissão Booker</label>
            <select id="booker_commission_type" name="booker_commission_type"
                    x-model="commissionType" {{-- Vincula ao Alpine --}}
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_type') border-red-500 dark:border-red-600 @enderror">
                <option value="percent">Percentual (%)</option>
                <option value="fixed">Valor Fixo (BRL)</option>
            </select>
             @error('booker_commission_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
         {{-- Valor/Taxa Comissão Booker --}}
         <div>
            <label for="booker_commission_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor/Taxa Comissão Booker</label>
            {{-- Input controlado pelo Alpine --}}
            <input type="number" step="0.01"
                   id="booker_commission_value"
                   name="booker_commission_value" {{-- Nome real do campo para o backend --}}
                   x-model.number="commissionDisplayValue" {{-- Alpine controla o valor exibido --}}
                   placeholder="Ex: 5.00 (%) ou 250.00 (fixo)"
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_value') border-red-500 dark:border-red-600 @enderror">
             @error('booker_commission_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Campos Comissão Agência (se houver) --}}
        <div class="md:col-span-2"></div>
    </div>

    {{-- Linha 5: Status e Contrato Formal --}}
     <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        {{-- Status Contrato --}}
        <div>
            <label for="contract_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Contrato</label>
             <select id="contract_status" name="contract_status"
                     class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_status') border-red-500 dark:border-red-600 @enderror">
                <option value="n/a" @selected(old('contract_status', $gig->contract_status ?? 'n/a') == 'n/a')>N/A (Sem Contrato)</option>
                <option value="para_assinatura" @selected(old('contract_status', $gig->contract_status) == 'para_assinatura')>Para Assinatura</option>
                <option value="assinado" @selected(old('contract_status', $gig->contract_status) == 'assinado')>Assinado</option>
                <option value="concluido" @selected(old('contract_status', $gig->contract_status) == 'concluido')>Concluído</option>
                <option value="expirado" @selected(old('contract_status', $gig->contract_status) == 'expirado')>Expirado</option>
                <option value="cancelado" @selected(old('contract_status', $gig->contract_status) == 'cancelado')>Cancelado</option>
            </select>
             @error('contract_status') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Número Contrato --}}
        <div>
             <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número Contrato (Opc)</label>
             <input type="text" id="contract_number" name="contract_number"
                    value="{{ old('contract_number', $gig->contract_number) }}"
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_number') border-red-500 dark:border-red-600 @enderror">
             @error('contract_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        {{-- Data Contrato --}}
         <div>
            <label for="contract_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Contrato (Opc)</label>
            <input type="date" id="contract_date" name="contract_date"
                   value="{{ old('contract_date', $gig->contract_date?->format('Y-m-d')) }}"
                   class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_date') border-red-500 dark:border-red-600 @enderror">
             @error('contract_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Linha 6: Tags e Notas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

         {{-- Notas --}}
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
            <textarea id="notes" name="notes" rows="4"
                      class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('notes') border-red-500 dark:border-red-600 @enderror"
            >{{ old('notes', $gig->notes) }}</textarea>
            @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
    </div>

</div>