<x-app-layout>
    {{-- Cabeçalho da página de edição --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Editar Booker') }}
        </h2>
    </x-slot>
    
    <div class="mb-6 flex justify-between items-center">
       
        <x-back-button :fallback="route('bookers.index')" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md shadow-sm transition" />
    </div>

    {{-- Formulário --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('bookers.update', $booker) }}" method="POST">
            @csrf
            @method('PUT')
            @include('bookers._form', ['booker' => $booker])

            {{-- Rodapé do form com botões --}}
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 flex justify-end space-x-3">
                <a href="{{ route('bookers.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium rounded-md shadow-sm transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-md shadow-sm transition">
                    Atualizar Booker
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
