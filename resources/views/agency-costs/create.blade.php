<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Adicionar Custo Operacional') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('agency-costs.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Description -->
                            <div>
                                <label for="description" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Descrição</label>
                                <input type="text" name="description" id="description" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            </div>

                            <!-- Cost Center -->
                            <div>
                                <label for="cost_center_id" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Centro de Custo</label>
                                <select name="cost_center_id" id="cost_center_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                                    @foreach ($costCenters as $costCenter)
                                        <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Monthly Value -->
                            <div>
                                <label for="monthly_value" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Valor Mensal (BRL)</label>
                                <input type="number" name="monthly_value" id="monthly_value" step="0.01" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            </div>

                            <!-- Reference Month -->
                            <div>
                                <label for="reference_month" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Mês de Referência</label>
                                <input type="month" name="reference_month" id="reference_month" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-2">
                                <label for="notes" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Observações</label>
                                <textarea name="notes" id="notes" rows="3" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                            </div>

                            <!-- Is Active -->
                            <div class="block">
                                <label for="is_active" class="inline-flex items-center">
                                    <input type="hidden" name="is_active" value="0">
                                    <input id="is_active" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500" name="is_active" value="1" checked>
                                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Ativo') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('agency-costs.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 me-4">
                                Cancelar
                            </a>
                            <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">
                                {{ __('Salvar Custo') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
