<x-app-layout>

    {{-- Cabeçalho e Botão Novo --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
         <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Gerenciamento de Gigs (Datas)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Visualize e gerencie as datas agendadas e seus status</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('gigs.import.form') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center text-sm shrink-0">
                <i class="fas fa-file-import mr-2"></i> Importar
            </a>
            <a href="{{ route('gigs.create', request()->query()) }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm shrink-0">
                <i class="fas fa-plus mr-2"></i> Nova Gig
            </a>
        </div>
    </div>

    {{-- Seção de Filtros (Expandida) --}}
<div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-md">
    <form action="{{ route('gigs.index') }}" method="GET">
        {{-- Linha de Filtros --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-4">
            {{-- 1. Busca Livre --}}
            <x-form.input 
                id="search" 
                label="Busca Livre" 
                placeholder="Nº Contrato, Artista, Local..." 
                :value="request('search')" 
            />

            {{-- 2. Artista --}}
            <x-form.select 
                id="artist_id" 
                label="Artista" 
                :options="$artists" 
                :selected="request('artist_id')" 
                empty="Todos"
            />

            {{-- 3. Booker --}}
            <x-form.select 
                id="booker_id" 
                label="Booker" 
                :options="$bookers" 
                :selected="request('booker_id')" 
                empty="Todos"
            >
                <option value="sem_booker" {{ request('booker_id') == 'sem_booker' ? 'selected' : '' }}>(Sem Booker / Agência)</option>
            </x-form.select>

            {{-- 4. Moeda --}}
            <div>
                <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Moeda</label>
                <select name="currency" id="currency" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="all">Todas</option>
                    @foreach($currencies as $currency)
                        <option value="{{ $currency }}" {{ request('currency') == $currency ? 'selected' : '' }}>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 5. Status Pagamento --}}
            <x-form.select 
                id="payment_status" 
                label="Status Pagamento" 
                :selected="request('payment_status')" 
                empty="Todos"
                :options="[
                    'pago' => 'Pago',
                    'vencido' => 'Vencido',
                    'a_vencer' => 'A Vencer',
                ]"
            />

            {{-- 6. Workflow Artista --}}
            <x-form.select 
                id="settlement_stage" 
                label="Workflow Artista" 
                :selected="request('settlement_stage')" 
                empty="Todos"
                :options="[
                    'aguardando_conferencia' => 'Conferir',
                    'fechamento_enviado' => 'Ag. NF/Recibo',
                    'documentacao_recebida' => 'Pronto p/ Pgto',
                    'pago' => 'Pago',
                ]"
            />

            {{-- 7. Status Pgto. Booker --}}
            <x-form.select 
                id="booker_payment_status" 
                label="Status Pgto. Booker" 
                :selected="request('booker_payment_status')" 
                empty="Todos"
                :options="[
                    'pago' => 'Pago',
                    'pendente' => 'Pendente',
                ]"
            />

            {{-- 8. Data Evento (De) --}}
            <x-form.date 
                id="start_date" 
                label="Data Evento (De)" 
                :value="request('start_date')" 
            />

            {{-- 9. Data Evento (Até) --}}
            <x-form.date 
                id="end_date" 
                label="Data Evento (Até)" 
                :value="request('end_date')" 
            />

            {{-- Botões --}}
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
                    {{-- Usa o componente para colunas ordenáveis --}}
                    {{-- Passa o nome da coluna do DB, o sortBy e sortDirection atuais --}}
                    <x-sortable-th column="gig_date" :currentSortBy="$sortBy" :currentSortDirection="$sortDirection" label="Data" defaultDirection="desc" />
                    {{-- Coluna Artista/Booker - Não ordenável diretamente por enquanto --}}
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista / Booker</th>
                    {{-- Coluna Local/Evento - Não ordenável diretamente --}}
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local / Evento</th>
                    <x-sortable-th column="currency" :currentSortBy="$sortBy" :currentSortDirection="$sortDirection" label="Moeda" />
                    <x-sortable-th column="cache_value" :currentSortBy="$sortBy" :currentSortDirection="$sortDirection" label="Cachê" class="text-right" defaultDirection="desc" /> {{-- Adiciona classe text-right --}}
                    <x-sortable-th column="contract_status" :currentSortBy="$sortBy" :currentSortDirection="$sortDirection" label="Status Contrato" class="text-center" />
                    <x-sortable-th column="payment_status" :currentSortBy="$sortBy" :currentSortDirection="$sortDirection" label="Pgto Cliente" class="text-center" />
                    <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Workflow Artista</th>
                    <x-sortable-th column="booker_payment_status" :currentSortBy="$sortBy" :currentSortDirection="$sortDirection" label="Pgto Booker" class="text-center" />
                    {{-- Coluna Ações - Não ordenável --}}
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($gigs as $gig)
                        {{-- Classe de Destaque para Vencido e para Moeda Estrangeira --}}
                        <tr class="{{ $gig->payment_status == 'vencido' ? 'bg-red-50 dark:bg-red-900/20' : ($gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '') }}">
                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $gig->gig_date->isoFormat('L') }}</td>
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
                                <x-status-badge :status="$gig->contract_status" type="contract" />
                            </td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center">
                                <x-status-badge :status="$gig->payment_status" type="payment" />
                            </td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center">
                               <x-workflow-badge :gig="$gig" />
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
                            <td colspan="10" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
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

{{-- Cache Busting: Atualiza página ao voltar via browser back --}}
<script>
(function() {
    // Usa pageshow para detectar navegação back (bfcache)
    // Isso é mais confiável que timestamp e não causa loops de reload
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            // Página veio do bfcache (navegação back) - recarrega para dados frescos
            window.location.reload();
        }
    });
})();
</script>
</x-app-layout>