<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                {{ __('Detalhes do Custo Operacional') }}
            </h2>
            <a href="{{ route('agency-costs.index') }}" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                {{ __('Voltar') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Informações do Custo</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Descrição</p>
                                    <p class="text-lg text-gray-900 dark:text-white">{{ $cost->description }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Centro de Custo</p>
                                    <p class="text-lg text-gray-900 dark:text-white">{{ $cost->costCenter->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Valor Mensal</p>
                                    <p class="text-lg text-gray-900 dark:text-white">R$ {{ number_format($cost->monthly_value, 2, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mês de Referência</p>
                                    <p class="text-lg text-gray-900 dark:text-white">{{ $cost->reference_month->isoFormat('MM/YYYY') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                                    <p class="text-lg">
                                        @if($cost->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Ativo
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Inativo
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
