{{-- resources/views/gigs/_expenses_form.blade.php --}}
@props(['gig', 'costCenters']) {{-- Adicionado $gig como prop se necessário para 'old' --}}

<div x-data="{
    costCenters: {{ json_encode($costCenters ?? []) }},
    expenses: {{ old('expenses', ($gig->exists && $gig->costs ? $gig->costs->map(fn($cost) => [
        'id' => $cost->id,
        'cost_center_id' => (string)($cost->cost_center_id ?? ''), // Garantir string e default
        'description' => $cost->description ?? '',
        'value' => $cost->value ?? '', // Default para string vazia se nulo
        'currency' => $cost->currency ?: 'BRL',
        'expense_date' => $cost->expense_date ? $cost->expense_date->format('Y-m-d') : now()->format('Y-m-d'),
        'notes' => $cost->notes ?? '',
        'is_confirmed' => (bool)($cost->is_confirmed ?? false),
        'is_invoice' => (bool)($cost->is_invoice ?? false),
        '_deleted' => false
    ])->toJson() : '[]')) }}, // old() para repopular em caso de erro de validação
    totalExpensesValue: 0,

    init() {
        this.calculateTotalExpenses();
        // Se for uma criação de Gig (sem $gig->exists ou sem old('expenses')),
        // e você quiser que comece com uma linha de despesa em branco:
        if (!{{ $gig->exists ? 'true' : 'false' }} && this.expenses.length === 0 && !{{ count(old('expenses', [])) > 0 ? 'true' : 'false' }}) {
            // this.addExpense(); // Descomente se quiser uma linha ao criar nova Gig
        }
    },

    addExpense() {
        this.expenses.push({
            id: null,
            cost_center_id: '', // Default para string vazia
            description: '',
            value: '',
            currency: 'BRL',
            expense_date: '{{ now()->format('Y-m-d') }}',
            notes: '',
            is_confirmed: false,
            is_invoice: false,
            _deleted: false
        });
        this.$nextTick(() => {
            const lastIndex = this.expenses.length - 1;
            const firstInput = document.getElementById(`expenses[${lastIndex}][cost_center_id]`);
            firstInput?.focus();
        });
        this.calculateTotalExpenses(); // Recalcular ao adicionar
    },

    removeExpense(index) {
        // Se a despesa tem um ID, ela existe no banco. Marcamos para deleção LÓGICA no backend.
        // Se não tem ID, é uma linha nova no formulário, apenas removemos do array.
        if (this.expenses[index].id) {
            if (confirm('Tem certeza que deseja marcar esta despesa salva para remoção? A remoção efetiva ocorrerá ao salvar a Gig.')) {
                this.expenses[index]._deleted = true; // Marca para o backend
                // Visualmente, você pode querer esconder ou estilizar diferente:
                // Ex: document.getElementById(`expense_row_${index}`).style.display = 'none';
                // Ou adicionar uma classe para tachar o texto, etc.
                // Por ora, o _deleted=true fará o backend ignorá-la para update e potencialmente deletá-la.
            }
        } else {
            this.expenses.splice(index, 1);
        }
        this.calculateTotalExpenses();
    },

    calculateTotalExpenses() {
        this.totalExpensesValue = this.expenses.reduce((sum, expense) => {
            if (expense._deleted) return sum; // Não soma se marcada para exclusão
            return sum + (parseFloat(expense.value) || 0);
        }, 0);
    },

    formatCurrency(value) {
        return parseFloat(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}" class="space-y-4">

    <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-200 dark:border-gray-700">
        <h4 class="text-md font-semibold text-gray-800 dark:text-white">
            Despesas da Gig
        </h4>
        <button type="button" @click="addExpense()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs flex items-center">
            <i class="fas fa-plus mr-1"></i> Adicionar Despesa
        </button>
    </div>

    <div x-show="expenses.length > 0 && expenses.filter(e => !e._deleted).length > 0" class="mb-3 text-sm text-gray-700 dark:text-gray-200">
        Total Previsto das Despesas (não excluídas): <strong class="text-primary-600 dark:text-primary-400" x-text="`R$ ${formatCurrency(totalExpensesValue)}`"></strong>
    </div>

    <template x-if="expenses.length === 0 || expenses.filter(e => !e._deleted).length === 0">
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma despesa adicionada para esta Gig.</p>
    </template>

    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
        <template x-for="(expense, index) in expenses" :key="index">
            {{-- Adiciona um ID à div da linha para manipulação visual se necessário --}}
            <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-700/30 relative"
                 :id="`expense_row_${index}`"
                 :class="{ 'opacity-60 !bg-red-50 dark:!bg-red-900/20': expense._deleted }">

                {{-- Campo hidden para marcar como deletado --}}
                <input type="hidden" :name="'expenses['+index+'][_deleted]'" x-model="expense._deleted">
                {{-- Campo hidden para o ID da despesa (se for uma existente) --}}
                <input type="hidden" :name="'expenses['+index+'][id]'" x-model="expense.id">


                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3">
                    {{-- Centro de Custo --}}
                    <div>
                        <label :for="'expense_cost_center_id_'+index" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Centro de Custo <span class="text-red-500">*</span></label>
                        <select :name="'expenses['+index+'][cost_center_id]'" :id="'expense_cost_center_id_'+index" x-model="expense.cost_center_id" required
                                :disabled="expense._deleted"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                :class="{ 'border-red-500 dark:border-red-600': $store.errors?.has('expenses.'+index+'.cost_center_id') }"> {{-- Exemplo para erro --}}
                            <option value="">Selecione...</option>
                            <template x-for="(name, id_key) in costCenters" :key="id_key"> {{-- Usar id_key para evitar conflito com id da despesa --}}
                                <option :value="id_key" x-text="name"></option>
                            </template>
                        </select>
                        {{-- Exemplo de como exibir erro específico do array com Alpine (requer adaptação do error handling) --}}
                        {{-- <template x-if="$store.errors?.has('expenses.'+index+'.cost_center_id')">
                            <p class="mt-1 text-xs text-red-500" x-text="$store.errors.get('expenses.'+index+'.cost_center_id')[0]"></p>
                        </template> --}}
                        @error('expenses.*.cost_center_id') {{-- Este é um erro geral para todos os cost_center_id --}}
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Descrição --}}
                    <div>
                        <label :for="'expense_description_'+index" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Descrição</label>
                        <input type="text" :name="'expenses['+index+'][description]'" :id="'expense_description_'+index" x-model="expense.description"
                               :disabled="expense._deleted"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               placeholder="Detalhes da despesa">
                    </div>

                    {{-- Valor --}}
                    <div>
                        <label :for="'expense_value_'+index" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Valor <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" :name="'expenses['+index+'][value]'" :id="'expense_value_'+index" x-model.number="expense.value" @input="calculateTotalExpenses()" required
                               :disabled="expense._deleted"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                               :class="{ 'border-red-500 dark:border-red-600': $store.errors?.has('expenses.'+index+'.value') }">
                        @error('expenses.*.value')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Moeda --}}
                    <div>
                        <label :for="'expense_currency_'+index" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Moeda</label>
                        <select :name="'expenses['+index+'][currency]'" :id="'expense_currency_'+index" x-model="expense.currency"
                                :disabled="expense._deleted"
                                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="BRL">BRL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>

                    {{-- Data da Despesa --}}
                    <div>
                        <label :for="'expense_expense_date_'+index" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Data Despesa</label>
                        <input type="date" :name="'expenses['+index+'][expense_date]'" :id="'expense_expense_date_'+index" x-model="expense.expense_date"
                               :disabled="expense._deleted"
                               class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>

                    {{-- Notas da Despesa --}}
                    <div class="md:col-span-1 lg:col-span-1">
                        <label :for="'expense_notes_'+index" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">Notas</label>
                        <textarea :name="'expenses['+index+'][notes]'" :id="'expense_notes_'+index" x-model="expense.notes" rows="1"
                                  :disabled="expense._deleted"
                                  class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                  placeholder="Info adicional..."></textarea>
                    </div>

                    {{-- Checkboxes de Confirmação e NF --}}
                    <div class="md:col-span-2 lg:col-span-3 flex items-center space-x-4 pt-2 mt-2 border-t border-gray-100 dark:border-gray-600">
                        <div class="flex items-center">
                            <input type="checkbox" :name="'expenses['+index+'][is_confirmed]'" :id="'expense_is_confirmed_'+index" x-model="expense.is_confirmed" value="1"
                                   :disabled="expense._deleted"
                                   class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label :for="'expense_is_confirmed_'+index" class="ml-2 block text-xs font-medium text-gray-700 dark:text-gray-300">Confirmada pela Agência?</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" :name="'expenses['+index+'][is_invoice]'" :id="'expense_is_invoice_'+index" x-model="expense.is_invoice" value="1"
                                   :disabled="expense._deleted"
                                   class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label :for="'expense_is_invoice_'+index" class="ml-2 block text-xs font-medium text-gray-700 dark:text-gray-300">Reembolsável via NF do Artista?</label>
                        </div>
                    </div>
                </div>

                <button type="button" @click="removeExpense(index)" title="Remover Despesa"
                        :disabled="expense._deleted"
                        class="absolute top-2 right-2 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1 rounded-full hover:bg-red-100 dark:hover:bg-red-800/50 transition-colors"
                        :class="{'opacity-50 cursor-not-allowed': expense._deleted}">
                    <i class="fas fa-times fa-sm"></i>
                </button>
            </div>
        </template>
    </div>
</div>