<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $serviceTaker->organization ?? 'Tomador de Serviço' }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('service-takers.edit', $serviceTaker) }}"
                   class="bg-primary-600 hover:bg-primary-700 text-white font-medium px-4 py-2 rounded-md flex items-center text-sm shadow-md transition">
                    <i class="fas fa-edit mr-2"></i> Editar
                </a>
                <a href="{{ route('service-takers.index') }}"
                   class="bg-gray-500 hover:bg-gray-600 text-white font-medium px-4 py-2 rounded-md flex items-center text-sm shadow-md transition">
                    <i class="fas fa-arrow-left mr-2"></i> Voltar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Info Card -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                                <i class="fas fa-building mr-2"></i>Dados da Organização
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Organização</dt>
                                    <dd class="font-medium">{{ $serviceTaker->organization ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Documento (CPF/CNPJ)</dt>
                                    <dd class="font-medium">{{ $serviceTaker->document ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Endereço</dt>
                                    <dd class="font-medium">{{ $serviceTaker->full_address }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Contact Card -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                                <i class="fas fa-address-book mr-2"></i>Contato
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Nome do Contato</dt>
                                    <dd class="font-medium">{{ $serviceTaker->contact ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="font-medium">
                                        @if($serviceTaker->email)
                                            <a href="mailto:{{ $serviceTaker->email }}" class="text-primary-500 hover:underline">
                                                {{ $serviceTaker->email }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Telefone (Contato)</dt>
                                    <dd class="font-medium">{{ $serviceTaker->phone ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Telefone (Empresa)</dt>
                                    <dd class="font-medium">{{ $serviceTaker->company_phone ?? '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Gigs relacionadas -->
                    @if($serviceTaker->gigs && $serviceTaker->gigs->count() > 0)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                                <i class="fas fa-calendar-check mr-2"></i>Gigs Relacionadas ({{ $serviceTaker->gigs->count() }})
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Artista</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Local</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($serviceTaker->gigs as $gig)
                                            <tr>
                                                <td class="px-4 py-2 text-sm">{{ $gig->gig_date?->format('d/m/Y') }}</td>
                                                <td class="px-4 py-2 text-sm">{{ $gig->artist?->name }}</td>
                                                <td class="px-4 py-2 text-sm">{{ Str::limit($gig->location_event_details, 40) }}</td>
                                                <td class="px-4 py-2 text-center">
                                                    <a href="{{ route('gigs.show', $gig) }}" class="text-primary-500 hover:text-primary-700">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
