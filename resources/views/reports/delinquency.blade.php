{{-- resources/views/reports/delinquency.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Histórico e Status de Parcelas por Evento {{-- TÍTULO PRINCIPAL ALTERADO --}}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Visualize o status completo de todas as parcelas de clientes, agrupadas por evento.</p> {{-- DESCRIÇÃO ALTERADA --}}
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
                        <div class="flex items-center space-x-2 pt-5 xl:col-span-2">
                            <a href="{{ route('reports.delinquency') }}"
                               class="flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-500 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full sm:w-auto">
                                <i class="fas fa-broom mr-2"></i>Limpar
                            </a>
                            <button type="submit" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 w-full sm:w-auto">
                                <i class="fas fa-filter mr-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Card de Resumo Financeiro --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Resumo das Gigs Listadas (Filtradas)</h3> {{-- TÍTULO DO CARD ALTERADO --}}
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                    {{-- A lógica do controller para essas variáveis de resumo já deve estar ok --}}
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Contratado Total (BRL Consolidado*):</span>
                        <span class="font-semibold text-gray-900 dark:text-white ml-1">R$ {{ number_format($totalContractValueConsolidatedBRL ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Recebido Total (BRL):</span>
                        <span class="font-semibold text-green-600 dark:text-green-400 ml-1">R$ {{ number_format($totalReceivedValueBRL ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Pendente Total (BRL Consolidado*):</span>
                        <span class="font-semibold text-red-600 dark:text-red-400 ml-1">R$ {{ number_format($totalPendingValueConsolidatedBRL ?? 0, 2, ',', '.') }}</span>
                    </div>
                    @if(!empty($totalPendingByOtherCurrency))
                        <div class="md:col-span-3 mt-2 pt-2 border-t dark:border-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                *Valores BRL consolidados consideram apenas contratos em BRL ou contratos em moeda estrangeira com taxa de câmbio já definida por pagamentos confirmados.
                                <br>Pendências adicionais (não confirmadas) em outras moedas: {{-- TEXTO AJUSTADO --}}
                                @foreach($totalPendingByOtherCurrency as $currency => $amount)
                                    <span class="font-semibold">{{ $currency }} {{ number_format($amount, 2, ',', '.') }}</span>@if(!$loop->last), @endif
                                @endforeach
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tabela Agrupada --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        {{-- ... (thead permanece o mesmo) ... --}}
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left">Gig (Contrato) / Local</th>
                                <th class="px-3 py-2 text-left">Data Evento</th>
                                <th class="px-3 py-2 text-left">Artista / Booker</th>
                                <th class="px-3 py-2 text-left">Descrição Parcela</th>
                                <th class="px-3 py-2 text-left">Vencimento</th>
                                <th class="px-3 py-2 text-right">Valor Parcela</th>
                                <th class="px-3 py-2 text-center">Moeda</th>
                                <th class="px-3 py-2 text-center">Status Parcela</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800">
                            {{-- O loop e a exibição das parcelas já devem mostrar o status correto (Confirmado, Vencido, A Vencer) --}}
                            @forelse ($pendingPaymentsGroupedByGig as $gigId => $group) {{-- Manter nome da var ou renomear para allPaymentsGroupedByGig --}}
                                @php
                                    $gig = $group['gig'];
                                    if (!$gig) continue;

                                    $cacheBrlDetails = $gig->cacheValueBrlDetails;
                                    $totalReceivedThisGigBRL = $gig->payments
                                                                ->whereNotNull('confirmed_at')
                                                                ->sum(function($p) {
                                                                    if (strtoupper($p->currency) === 'BRL') return $p->received_value_actual;
                                                                    $rate = $p->exchange_rate_received_actual ?: ($p->exchange_rate ?: 1);
                                                                    return $p->received_value_actual * $rate;
                                                                });
                                    $contractValueThisGigBRL = $cacheBrlDetails['value'] ?? ($gig->currency === 'BRL' ? $gig->cache_value : 0);
                                    $displayableContractValueBRL = ($cacheBrlDetails['type'] === 'confirmed' || $gig->currency === 'BRL') ? $contractValueThisGigBRL : null;
                                    
                                    // Saldo pendente da Gig na moeda original
                                    $pendingOriginalCurrency = $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual');
                                    // Saldo pendente da Gig em BRL (se conversão BRL do contrato for confiável)
                                    $pendingBalanceThisGigBRL = ($displayableContractValueBRL !== null) ? ($displayableContractValueBRL - $totalReceivedThisGigBRL) : null;

                                @endphp
                                    <tr class="bg-gray-100 dark:bg-gray-700/50 hover:bg-gray-200 dark:hover:bg-gray-600/50">
                                        {{-- ... (células da linha da Gig - sem grandes alterações) ... --}}
                                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-100">
                                            <a href="{{ route('gigs.show', $gig) }}" class="text-primary-600 hover:underline">
                                                {{ $gig->contract_number ?: 'Gig #'.$gigId }}
                                            </a>
                                            @if($gig->location_event_details)
                                            <span class="block text-xxs text-gray-500 dark:text-gray-400 italic truncate max-w-[200px]" title="{{ $gig->location_event_details }}">
                                                {{ Str::limit($gig->location_event_details, 35) }}
                                            </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $gig->gig_date->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span class="font-semibold text-gray-900 dark:text-white block">{{ $gig->artist->name ?? 'N/A' }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $gig->booker->name ?? 'Agência' }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-300 text-right" colspan="5">
                                            <div class="space-y-0.5">
                                                <div>
                                                    Contrato: <span class="font-semibold">{{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}</span>
                                                    @if($gig->currency !== 'BRL' && $displayableContractValueBRL !== null)
                                                        <span class="text-gray-500 dark:text-gray-400">(BRL {{ number_format($displayableContractValueBRL, 2, ',', '.') }})</span>
                                                    @elseif($gig->currency !== 'BRL')
                                                        <span class="text-gray-500 dark:text-gray-400">(BRL não consolidado)</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    Recebido: <span class="font-semibold text-green-600 dark:text-green-400">BRL {{ number_format($totalReceivedThisGigBRL, 2, ',', '.') }}</span>
                                                </div>
                                                <div>
                                                    Pendente Gig: 
                                                    <span class="font-semibold text-red-600 dark:text-red-400">
                                                        {{ $gig->currency }} {{ number_format(max(0, $pendingOriginalCurrency), 2, ',', '.') }} {{-- Garante que não seja negativo --}}
                                                    </span>
                                                    @if($pendingBalanceThisGigBRL !== null && $gig->currency !== 'BRL' && $pendingOriginalCurrency > 0.009)
                                                         <span class="text-gray-500 dark:text-gray-400">(aprox. BRL {{ number_format(max(0,$pendingBalanceThisGigBRL), 2, ',', '.') }})</span>
                                                    @elseif ($gig->currency === 'BRL' && $pendingOriginalCurrency > 0.009)
                                                         <span class="text-gray-500 dark:text-gray-400">(BRL {{ number_format(max(0,$pendingBalanceThisGigBRL), 2, ',', '.') }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- Parcelas da Gig --}}
                                    @foreach ($group['payments'] as $payment)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 
                                            {{ $payment->confirmed_at ? 'bg-green-50 dark:bg-green-900/10' : '' }}
                                        ">
                                            <td class="px-3 py-1.5 border-l-4 {{ $payment->confirmed_at ? 'border-green-300 dark:border-green-700' : 'border-transparent' }}"></td>
                                            <td class="px-3 py-1.5"></td>
                                            <td class="px-3 py-1.5"></td>
                                            <td class="pl-6 pr-3 py-1.5 whitespace-normal max-w-xs truncate" title="{{ $payment->description }}">
                                                {{ $payment->description ?: 'Parcela' }}
                                            </td>
                                            <td class="px-3 py-1.5 whitespace-nowrap font-medium 
                                                {{ $payment->inferred_status === 'vencido' ? 'text-red-600 dark:text-red-400' : 
                                                   ($payment->inferred_status === 'a_vencer' ? 'text-blue-600 dark:text-blue-400' : 
                                                   ($payment->inferred_status === 'confirmado' ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300')) }}">
                                                {{ $payment->due_date->format('d/m/Y') }}
                                            </td>
                                            <td class="px-3 py-1.5 whitespace-nowrap text-right font-medium">{{ number_format($payment->due_value, 2, ',', '.') }}</td>
                                            <td class="px-3 py-1.5 whitespace-nowrap text-center">{{ $payment->currency }}</td>
                                            <td class="px-3 py-1.5 whitespace-nowrap text-center">
                                                <x-status-badge :status="$payment->inferred_status" type="payment" />
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td colspan="8" class="py-1 border-t-2 border-gray-200 dark:border-gray-700"></td>
                                    </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                        Nenhuma parcela encontrada para os filtros aplicados.
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