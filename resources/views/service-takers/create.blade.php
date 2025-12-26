<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Novo Tomador de Serviço') }}
            </h2>
            <a href="{{ route('service-takers.index') }}"
               class="bg-gray-500 hover:bg-gray-600 text-white font-medium px-4 py-2 rounded-md flex items-center text-sm shadow-md transition">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('service-takers.store') }}" class="space-y-6">
                        @csrf

                        <!-- Organização -->
                        <div>
                            <label for="organization" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organização</label>
                            <input type="text" name="organization" id="organization" value="{{ old('organization') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            @error('organization')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Documento -->
                        <div>
                            <label for="document" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPF/CNPJ</label>
                            <input type="text" name="document" id="document" value="{{ old('document') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            @error('document')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- IE e IM -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="state_registration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Inscrição Estadual (IE)</label>
                                <input type="text" name="state_registration" id="state_registration" value="{{ old('state_registration') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                @error('state_registration')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="municipal_registration" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Inscrição Municipal (IM)</label>
                                <input type="text" name="municipal_registration" id="municipal_registration" value="{{ old('municipal_registration') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                @error('municipal_registration')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Documento Internacional -->
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="is_international" id="is_international" value="1"
                                   {{ old('is_international') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                            <label for="is_international" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Documento Internacional (estrangeiro)
                            </label>
                        </div>

                        <!-- Endereço -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="street" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Endereço</label>
                                <input type="text" name="street" id="street" value="{{ old('street') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">CEP</label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                                <input type="text" name="city" id="city" value="{{ old('city') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">País</label>
                                <input type="text" name="country" id="country" value="{{ old('country', 'Brasil') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>

                        <!-- Contato -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Contato</label>
                                <input type="text" name="contact" id="contact" value="{{ old('contact') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone do Contato</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label for="company_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone da Empresa</label>
                                <input type="text" name="company_phone" id="company_phone" value="{{ old('company_phone') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-6 py-2 rounded-md shadow-md transition">
                                <i class="fas fa-save mr-2"></i> Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
