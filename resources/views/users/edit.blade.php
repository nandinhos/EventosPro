<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white">Editar Usuário: {{ $user->name }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('users.update', $user) }}"
                          x-data="{
                              isBooker: {{ json_encode(old('is_booker', $user->booker ? true : false)) }},
                              creationType: '{{ old('booker_creation_type', 'existing') }}'
                          }">
                        @csrf
                        @method('PATCH')
                        
                         @if ($errors->any())
                            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 rounded-md">
                                <h4 class="font-bold text-red-800 dark:text-red-300">Foram encontrados os seguintes erros:</h4>
                                <ul class="mt-2 list-disc list-inside text-sm text-red-700 dark:text-red-300">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Dados de Acesso</h3>
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="name" value="Nome do Usuário" />
                                <x-text-input id="name" name="name" :value="old('name', $user->name)" required class="block mt-1 w-full" />
                            </div>
                            <div>
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" :value="old('email', $user->email)" required type="email" class="block mt-1 w-full" />
                            </div>
                            <div>
                                <x-input-label for="password" value="Nova Senha (deixe em branco para não alterar)" />
                                <x-text-input id="password" name="password" type="password" class="block mt-1 w-full" />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" value="Confirmar Nova Senha" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" />
                            </div>
                        </div>

                        <hr class="my-6 dark:border-gray-700">

                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Perfil de Booker</h3>
                        <div class="flex items-center">
                            <input type="checkbox" id="is_booker" name="is_booker" value="1" x-model="isBooker" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label for="is_booker" class="ml-2 block text-sm font-medium text-gray-900 dark:text-white">Este usuário é um Booker?</label>
                        </div>
                        
                        <div x-show="isBooker" x-transition class="mt-4 space-y-4 border-l-4 border-primary-500 pl-4 py-2">
                            @if($user->booker)
                                {{-- SE O USUÁRIO JÁ É UM BOOKER --}}
                                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300">Dados do Booker Associado</h4>
                                <div>
                                    <x-input-label for="default_commission_rate" value="Taxa de Comissão Padrão (%)" />
                                    <x-text-input id="default_commission_rate" name="default_commission_rate" :value="old('default_commission_rate', $user->booker->default_commission_rate)" type="number" step="0.01" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('default_commission_rate')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="contact_info" value="Informação de Contato" />
                                    <x-text-input id="contact_info" name="contact_info" :value="old('contact_info', $user->booker->contact_info)" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('contact_info')" class="mt-2" />
                                </div>
                            @else
                                {{-- SE O USUÁRIO NÃO É UM BOOKER, MOSTRA A LÓGICA DE ASSOCIAÇÃO/CRIAÇÃO --}}
                                <div>
                                    <div class="flex space-x-6">
                                        <div class="flex items-center">
                                            <input type="radio" id="type_existing_edit" name="booker_creation_type" value="existing" x-model="creationType" x-bind:disabled="!isBooker" class="h-4 w-4 ...">
                                            <label for="type_existing_edit" class="ml-2 ...">Associar a Booker Existente</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="type_new_edit" name="booker_creation_type" value="new" x-model="creationType" x-bind:disabled="!isBooker" class="h-4 w-4 ...">
                                            <label for="type_new_edit" class="ml-2 ...">Cadastrar Novo Booker</label>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('booker_creation_type')" class="mt-2" />

                                    <div x-show="creationType === 'existing'" class="mt-4">
                                        <x-input-label for="existing_booker_id" value="Selecione o Booker" />
                                        <select name="existing_booker_id" id="existing_booker_id" 
                                                x-bind:disabled="!isBooker || creationType !== 'existing'" 
                                                class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <option value="">Selecione...</option>
                                            @foreach($availableBookers as $booker)
                                                <option value="{{ $booker->id }}" 
                                                    @if($booker->user) disabled @endif 
                                                    @selected(old('existing_booker_id') == $booker->id)>
                                                    {{ $booker->name }} @if($booker->user) (Já associado) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('existing_booker_id')" class="mt-2" />
                                    </div>

                                    <div x-show="creationType === 'new'" class="space-y-4 mt-4">
                                        <x-input-label for="booker_name_edit" value="Nome do Novo Booker" />
                                        <x-text-input id="booker_name_edit" name="booker_name" :value="old('booker_name')" 
                                                      x-bind:disabled="!isBooker || creationType !== 'new'" 
                                                      class="block mt-1 w-full uppercase" />
                                        <x-input-error :messages="$errors->get('booker_name')" class="mt-2" />
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end mt-8">
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900">Cancelar</a>
                            <x-primary-button class="ml-4">Salvar Alterações</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>