<x-app-layout>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Adicionar Despesa à Gig #{{ $gig->id }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->artist->name ?? 'N/A' }} em {{ $gig->gig_date->format('d/m/Y') }}</p>
        </div>
        {{-- Botão Voltar aponta para a GIG --}}
        <a href="{{ route('gigs.show', $gig) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para Detalhes da Gig
       </a>
    </div>

    {{-- Card com Formulário --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('gigs.costs.store', $gig) }}" method="POST">
            @csrf

            {{-- Inclui o formulário parcial. Passa um objeto GigCost novo. --}}
            {{-- Note que $costCenters e $gig são passados pelo controller create --}}
            @include('gig_costs._form', [
                'cost' => $cost, // $cost é passado pelo controller create (new GigCost)
                'gig' => $gig,
                'costCenters' => $costCenters,
                'formType' => 'create' // Para diferenciar no _form se necessário
            ])

            {{-- Rodapé com Botões --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end items-center space-x-3">
                 <a href="{{ route('gigs.show', $gig) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
                    Cancelar
                </a>
                <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">
                    <i class="fas fa-save mr-1"></i> Salvar Despesa
                </button>
            </div>
        </form>
    </div>
</x-app-layout>