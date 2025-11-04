<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Centro de Custo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if($costCenter->gig_costs_count > 0)
                <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    <strong>Atenção!</strong> Este centro de custo possui {{ $costCenter->gig_costs_count }} despesa(s) associada(s). A exclusão não será permitida.
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('cost-centers.update', $costCenter) }}">
                        @csrf
                        @method('PUT')
                        @include('cost-centers._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
