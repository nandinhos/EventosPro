<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Tomadores de Serviço') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('service-takers.import') }}"
                   class="bg-gray-600 hover:bg-gray-700 text-white font-medium px-4 py-2 rounded-md flex items-center text-sm shadow-md transition">
                    <i class="fas fa-file-import mr-2"></i> Importar CSV
                </a>
                <a href="{{ route('service-takers.create') }}"
                   class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-5 py-2 rounded-md flex items-center text-sm shadow-md transition">
                    <i class="fas fa-plus mr-2"></i> Novo Tomador
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100" 
                     x-data="{
                        expandedRows: [],
                        showDeleteModal: false,
                        deleteId: null,
                        deleteName: '',
                        
                        toggleExpand(id) {
                            const index = this.expandedRows.indexOf(id);
                            if (index > -1) {
                                this.expandedRows.splice(index, 1);
                            } else {
                                this.expandedRows.push(id);
                            }
                        },
                        isExpanded(id) {
                            return this.expandedRows.includes(id);
                        },
                        openDeleteModal(id, name) {
                            this.deleteId = id;
                            this.deleteName = name;
                            this.showDeleteModal = true;
                        },
                        closeDeleteModal() {
                            this.showDeleteModal = false;
                            this.deleteId = null;
                            this.deleteName = '';
                        }
                     }">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('service-takers.index') }}" class="mb-6 flex gap-4">
                        <input type="text" name="search" placeholder="Buscar por organização, documento ou contato..."
                               value="{{ request('search') }}"
                               class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">

                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">Filtrar</button>
                        <a href="{{ route('service-takers.index') }}" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">Limpar</a>
                    </form>

                    <!-- Stats -->
                    <div class="mb-4 text-sm text-gray-500">
                        Total: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $serviceTakers->total() }}</span> tomadores
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase w-8"></th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Organização</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Cidade</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Email</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($serviceTakers as $serviceTaker)
                                    {{-- Linha Principal --}}
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                        @click="toggleExpand({{ $serviceTaker->id }})">
                                        <td class="px-4 py-3 text-sm">
                                            <i class="fas fa-chevron-right text-gray-400 transition-transform duration-200" 
                                               :class="isExpanded({{ $serviceTaker->id }}) && 'rotate-90'"></i>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900 dark:text-white">
                                                {{ $serviceTaker->organization ?? 'Sem nome' }}
                                            </div>
                                            @if($serviceTaker->document)
                                                <div class="text-sm italic text-gray-500 dark:text-gray-400">
                                                    {{ $serviceTaker->formatted_document }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $serviceTaker->city ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            @if($serviceTaker->email)
                                                <a href="mailto:{{ $serviceTaker->email }}" 
                                                   @click.stop
                                                   class="text-primary-500 hover:underline">
                                                    {{ $serviceTaker->email }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center" @click.stop>
                                            <div class="flex items-center justify-center space-x-3">
                                                <a href="{{ route('service-takers.show', $serviceTaker) }}" title="Ver"
                                                   class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                    <i class="fas fa-eye fa-fw"></i>
                                                </a>
                                                <a href="{{ route('service-takers.edit', $serviceTaker) }}" title="Editar"
                                                   class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                                    <i class="fas fa-edit fa-fw"></i>
                                                </a>
                                                <button type="button" title="Excluir"
                                                        @click="openDeleteModal({{ $serviceTaker->id }}, '{{ addslashes($serviceTaker->organization ?? 'Este tomador') }}')"
                                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                    <i class="fas fa-trash-alt fa-fw"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Linha Expandida com Detalhes --}}
                                    <tr x-show="isExpanded({{ $serviceTaker->id }})"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-cloak
                                        class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                                        <td colspan="5" class="p-0">
                                            <div class="p-4 border-t border-gray-200 dark:border-gray-600">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    {{-- Card Endereço --}}
                                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                                                        <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                                                            <i class="fas fa-map-marker-alt mr-2 text-primary-500"></i>Endereço
                                                        </h5>
                                                        <div class="space-y-1 text-sm">
                                                            @if($serviceTaker->street)
                                                                <p class="text-gray-700 dark:text-gray-300">{{ $serviceTaker->street }}</p>
                                                            @endif
                                                            <p class="text-gray-600 dark:text-gray-400">
                                                                {{ collect([$serviceTaker->city, $serviceTaker->postal_code, $serviceTaker->country])->filter()->implode(', ') ?: '-' }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    {{-- Card Contato --}}
                                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                                                        <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                                                            <i class="fas fa-user mr-2 text-primary-500"></i>Contato
                                                        </h5>
                                                        <div class="space-y-1 text-sm">
                                                            <p class="font-medium text-gray-800 dark:text-white">{{ $serviceTaker->contact ?: '-' }}</p>
                                                            @if($serviceTaker->phone)
                                                                <p class="text-gray-600 dark:text-gray-400">
                                                                    <i class="fas fa-phone text-xs mr-1"></i>{{ $serviceTaker->phone }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Card Telefones --}}
                                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                                                        <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3 flex items-center">
                                                            <i class="fas fa-building mr-2 text-primary-500"></i>Empresa
                                                        </h5>
                                                        <div class="space-y-1 text-sm">
                                                            @if($serviceTaker->company_phone)
                                                                <p class="text-gray-600 dark:text-gray-400">
                                                                    <i class="fas fa-phone text-xs mr-1"></i>{{ $serviceTaker->company_phone }}
                                                                </p>
                                                            @else
                                                                <p class="text-gray-400">Sem telefone cadastrado</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Nenhum tomador de serviço encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $serviceTakers->withQueryString()->links() }}
                    </div>

                    {{-- Modal de Confirmação de Exclusão --}}
                    <div x-show="showDeleteModal"
                         x-cloak
                         @keydown.escape.window="closeDeleteModal()"
                         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center"
                         style="display: none;">
                        
                        {{-- Overlay --}}
                        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" @click="closeDeleteModal()"></div>
                        
                        {{-- Modal Content --}}
                        <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-4 shadow-2xl p-6 transform transition-all"
                             x-transition:enter="ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="ease-in duration-200"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.away="closeDeleteModal()">
                            
                            {{-- Header --}}
                            <div class="flex items-center pb-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30">
                                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirmar Exclusão</h3>
                                </div>
                                <button @click="closeDeleteModal()" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            
                            {{-- Body --}}
                            <div class="py-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Tem certeza que deseja excluir o tomador <strong x-text="deleteName" class="text-gray-900 dark:text-white"></strong>?
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                                    Esta ação não pode ser desfeita.
                                </p>
                            </div>
                            
                            {{-- Footer --}}
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <button type="button" @click="closeDeleteModal()"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-md transition">
                                    Cancelar
                                </button>
                                <form :action="'/service-takers/' + deleteId" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition">
                                        <i class="fas fa-trash-alt mr-2"></i>Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
