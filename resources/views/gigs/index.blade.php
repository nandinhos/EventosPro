<x-app-layout>

    {{-- Cabeçalho e Botão Novo --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
         <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Gerenciamento de Gigs (Datas)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Visualize e gerencie as datas agendadas e seus status</p>
        </div>
        <a href="{{ route('gigs.create', request()->query()) }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm shrink-0">
            <i class="fas fa-plus mr-2"></i> Nova Gig
        </a>
    </div>

    {{-- Seção de Filtros (Expandida) --}}
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-md">
        <form action="{{ route('gigs.index') }}" method="GET">
            {{-- Linha 1 de Filtros --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
                {{-- Busca Livre --}}
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Busca Livre</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Nº Contrato, Artista, Local..." class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                {{-- Filtro Artista --}}
                <div>
                    <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artista</label>
                    <select name="artist_id" id="artist_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        @foreach($artists as $id => $name)
                            <option value="{{ $id }}" {{ request('artist_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Filtro Booker --}}
                <div>
                    <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Booker</label>
                    <select name="booker_id" id="booker_id" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="sem_booker" {{ request('booker_id') == 'sem_booker' ? 'selected' : '' }}>(Sem Booker / Agência)</option>
                        @foreach($bookers as $id => $name)
                            <option value="{{ $id }}" {{ request('booker_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                 {{-- Filtro Status Pagamento --}}
                 <div>
                    <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Pagamento</label>
                    <select name="payment_status" id="payment_status" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Todos</option>
                        <option value="pago" {{ request('payment_status') == 'pago' ? 'selected' : '' }}>Pago</option>
                        <option value="vencido" {{ request('payment_status') == 'vencido' ? 'selected' : '' }}>Vencido</option>
                        <option value="a_vencer" {{ request('payment_status') == 'a_vencer' ? 'selected' : '' }}>A Vencer</option>
                    </select>
                </div>
                 {{-- Filtro Moeda --}}
                 <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda</label>
                    <select name="currency" id="currency" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="all">Todas</option> {{-- Mudei para 'all' para clareza --}}
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}" {{ request('currency') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
             {{-- Linha 2 de Filtros --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                 {{-- Filtro Data Início --}}
                 <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento (De)</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                 {{-- Filtro Data Fim --}}
                 <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Evento (Até)</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                 {{-- Espaçadores e Botões --}}
                 <div class="lg:col-span-2"></div> {{-- Ocupa espaço --}}
                <div class="flex items-end justify-end space-x-2">
                     <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-md text-sm">
                        Limpar
                    </a>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded-md text-sm">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabela de Gigs (Estrutura Atualizada) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        {{-- Colunas Atualizadas --}}
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista / Booker</th> {{-- Coluna Combinada --}}
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local / Evento</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Moeda</th> {{-- Nova Coluna --}}
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê</th> {{-- Valor Original --}}
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" title="Status Pagamento Contratante">Pgto Cliente</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" title="Status Pagamento Artista">Pgto Artista</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" title="Status Pagamento Comissão Booker">Pgto Booker</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($gigs as $gig)
                        {{-- Classe de Destaque para Vencido e para Moeda Estrangeira --}}
                        <tr class="{{ $gig->payment_status == 'vencido' ? 'bg-red-50 dark:bg-red-900/20' : ($gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '') }}">
                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $gig->gig_date->format('d/m/Y') }}</td>
                            {{-- Coluna Combinada Artista/Booker --}}
                            <td class="px-3 py-1.5 whitespace-nowrap">
                                <span class="font-semibold text-gray-900 dark:text-white block">{{ $gig->artist->name ?? 'N/A' }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $gig->booker->name ?? 'Agência' }}</span>
                            </td>
                            <td class="px-3 py-1.5 whitespace-normal text-gray-600 dark:text-gray-400">{{ $gig->location_event_details }}</td>
                             {{-- Coluna Moeda --}}
                             <td class="px-3 py-1.5 whitespace-nowrap text-center text-gray-600 dark:text-gray-400">
                                {{ $gig->currency }}
                            </td>
                            {{-- Coluna Cachê (Valor Original) --}}
                            <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">
                                {{ number_format($gig->cache_value, 2, ',', '.') }}
                            </td>
                            {{-- Colunas de Status (usando componente) --}}
                            <td class="px-3 py-1.5 whitespace-nowrap text-center">
                                <x-status-badge :status="$gig->payment_status" type="payment" />
                            </td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center">
                               <x-status-badge :status="$gig->artist_payment_status" type="payment-artist" />
                            </td>
                             <td class="px-3 py-1.5 whitespace-nowrap text-center">
                                <x-status-badge :status="$gig->booker_payment_status" type="payment-booker" />
                            </td>
                            {{-- Coluna Ações --}}
                            <td class="px-3 py-1.5 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('gigs.show', ['gig' => $gig] + request()->query()) }}" title="Ver Detalhes" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        <i class="fas fa-eye fa-fw"></i>
                                    </a>
                                    <a href="{{ route('gigs.edit', ['gig' => $gig] + request()->query()) }}" title="Editar" class="text-primary-500 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                                        <i class="fas fa-edit fa-fw"></i>
                                    </a>
                                    <form action="{{ route('gigs.destroy', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza?');" class="inline">
                                        @csrf @method('DELETE')
                                        {{-- Adiciona campos hidden para cada filtro ativo --}}
                        @foreach(request()->query() as $key => $value)
                            @if(!is_array($value)) {{-- Evita erro com arrays --}}
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                                        {{-- Botão de Exclusão --}}
                                        <button type="submit" title="Excluir" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                            <i class="fas fa-trash-alt fa-fw"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Nenhum registro encontrado. Ajuste os filtros ou <a href="{{ route('gigs.create') }}" class="text-primary-600 hover:underline">crie uma nova Gig</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6 mt-4">
             @if ($gigs->hasPages())
                {{ $gigs->appends(request()->query())->links() }}
            @endif
        </div>
    </div>

</x-app-layout>