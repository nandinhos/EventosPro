<x-app-layout>
    <div class="mb-6 flex justify-between items-center">
        <div><h2 class="text-lg font-semibold text-gray-800 dark:text-white">Editar Booker: {{ $booker->name }}</h2></div>
        <a href="{{ route('bookers.index') }}" class="bg-gray-200 ...">Voltar</a>
    </div>
     <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <form action="{{ route('bookers.update', $booker) }}" method="POST">
            @csrf
            @method('PUT')
            @include('bookers._form', ['booker' => $booker])
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 ... flex justify-end ...">
                <a href="{{ route('bookers.index') }}" class="bg-gray-200 ...">Cancelar</a>
                <button type="submit" class="bg-primary-600 ...">Atualizar Booker</button>
            </div>
        </form>
    </div>
</x-app-layout>