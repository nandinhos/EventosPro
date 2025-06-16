<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Depuração de Projeções Financeiras
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filtro de Período --}}
            <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-md">
                <form action="{{ route('projections.debug') }}" method="GET">
                    <div class="flex items-end space-x-4">
                        <div class="flex-1">
                            <label for="period" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Período de Projeção</label>
                            <select name="period" id="period" class="mt-1 block w-full rounded-md ...">
                                <option value="30_days" @selected($period == '30_days')>Próximos 30 dias</option>
                                <option value="60_days" @selected($period == '60_days')>Próximos 60 dias</option>
                                <option value="90_days" @selected($period == '90_days')>Próximos 90 dias</option>
                                <option value="next_quarter" @selected($period == 'next_quarter')>Próximo Trimestre</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm">Analisar</button>
                    </div>
                </form>
            </div>

            {{-- Loop para cada cálculo de depuração --}}
            @foreach($debugData as $title => $data)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                            <h3 class="text-lg font-medium">{{ $title }}</h3>
                            <span class="text-xl font-bold {{ $data['value'] >= 0 ? 'text-green-600' : 'text-red-600' }}">R$ {{ number_format($data['value'], 2, ',', '.') }}</span>
                        </div>

                        @if($data['items'] !== null && $data['items']->isNotEmpty())
                            <h4 class="text-sm font-semibold mb-2">Itens Considerados no Cálculo:</h4>
                            <div class="overflow-x-auto text-xs">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        {{-- Tabela para Artistas, Bookers e Clientes --}}
                                        @if(in_array($title, ['Contas a Pagar (Artistas)', 'Contas a Pagar (Bookers)', 'Contas a Receber (Clientes)']))
                                            <tr>
                                                <th class="px-2 py-1 text-left">Data</th>    
                                                <th class="px-2 py-1 text-left">Gig</th>
                                                <th class="px-2 py-1 text-left">Descrição</th>   
                                                <th class="px-2 py-1 text-right">Valor (BRL)</th>
                                            </tr>
                                        @elseif($title === 'Contas a Pagar (Despesas Previstas)')
                                            <tr>
                                                <th class="px-2 py-1 text-left">Centro de Custo</th>
                                                <th class="px-2 py-1 text-right">Total Previsto</th>
                                            </tr>
                                        @endif
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        {{-- ** INÍCIO DA LÓGICA CORRIGIDA ** --}}
                                        @if($title === 'Contas a Pagar (Artistas)')
                                            @foreach($data['items'] as $gig)
                                            <tr>                                                
                                                <td class="px-2 py-1">{{ $gig->gig_date->format('d/m/Y') }}</td>
                                                <td class="px-2 py-1">#{{ $gig->id }}</td>
                                                <td class="px-2 py-1">{{ $gig->artist->name ?? 'N/A' }}</td>
                                                {{-- USA O VALOR CORRETO, que inclui o reembolso --}}
                                                <td class="px-2 py-1 text-right">{{ number_format($gig->calculated_artist_invoice_value_brl, 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        @elseif($title === 'Contas a Pagar (Bookers)')
                                            @foreach($data['items'] as $gig)
                                            <tr>
                                                <td class="px-2 py-1">{{ $gig->gig_date->format('d/m/Y') }}</td>
                                                <td class="px-2 py-1">#{{ $gig->id }}</td>
                                                <td class="px-2 py-1">{{ $gig->booker->name ?? 'N/A' }}</td>
                                                {{-- USA O VALOR CORRETO da comissão do booker --}}
                                                <td class="px-2 py-1 text-right">{{ number_format($gig->calculated_booker_commission_brl, 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        @elseif($title === 'Contas a Receber (Clientes)')
                                            @foreach($data['items'] as $payment)
                                            <tr>
                                            
                                            <td class="px-2 py-1">{{ $payment->due_date->format('d/m/Y') }}</td>    
                                            <td class="px-2 py-1">#{{ $payment->gig_id }}</td>
                                                <td class="px-2 py-1">{{ $payment->description ?? 'N/A' }}</td>
                                                <td class="px-2 py-1 text-right">{{ number_format($payment->due_value_brl, 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        @elseif($title === 'Contas a Pagar (Despesas Previstas)')
                                            @foreach($data['items'] as $item)
                                            <tr>
                                                <td class="px-2 py-1">{{ $item['cost_center_name'] }}</td>
                                                <td class="px-2 py-1 text-right">{{ number_format($item['total_brl'], 2, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        @endif
                                        {{-- ** FIM DA LÓGICA CORRIGIDA ** --}}
                                    </tbody>
                                </table>
                            </div>
                        @elseif($data['items'] !== null)
                            <p class="text-gray-500">Nenhum item encontrado para esta projeção no período.</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>