{{-- resources/views/gigs/_form.blade.php --}}
{{-- Variáveis esperadas: $gig, $artists, $bookers, $bookersForSelect, $bookersData, $tags, $selectedTags (opc), $costCenters, $expensesDataForView, $initialCommissionData --}}

@php
    // $currentSelectedTags ainda é útil para o select de Tags
    $currentSelectedTags = old('tags', $selectedTags ?? []);
@endphp

<div x-data="gigFormManager(
        {{ Js::from($initialCommissionData['agency_type']) }},
        {{ Js::from($initialCommissionData['agency_input_value']) }},
        {{ Js::from($initialCommissionData['booker_type']) }},
        {{ Js::from($initialCommissionData['booker_input_value']) }},
        {{ Js::from($initialCommissionData['cache_value']) }},
        {{ Js::from($bookersData ?? []) }}
    )"
    x-init="
        activeTab = parseInt(new URLSearchParams(window.location.search).get('active_tab') || {{ old('active_tab', 1) }});
        @if ($errors->any())
            let firstErrorFieldId = '';
            let errorTab = 0;
            const fieldToTabMap = {
                'artist_id': 1, 'booker_id': 1, 'gig_date': 1, 'location_event_details': 1, 'tags': 5,
                'cache_value': 2, 'currency': 2, 'contract_number': 2, 'contract_date': 2, 'contract_status': 2,
                'agency_commission_type': 3, 'agency_commission_value': 3, 'booker_commission_type': 3, 'booker_commission_value': 3,
                'expenses': 4, // Erros de expenses.*.* vão para a aba 4
                'notes': 5
            };
            const errorKeys = {{ Js::from($errors->keys()) }};
            for (let key of errorKeys) {
                let baseKey = key.split('.')[0]; // Pega a parte base (ex: 'expenses' de 'expenses.0.value')
                if (fieldToTabMap[baseKey]) {
                    errorTab = fieldToTabMap[baseKey];
                    break;
                }
                if (fieldToTabMap[key]) { // Para erros não-array
                    errorTab = fieldToTabMap[key];
                    break;
                }
            }
            if (errorTab > 0) activeTab = errorTab;
        @endif
    "
    class="p-0 md:p-0">

    {{-- Navegação das Abas --}}
    <div class="mb-0 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-t-xl">
        <nav class="-mb-px flex space-x-1 sm:space-x-4 overflow-x-auto px-4" aria-label="Tabs">
            @php $tabs = ['Principais', 'Financeiro', 'Comissões', 'Despesas', 'Notas & Tags']; @endphp
            @foreach($tabs as $index => $tabName)
                @php $tabId = $index + 1; @endphp
                <button type="button"
                        @click="activeTab = {{ $tabId }}; const url = new URL(window.location); url.searchParams.set('active_tab', {{ $tabId }}); window.history.replaceState({}, '', url);"
                        :class="{
                            'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-300': activeTab === {{ $tabId }},
                            'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:border-gray-500': activeTab !== {{ $tabId }}
                        }"
                        class="whitespace-nowrap py-3 px-1 sm:px-4 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-150 ease-in-out">
                    {{ $tabName }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Input hidden para submeter a aba ativa e o helper old() funcionar --}}
    <input type="hidden" name="active_tab" x-model="activeTab">

    {{-- Conteúdo das Abas --}}
    <div class="py-6 px-2 sm:px-6 space-y-6">
        {{-- Aba 1: Informações Principais --}}
        <div x-show="activeTab === 1" role="tabpanel" id="tab-panel-1" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Informações Principais</h3>
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
                <select id="booker_id" name="booker_id" x-model="selectedBookerId" @change="onBookerChange()"
                        class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_id') border-red-500 dark:border-red-600 @enderror">
                    <option value="">Agência / Sem Booker</option>
                    @foreach($bookersForSelect as $id => $name)
                         <option value="{{ $id }}" @selected(old('booker_id', $gig->booker_id) == $id)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                 @error('booker_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Tomador de Serviço (para Nota de Débito) --}}
            <div>
                <label for="service_taker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tomador de Serviço <span class="text-xs text-gray-400 font-normal">(para Nota de Débito)</span>
                </label>
                <select id="service_taker_id" name="service_taker_id"
                        class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('service_taker_id') border-red-500 dark:border-red-600 @enderror">
                    <option value="">Selecione...</option>
                    @if(isset($serviceTakers))
                        @foreach($serviceTakers as $id => $name)
                            <option value="{{ $id }}" @selected(old('service_taker_id', $gig->service_taker_id) == $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('service_taker_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Data Evento --}}
            <div>
                <label for="gig_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento <span class="text-red-500">*</span></label>
                <input type="date" id="gig_date" name="gig_date" required
                       value="{{ old('gig_date', $gig->gig_date?->format('Y-m-d')) }}"
                       class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('gig_date') border-red-500 dark:border-red-600 @enderror">
                 @error('gig_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Local/Evento Detalhes --}}
             <div>
                <label for="location_event_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local / Descrição do Evento <span class="text-red-500">*</span></label>
                <textarea id="location_event_details" name="location_event_details" rows="3" required
                          class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('location_event_details') border-red-500 dark:border-red-600 @enderror"
                >{{ old('location_event_details', $gig->location_event_details) }}</textarea>
                @error('location_event_details') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Aba 2: Financeiro (Cachê e Contrato) --}}
        <div x-show="activeTab === 2" role="tabpanel" id="tab-panel-2" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Financeiro (Cachê e Contrato)</h3>
            {{-- Cachê Bruto --}}
            <div>
                <label for="cache_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor do Contrato (Cachê Original) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="cache_value" name="cache_value" required
                       x-model.number="baseCacheValueForCommissions" {{-- Usar este para o Alpine --}}
                       class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('cache_value') border-red-500 dark:border-red-600 @enderror">
                @error('cache_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Moeda --}}
            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda <span class="text-red-500">*</span></label>
                <select id="currency" name="currency" required
                        class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('currency') border-red-500 dark:border-red-600 @enderror">
                    <option value="BRL" @selected(old('currency', $gig->currency ?? 'BRL') == 'BRL')>BRL</option>
                    <option value="USD" @selected(old('currency', $gig->currency) == 'USD')>USD</option>
                    <option value="EUR" @selected(old('currency', $gig->currency) == 'EUR')>EUR</option>
                    <option value="GBP" @selected(old('currency', $gig->currency) == 'GBP')>GBP</option>
                </select>
                @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <hr class="dark:border-gray-700">
             {{-- Número Contrato --}}
            <div>
                 <label for="contract_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número Contrato (Opcional)</label>
                 <input type="text" id="contract_number" name="contract_number"
                        value="{{ old('contract_number', $gig->contract_number) }}"
                        class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_number') border-red-500 dark:border-red-600 @enderror">
                 @error('contract_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Data Contrato --}}
             <div>
                <label for="contract_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Contrato (Opcional)</label>
                <input type="date" id="contract_date" name="contract_date"
                       value="{{ old('contract_date', $gig->contract_date?->format('Y-m-d')) }}"
                       class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('contract_date') border-red-500 dark:border-red-600 @enderror">
                 @error('contract_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
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
        </div>

        {{-- Aba 3: Comissões --}}
        <div x-show="activeTab === 3" role="tabpanel" id="tab-panel-3" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Comissões</h3>
            {{-- Comissão Agência --}}
            <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2">Comissão da Agência</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                    <div>
                        <label for="agency_commission_type_select" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                        <select id="agency_commission_type_select" name="agency_commission_type" x-model="agencyType"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('agency_commission_type') border-red-500 dark:border-red-600 @enderror">
                            <option value="percent">Percentual (%)</option>
                            <option value="fixed">Valor Fixo (BRL)</option>
                        </select>
                        @error('agency_commission_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="agency_commission_value_input" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="agencyType === 'percent' ? 'Taxa (%)' : 'Valor Fixo (R$)'"></label>
                        <input type="number" step="0.01" id="agency_commission_value_input" name="agency_commission_value" x-model.number="agencyDisplayValue"
                               :placeholder="agencyType === 'percent' ? 'Ex: 20.00' : 'Ex: 500.00'"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('agency_commission_value') border-red-500 dark:border-red-600 @enderror">
                        @error('agency_commission_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                           Valor Estimado: <span x-text="formatCurrencyForDisplay(calculatedAgencyCommissionEstimate)"></span>
                        </p>
                    </div>
                </div>
            </fieldset>

            {{-- Comissão Booker --}}
            <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2">Comissão do Booker (Opcional)</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                    <div>
                        <label for="booker_commission_type_select" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                        <select id="booker_commission_type_select" name="booker_commission_type" x-model="bookerType"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_type') border-red-500 dark:border-red-600 @enderror">
                             <option value="">Nenhuma</option> {{-- Adicionado para desmarcar --}}
                            <option value="percent">Percentual (%)</option>
                            <option value="fixed">Valor Fixo (BRL)</option>
                        </select>
                        @error('booker_commission_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="booker_commission_value_input" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="bookerType === 'percent' ? 'Taxa (%)' : (bookerType === 'fixed' ? 'Valor Fixo (BRL)' : 'Valor/Taxa')"></label>
                        <input type="number" step="0.01" id="booker_commission_value_input" name="booker_commission_value"
                               x-model.number="bookerDisplayValue"
                               :placeholder="bookerType === 'percent' ? 'Ex: 5.00' : (bookerType === 'fixed' ? 'Ex: 250.00' : 'Defina o tipo')"
                               :disabled="!bookerType"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_value') border-red-500 dark:border-red-600 @enderror">
                        @error('booker_commission_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="bookerType && bookerDisplayValue">
                            Valor Estimado: <span x-text="formatCurrencyForDisplay(calculatedBookerCommissionEstimate)"></span>
                        </p>
                    </div>
                </div>
            </fieldset>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Nota: A comissão da Agência é calculada sobre o "Valor do Contrato" MENOS as "Despesas Pagas pela Agência". A comissão do Booker é calculada sobre o "Valor do Contrato" original. O valor final das comissões será sempre calculado e salvo no backend.
            </p>
        </div>

        {{-- Aba 4: Despesas Previstas --}}
        <div x-show="activeTab === 4" role="tabpanel" id="tab-panel-4" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Despesas Previstas</h3>
            {{-- O @include para _expenses_form já foi corrigido para usar Js::from na resposta anterior --}}
            @include('gigs._expenses_form', [
                'gig' => $gig,
                'costCenters' => $costCenters,
                'expensesDataForView' => $expensesDataForView ?? []
            ])
        </div>

        {{-- Aba 5: Notas & Tags --}}
        <div x-show="activeTab === 5" role="tabpanel" id="tab-panel-5" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Notas</h3>
            {{-- Tags (já estava na Aba 1, movido para cá para melhor organização) --}}
            
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas Gerais da Gig (Opcional)</label>
                <textarea id="notes" name="notes" rows="5"
                          class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('notes') border-red-500 dark:border-red-600 @enderror"
                >{{ old('notes', $gig->notes) }}</textarea>
                @error('notes') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>

{{-- Script para o gigFormManager e TomSelect --}}
@pushOnce('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet"> {{-- Ou o tema padrão se preferir --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        // Se você quiser usar TomSelect para outros selects além de #tags,
        // adicione a inicialização deles aqui também.
        // O TomSelect para #tags é melhor inicializar aqui, APÓS o Alpine ter renderizado o DOM inicial.
        const tagsElement = document.getElementById('tags');
        if (tagsElement) {
            new TomSelect(tagsElement,{
                plugins: ['remove_button'],
                create: false, // Mudar para true se quiser permitir criar novas tags dinamicamente
                // placeholder: 'Selecione ou digite para buscar tags...'
            });
        }
    });

    function gigFormManager(agencyTypeInitial, agencyValueInitial, bookerTypeInitial, bookerValueInitial, cacheValueInitial, bookersData) {
        return {
            activeTab: parseInt(new URLSearchParams(window.location.search).get('active_tab') || {{ old('active_tab', 1) }}),
            agencyType: agencyTypeInitial,
            agencyDisplayValue: agencyValueInitial,
            bookerType: bookerTypeInitial,
            bookerDisplayValue: bookerValueInitial,
            baseCacheValueForCommissions: parseFloat(cacheValueInitial) || 0, // Input do cachê original da gig
            bookersData: bookersData || {},
            selectedBookerId: '{{ old('booker_id', $gig->booker_id) }}',

            // Estimativa da base de comissão para a Agência (Cachê Original - Despesas da Agência)
            // Esta é uma estimativa visual, o cálculo real é no backend.
            // Para uma estimativa mais precisa aqui, precisaríamos do total das despesas da agência do expensesManager.
            // Por enquanto, a nota no form explica que o cálculo final é no backend.
            get commissionBaseEstimateBRLForAgency() {
                // Simplificação: no form, a comissão da agência é mostrada sobre o "Valor do Contrato"
                // A dedução de despesas é complexa de fazer em tempo real entre componentes Alpine separados.
                // A nota explicativa no formulário é crucial.
                return this.baseCacheValueForCommissions;
            },

            get calculatedAgencyCommissionEstimate() {
                if (this.agencyType === 'percent') {
                    return (this.commissionBaseEstimateBRLForAgency * (parseFloat(this.agencyDisplayValue) || 0)) / 100;
                }
                return parseFloat(this.agencyDisplayValue) || 0;
            },

            get calculatedBookerCommissionEstimate() {
                // Booker é sobre o Valor Contrato Original (baseCacheValueForCommissions)
                if (this.bookerType === 'percent') {
                    return (this.baseCacheValueForCommissions * (parseFloat(this.bookerDisplayValue) || 0)) / 100;
                }
                return parseFloat(this.bookerDisplayValue) || 0;
            },
            
            formatCurrencyForDisplay(value) {
                const num = parseFloat(value);
                if (isNaN(num)) return 'R$ 0,00';
                return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            },

            onBookerChange() {
                if (this.selectedBookerId && this.bookersData[this.selectedBookerId]) {
                    const booker = this.bookersData[this.selectedBookerId];
                    if (booker.default_commission_rate) {
                        // Atribui automaticamente a taxa padrão do booker
                        this.bookerType = 'percent';
                        this.bookerDisplayValue = parseFloat(booker.default_commission_rate);
                    }
                } else {
                    // Se não há booker selecionado, limpa os campos de comissão
                    this.bookerType = '';
                    this.bookerDisplayValue = null;
                }
            },

            init() {
                //console.log('gigFormManager init');
                //console.log('Initial Agency Type:', this.agencyType, 'Initial Agency Value:', this.agencyDisplayValue);
                //console.log('Initial Booker Type:', this.bookerType, 'Initial Booker Value:', this.bookerDisplayValue);
                //console.log('Initial Cache Value:', this.baseCacheValueForCommissions);
                //console.log('Bookers Data:', this.bookersData);

                this.$watch('activeTab', value => { // Atualiza URL ao mudar de aba
                    const url = new URL(window.location);
                    url.searchParams.set('active_tab', value);
                    window.history.replaceState({}, '', url);
                });
            }
        };
    }
</script>
@endPushOnce