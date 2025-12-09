{{-- Partial de ações para cada despesa --}}
@php
    $stage = $expense->reimbursement_stage;
@endphp

<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="p-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400">
        <i class="fas fa-ellipsis-v"></i>
    </button>

    <div x-show="open" @click.away="open = false" x-transition
         class="absolute right-0 z-10 mt-1 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">
        
        {{-- Ver Gig --}}
        <a href="{{ route('gigs.show', $expense->gig_id) }}" 
           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
            <i class="fas fa-eye w-5 text-gray-400"></i>
            Ver Evento
        </a>

        @switch($stage)
            @case('aguardando_comprovante')
                {{-- Ação: Registrar Comprovante --}}
                <button type="button" 
                        @click="$dispatch('open-receive-proof-modal', { costId: {{ $expense->id }}, description: '{{ addslashes($expense->description) }}', value: {{ $expense->value }} })"
                        class="w-full flex items-center px-4 py-2 text-sm text-yellow-600 dark:text-yellow-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-file-upload w-5"></i>
                    Registrar Comprovante
                </button>
                @break

            @case('comprovante_recebido')
                {{-- Ação: Marcar como Pago (sem conferência intermediária) --}}
                <form action="{{ route('expenses.reimbursements.reimburse', $expense) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-check-circle w-5"></i>
                        Marcar como Pago
                    </button>
                </form>

                {{-- Reverter --}}
                <form action="{{ route('expenses.reimbursements.revert', $expense) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-undo w-5"></i>
                        Reverter
                    </button>
                </form>
                @break

            @case('conferido')
                {{-- Ação: Marcar como Pago --}}
                <form action="{{ route('expenses.reimbursements.reimburse', $expense) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-green-600 dark:text-green-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-check-circle w-5"></i>
                        Marcar como Pago
                    </button>
                </form>

                {{-- Reverter --}}
                <form action="{{ route('expenses.reimbursements.revert', $expense) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-undo w-5"></i>
                        Reverter
                    </button>
                </form>
                @break

            @case('reembolsado')
                {{-- Info: Ver Comprovante (se existir) --}}
                @if($expense->reimbursement_proof_file)
                    <a href="{{ Storage::url($expense->reimbursement_proof_file) }}" target="_blank"
                       class="flex items-center px-4 py-2 text-sm text-primary-600 dark:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-file-download w-5"></i>
                        Ver Comprovante
                    </a>
                @endif

                {{-- Reverter --}}
                <form action="{{ route('expenses.reimbursements.revert', $expense) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-undo w-5"></i>
                        Reverter
                    </button>
                </form>
                @break
        @endswitch
    </div>
</div>
