<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white">Novo Usuário</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    {{-- O 'x-data' espera um objeto JavaScript literal. Usamos @json para converter o array PHP
                         para uma string JSON válida que o Alpine.js pode interpretar diretamente. --}}
                    <form method="POST" 
                          action="{{ route('users.store') }}" 
                          x-data="{
                              isBooker: {{ json_encode(old('is_booker', false)) }},
                              creationType: '{{ old('booker_creation_type', 'existing') }}'
                          }">
                        @csrf
                        
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
                                <x-text-input id="name" name="name" :value="old('name')" required class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" :value="old('email')" required type="email" class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="password" value="Senha" />
                                <x-text-input id="password" name="password" type="password" required class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="password_confirmation" value="Confirmar Senha" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required class="block mt-1 w-full" />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>

                        <hr class="my-6 dark:border-gray-700">

                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Perfil de Booker</h3>
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="is_booker" 
                                   name="is_booker" 
                                   x-model="isBooker"
                                   value="1"
                                   class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                            <label for="is_booker" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Este usuário é um Booker?
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('is_booker')" class="mt-2" />

                        <div x-show="isBooker" x-transition class="mt-4 space-y-4 border-l-4 border-primary-500 pl-4 py-2">
                            <div class="flex space-x-6">
                                <div class="flex items-center">
                                    <input type="radio" 
                                           id="type_existing_create" 
                                           name="booker_creation_type" 
                                           value="existing" 
                                           x-model="creationType"
                                           {{-- Desabilitar se não for um booker ou se o tipo de criação não for 'existing' --}}
                                           x-bind:disabled="!isBooker"
                                           {{ old('booker_creation_type') === 'existing' ? 'checked' : '' }}
                                           class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="type_existing_create" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Associar a Booker Existente</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="type_new_create" name="booker_creation_type" value="new" x-model="creationType" x-bind:disabled="!isBooker" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="type_new_create" class="ml-2 text-sm text-gray-700 dark:text-gray-300">Cadastrar Novo Booker</label>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('booker_creation_type')" class="mt-2" />

                            <div x-show="creationType === 'existing'">
                                <x-input-label for="existing_booker_id" value="Selecione o Booker" />
                                <select name="existing_booker_id" id="existing_booker_id" x-bind:disabled="!isBooker || creationType !== 'existing'" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Selecione...</option>
                                    @foreach($bookers as $booker)
                                        <option value="{{ $booker->id }}" @if($booker->user) disabled @endif @selected(old('existing_booker_id') == $booker->id)>
                                            {{ $booker->name }} @if($booker->user) (Já associado) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('existing_booker_id')" class="mt-2" />
                            </div>
                            
                            <div x-show="creationType === 'new'" class="space-y-4">
                                <div>
                                    <x-input-label for="booker_name" value="Nome do Booker (para exibição)" />
                                    <x-text-input id="booker_name" name="booker_name" :value="old('booker_name')" x-bind:disabled="!isBooker || creationType !== 'new'" class="block mt-1 w-full uppercase" placeholder="NOME EM MAIÚSCULAS" />
                                    <x-input-error :messages="$errors->get('booker_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="default_commission_rate" value="Taxa de Comissão Padrão (%)" />
                                    <x-text-input id="default_commission_rate" name="default_commission_rate" :value="old('default_commission_rate')" type="number" step="0.01" x-bind:disabled="!isBooker || creationType !== 'new'" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('default_commission_rate')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="contact_info" value="Informação de Contato" />
                                    <x-text-input id="contact_info" name="contact_info" :value="old('contact_info')" x-bind:disabled="!isBooker || creationType !== 'new'" class="block mt-1 w-full" />
                                    <x-input-error :messages="$errors->get('contact_info')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-8">
                            <a href="{{ route('users.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">Cancelar</a>
                            <x-primary-button class="ml-4">Criar Usuário</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
