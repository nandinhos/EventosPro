<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Pendências por Booker
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Visualize todas as parcelas de clientes pendentes, agrupadas por Booker responsável.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">

            {{-- Formulário de Filtros --}}
            <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow-md mb-6">
                <form method="GET" action="{{ route('reports.delinquency') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="event_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Evento (De)</label>
                            <input type="date" name="event_start_date" id="event_start_date" value="{{ request('event_start_date') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label for="event_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data Evento (Até)</label>
                            <input type="date" name="event_end_date" id="event_end_date" value="{{ request('event_end_date') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label for="due_start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Venc. Parcela (De)</label>
                            <input type="date" name="due_start_date" id="due_start_date" value="{{ request('due_start_date') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label for="due_end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Venc. Parcela (Até)</label>
                            <input type="date" name="due_end_date" id="due_end_date" value="{{ request('due_end_date') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                        </div>
                        <div>
                            <label for="artist_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Artista</label>
                            <select name="artist_id" id="artist_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">Todos</option>
                                @foreach ($artists as $id => $name)
                                    <option value="{{ $id }}" {{ request('artist_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="booker_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Booker</label>
                            <select name="booker_id" id="booker_id" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="">Todos</option>
                                <option value="sem_booker" {{ request('booker_id') == 'sem_booker' ? 'selected' : '' }}>(Sem Booker / Agência)</option>
                                @foreach ($bookers as $id => $name)
                                    <option value="{{ $id }}" {{ request('booker_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moeda da Parcela</label>
                            <select name="currency" id="currency" class="mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white text-sm">
                                <option value="all">Todas</option>
                                @foreach($currencies as $currencyCode)
                                    <option value="{{ $currencyCode }}" {{ request('currency') == $currencyCode ? 'selected' : '' }}>{{ $currencyCode }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- ***** NOVO CHECKBOX AQUI ***** --}}
                        <div class="flex items-center pt-5">
                            <input type="checkbox" name="include_paid" id="include_paid" value="1" 
                                   @if(request('include_paid')) checked @endif
                                   class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label for="include_paid" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Incluir Gigs Pagas
                            </label>
                        </div>
                        <div class="flex items-center space-x-2 pt-5 xl:col-span-2">
                            <a href="{{ route('reports.delinquency') }}"
                               class="flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full sm:w-auto">
                                <i class="fas fa-broom mr-2"></i>Limpar
                            </a>
                            <button type="submit" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full sm:w-auto">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                            <a href="{{ route('reports.delinquency.exportPdf', request()->query()) }}" 
                               target="_blank"
                               class="flex items-center justify-center px-4 py-2 border border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-md shadow-sm text-sm font-medium w-full sm:w-auto">
                                <i class="fas fa-file-pdf mr-2"></i>Exportar PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Card de Resumo Financeiro (a lógica no controller foi simplificada, então este card agora é um resumo geral de todas as gigs listadas) --}}
            {{-- Se precisar de um cálculo mais detalhado, a lógica no controller precisará ser ajustada --}}

            {{-- Tabela de Pendências Agrupada --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left w-2/5">Gig (Contrato) / Local</th>
                                <th class="px-3 py-2 text-left w-1/5">Artista / Data Evento</th>
                                <th class="px-3 py-2 text-left w-2/5" colspan="3">Detalhes das Parcelas Pendentes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            @forelse ($gigsGroupedByBooker as $bookerName => $gigs)
                                {{-- Linha de Cabeçalho do Booker --}}
                                <tr class="bg-primary-50 dark:bg-primary-900/30">
                                    <td colspan="5" class="px-3 py-3 font-bold text-lg ">
                                        <i class="fas fa-user-tie fa-fw mr-2"></i>{{ $bookerName }}
                                    </td>
                                </tr>

                                {{-- Loop para as Gigs deste Booker --}}
                                @foreach ($gigs as $gig)
                                    <tr class="border-b border-gray-200 dark:border-gray-700 ">
                                        {{-- Célula com Info da Gig --}}
                                        <td class="px-3 py-3 align-middle">
                                            <a href="{{ route('gigs.show', $gig) }}" class="font-semibold text-primary-600 hover:underline">
                                                {{ $gig->contract_number ?: 'Gig #'.$gig->id }}
                                            </a>
                                            @if($gig->location_event_details)
                                            <span class="block text-xs text-gray-500 dark:text-gray-400 italic" title="{{ $gig->location_event_details }}">
                                                {{ Str::limit($gig->location_event_details, 50) }}
                                            </span>
                                            @endif
                                        </td>
                                        {{-- Célula com Info do Artista e Data --}}
                                        <td class="px-3 py-3 align-middle">
                                            <span class="font-semibold text-gray-900 dark:text-white block">{{ $gig->artist->name ?? 'N/A' }}</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $gig->gig_date->format('d/m/Y') }}</span>
                                        </td>
                                        {{-- Célula para as Parcelas e Resumo Financeiro --}}
                                        <td class="px-3 py-3 align-top" colspan="3">
                                            <table class="w-full">
                                                {{-- Sub-cabeçalho das parcelas --}}
                                                <thead class="text-xxs text-gray-400 dark:text-gray-500">
                                                    <tr>
                                                        <th class="pb-1 text-left">Vencimento</th>
                                                        <th class="pb-1 text-right">Valor</th>
                                                        <th class="pb-1 text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {{-- Loop para as Parcelas Pendentes desta Gig --}}
                                                    @foreach ($gig->payments as $payment)
                                                        <tr class="text-xs">
                                                            <td class="py-1 font-medium {{ $payment->due_date->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                                {{ $payment->due_date->format('d/m/Y') }}
                                                            </td>
                                                            <td class="py-1 text-right font-medium">
                                                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                                            </td>
                                                            <td class="py-1 text-center">
                                                                <x-status-badge :status="$payment->inferred_status" type="payment" />
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                {{-- Rodapé com o Resumo Financeiro da Gig --}}
                                                <tfoot class="text-xs border-t border-gray-200 dark:border-gray-600">
                                                    <tr>
                                                        <td class="pt-2 text-green-600 dark:text-green-400 font-semibold"">Total Recebido:</td>
                                                        <td class="pt-2 text-right text-green-600 dark:text-green-400 font-semibold" colspan="2">
                                                            R$ {{ number_format($gig->total_received_brl, 2, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-red-600 dark:text-red-400 font-bold text-sm"">Saldo Pendente Gig:</td>
                                                        <td class="text-right text-red-600 dark:text-red-400 font-bold text-sm" colspan="2">
                                                            {{ $gig->currency }} {{ number_format(max(0, $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual')), 2, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        Nenhuma pendência encontrada para os filtros aplicados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>