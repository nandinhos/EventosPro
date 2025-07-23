<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white">Detalhes do Usuário: {{ $user->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 space-y-6">

                    <!-- Seção de Dados de Acesso -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Dados de Acesso</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome do Usuário</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">{{ $user->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">{{ $user->email }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Data de Criação</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Perfil</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">
                                    @if($user->booker)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Booker</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800">Operador</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Seção do Perfil de Booker (se existir) -->
                    @if($user->booker)
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Perfil de Booker Associado</h3>
                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome do Booker</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">{{ $user->booker->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Taxa de Comissão Padrão</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">{{ number_format($user->booker->default_commission_rate, 2, ',', '.') }}%</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Informação de Contato</p>
                                <p class="mt-1 text-md text-gray-900 dark:text-white">{{ $user->booker->contact_info ?: 'Não informado' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Botões de Ação -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Voltar para a Lista</a>
                        <a href="{{ route('users.edit', $user) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-semibold inline-flex items-center">
                            <i class="fas fa-edit mr-2"></i> Editar Usuário
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>