{{-- resources/views/gigs/_form.blade.php --}}
{{--
    Formulário parcial para Gigs (usado em create e edit) com ABAS.
    Recebe as variáveis:
    - $gig (Objeto Gig, vazio para create, preenchido para edit)
    - $artists (Array/Collection de artistas para select)
    - $bookers (Array/Collection de bookers para select)
    - $tags (Array/Collection agrupada de tags para select)
    - $selectedTags (Array de IDs das tags selecionadas para edit/old)
    - $costCenters (Array/Collection de centros de custo para o form de despesas)
--}}
@php
    $currentSelectedTags = old('tags', $selectedTags ?? []);

    // Valores iniciais para Alpine (comissões)
    $initialAgencyType = old('agency_commission_type', $gig->agency_commission_type ?? 'percent');
    $initialAgencyRate = old('agency_commission_rate', $gig->agency_commission_rate);
    $initialAgencyFixedValue = old('agency_commission_value', $initialAgencyType === 'fixed' ? $gig->agency_commission_value : null); // Correção: só pega se for fixed
    $initialAgencyDisplayValue = $initialAgencyType === 'percent' ? ($initialAgencyRate ?? ($gig->exists ? $gig->agency_commission_rate : 20.00) ) : $initialAgencyFixedValue;


    $initialBookerType = old('booker_commission_type', $gig->booker_commission_type ?? 'percent');
    $initialBookerRate = old('booker_commission_rate', $gig->booker_commission_rate);
    $initialBookerFixedValue = old('booker_commission_value', $initialBookerType === 'fixed' ? $gig->booker_commission_value : null); // Correção
    $initialBookerDisplayValue = $initialBookerType === 'percent' ? ($initialBookerRate ?? ($gig->exists && $gig->booker_id ? $gig->booker_commission_rate : 5.00) ) : $initialBookerFixedValue;


    $initialCacheValue = old('cache_value', $gig->cache_value);
    // expenses_value_brl foi removido do modelo Gig, mas o form de despesas dinâmicas o substitui.
    // Para a lógica Alpine de cálculo de comissão, vamos precisar do total das despesas dinâmicas.
    // Isso será gerenciado dentro do x-data das despesas.
@endphp

<div x-data="{
        activeTab: 1,
        // Valores da Agência
        agencyType: '{{ $initialAgencyType }}',
        agencyDisplayValue: {{ $initialAgencyDisplayValue ?? 'null' }},

        // Valores do Booker
        bookerType: '{{ $initialBookerType }}',
        bookerDisplayValue: {{ $initialBookerDisplayValue ?? 'null' }},

        // Valores base para cálculo (usados pelos watchers, atualizados pelo x-model dos inputs)
        baseCacheValue: {{ $initialCacheValue ?? 0 }},
        // O total das despesas virá do x-data do _expenses_form.blade.php
        // Vamos precisar de uma forma de comunicar esse total para cá, ou recalcular a base de comissão
        // dentro do contexto dos inputs de comissão. Por simplicidade, vamos assumir que o cálculo
        // da base de comissão nos accessors do modelo Gig já considera as despesas corretamente
        // e que o GigObserver fará o cálculo final ao salvar.
        // O JavaScript aqui é mais para UX de mostrar a taxa ou valor.

        // Função para UX do input de comissão: mostrar taxa ou valor
        updateDisplayValue(commissionPrefix) {
            const type = this[commissionPrefix + 'Type'];
            const displayValueInput = document.getElementById(commissionPrefix + '_commission_value'); // O input que sempre existe
            
            if (type === 'percent') {
                // Se mudou para percentual, tentamos manter o valor que estava no campo como taxa
                // Se o valor no campo for muito alto para ser uma taxa, pode-se limpar ou usar um default.
                // A validação no backend cuidará do max:100.
                // this[commissionPrefix + 'DisplayValue'] = parseFloat(displayValueInput.value) || (commissionPrefix === 'agency' ? 20 : 5);
            } else { // fixed
                // Se mudou para fixo, não precisamos converter nada aqui, o usuário digita o valor fixo.
                // this[commissionPrefix + 'DisplayValue'] = parseFloat(displayValueInput.value) || null;
            }
            // O importante é que o NOME do input enviado ('agency_commission_value' ou 'booker_commission_value')
            // contenha o valor que o usuário digitou, e o tipo (percent/fixed) também seja enviado.
            // O prepareGigData no controller/observer fará a lógica de qual campo popular (rate ou value).
        }
     }"
     x-init="
        $watch('agencyType', (newType) => updateDisplayValue('agency'));
        $watch('bookerType', (newType) => updateDisplayValue('booker'));

        // Lógica para abrir aba com erro (simplificado)
        @if ($errors->any())
            let firstErrorFieldId = '';
            @foreach ($errors->keys() as $key)
                @if (!$loop->first) firstErrorFieldId = '{{ $key }}'; @break @endif
            @endforeach
            if (firstErrorFieldId) {
                const errorField = document.getElementById(firstErrorFieldId);
                if (errorField) {
                    const tabPane = errorField.closest('[role=tabpanel]');
                    if (tabPane) {
                        activeTab = parseInt(tabPane.id.replace('tab-panel-', ''));
                    }
                }
            }
        @endif
     "
     class="p-0 md:p-0"> {{-- Removido padding para o container das abas ocupar todo o espaço --}}

    <!-- Navegação das Abas -->
    <div class="mb-0 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-t-xl">
        <nav class="-mb-px flex space-x-1 sm:space-x-4 overflow-x-auto px-4" aria-label="Tabs">
            @php $tabs = ['Principais', 'Financeiro', 'Comissões', 'Despesas', 'Notas']; @endphp
            @foreach($tabs as $index => $tabName)
                @php $tabId = $index + 1; @endphp
                <button type="button"
                        @click="activeTab = {{ $tabId }}"
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

    <!-- Conteúdo das Abas -->
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
            {{-- Local/Evento Detalhes --}}
             <div>
                <label for="location_event_details" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Local / Descrição do Evento <span class="text-red-500">*</span></label>
                <textarea id="location_event_details" name="location_event_details" rows="3" required
                          class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('location_event_details') border-red-500 dark:border-red-600 @enderror"
                >{{ old('location_event_details', $gig->location_event_details) }}</textarea>
                @error('location_event_details') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Tags --}}
            <div>
                <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags (Opcional)</label>
                 <select name="tags[]" id="tags" multiple
                         class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('tags.*') border-red-500 dark:border-red-600 @enderror tomselect-tags">
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
                 <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Segure Ctrl/Cmd para selecionar várias ou comece a digitar.</p>
                 @error('tags.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                 @error('tags') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Aba 2: Financeiro (Cachê e Contrato) --}}
        <div x-show="activeTab === 2" role="tabpanel" id="tab-panel-2" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Financeiro (Cachê e Contrato)</h3>
            {{-- Cachê Bruto --}}
            <div>
                <label for="cache_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cachê Bruto <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" id="cache_value" name="cache_value" required
                       x-model.number="baseCacheValue"
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
                    <option value="GPB" @selected(old('currency', $gig->currency) == 'GPB')>GBP</option>
                </select>
                @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            {{-- Campo de Câmbio removido do cadastro inicial --}}
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
                        <label for="agency_commission_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                        <select id="agency_commission_type" name="agency_commission_type" x-model="agencyType"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('agency_commission_type') border-red-500 dark:border-red-600 @enderror">
                            <option value="percent">Percentual (%)</option>
                            <option value="fixed">Valor Fixo (BRL)</option>
                        </select>
                        @error('agency_commission_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="agency_commission_value" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="agencyType === 'percent' ? 'Taxa (%)' : 'Valor Fixo (BRL)'"></label>
                        <input type="number" step="0.01" id="agency_commission_value" name="agency_commission_value"
                               x-model.number="agencyDisplayValue"
                               :placeholder="agencyType === 'percent' ? 'Ex: 20.00' : 'Ex: 500.00'"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('agency_commission_value') border-red-500 dark:border-red-600 @enderror">
                        @error('agency_commission_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="agencyType === 'percent' && agencyDisplayValue">
                            Valor Estimado: R$ <span x-text="calculateValueFromRate(agencyDisplayValue, baseCacheValue, {{ optional($gig->expenses)->sum('value') ?? 0 }})"></span>
                         </p>
                    </div>
                </div>
            </fieldset>

            {{-- Comissão Booker --}}
            <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                <legend class="text-sm font-medium text-gray-700 dark:text-gray-300 px-2">Comissão do Booker (Opcional)</legend>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                    <div>
                        <label for="booker_commission_type" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                        <select id="booker_commission_type" name="booker_commission_type" x-model="bookerType"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_type') border-red-500 dark:border-red-600 @enderror">
                             <option value="">Nenhuma</option>
                            <option value="percent">Percentual (%)</option>
                            <option value="fixed">Valor Fixo (BRL)</option>
                        </select>
                        @error('booker_commission_type') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="booker_commission_value" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" x-text="bookerType === 'percent' ? 'Taxa (%)' : (bookerType === 'fixed' ? 'Valor Fixo (BRL)' : 'Valor/Taxa')"></label>
                        <input type="number" step="0.01" id="booker_commission_value" name="booker_commission_value"
                               x-model.number="bookerDisplayValue"
                               :placeholder="bookerType === 'percent' ? 'Ex: 5.00' : (bookerType === 'fixed' ? 'Ex: 250.00' : 'Defina o tipo')"
                               :disabled="!bookerType"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('booker_commission_value') border-red-500 dark:border-red-600 @enderror">
                        @error('booker_commission_value') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                         <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-show="bookerType === 'percent' && bookerDisplayValue">
                            Valor Estimado: R$ <span x-text="calculateValueFromRate(bookerDisplayValue, baseCacheValue, {{ optional($gig->expenses)->sum('value') ?? 0 }})"></span>
                         </p>
                    </div>
                </div>
            </fieldset>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Nota: As comissões percentuais são calculadas sobre o "Cachê Bruto" MENOS as "Despesas Confirmadas" (que serão adicionadas na próxima aba).
                O valor final das comissões será calculado e salvo no backend.
            </p>
        </div>

        {{-- Aba 4: Despesas Previstas --}}
        <div x-show="activeTab === 4" role="tabpanel" id="tab-panel-4" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Despesas Previstas</h3>
            @include('gigs._expenses_form', ['costCenters' => $costCenters ?? \App\Models\CostCenter::orderBy('name')->pluck('name', 'id'), 'gig' => $gig])
        </div>

        {{-- Aba 5: Notas Adicionais --}}
        <div x-show="activeTab === 5" role="tabpanel" id="tab-panel-5" class="space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white sr-only">Notas Adicionais</h3>
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

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    // O evento 'alpine:init' é útil se você estiver definindo componentes Alpine globais (Alpine.data).
    // Como a lógica principal do formulário de Gigs já está em um x-data inline no _form.blade.php,
    // este bloco pode não ser estritamente necessário A MENOS QUE TomSelect precise que o Alpine
    // já esteja totalmente inicializado, o que geralmente não é o caso para bibliotecas externas.
    // Manter o DOMContentLoaded é mais seguro para garantir que o HTML do select já exista.

    document.addEventListener('DOMContentLoaded', function () {
        // Inicializa TomSelect para o campo de tags
        const tagsElement = document.getElementById('tags');
        if (tagsElement) {
            new TomSelect(tagsElement,{
                plugins: ['remove_button'],
                create: false,
                // placeholder: 'Selecione ou digite para buscar tags...'
            });
        }

        // Exemplo de como você poderia inicializar TomSelect para outros selects se desejado:
        // const artistSelect = document.getElementById('artist_id');
        // if (artistSelect) {
        //     new TomSelect(artistSelect, { /* opções se necessário */ });
        // }

        // const bookerSelect = document.getElementById('booker_id');
        // if (bookerSelect) {
        //     new TomSelect(bookerSelect, { allowEmptyOption: true /* para a opção "Sem Booker" */ });
        // }
    });
</script>
@endpush