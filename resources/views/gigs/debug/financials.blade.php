<x-app-layout>
    @php
        // Prepara os detalhes da conversão uma única vez para usar na view
        // Esta variável já vem do GigController@debugFinancials
        $cacheBrlDetails = $gig->cacheValueBrlDetails;
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Depuração Financeira da Gig #{{ $gig->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('gigs.show', $gig) }}" class="inline-block bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm mb-6">
                <i class="fas fa-arrow-left mr-1"></i> Voltar para Detalhes da Gig
            </a>

            {{-- ======================================================================= --}}
            {{-- Card 1: Inputs Usados nos Cálculos (Com Detalhes da Conversão)         --}}
            {{-- ======================================================================= --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Inputs Usados nos Cálculos</h3>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="py-2 font-semibold w-1/3 align-top">Valor Contrato Original:</td>
                                <td>{{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 font-semibold align-top">Valor Contrato (BRL):</td>
                                <td class="py-2">
                                    @if($cacheBrlDetails['value'] !== null)
                                        <div class="flex items-center space-x-2">
                                            <span class="font-semibold text-lg">R$ {{ number_format($cacheBrlDetails['value'], 2, ',', '.') }}</span>
                                            @if($cacheBrlDetails['type'] === 'projected')
                                                <span class="text-xs bg-yellow-100 text-yellow-800 p-1 rounded">Projeção</span>
                                            @else
                                                <span class="text-xs bg-green-100 text-green-800 p-1 rounded">Confirmado</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            (Taxa de câmbio {{ $cacheBrlDetails['type'] === 'projected' ? 'de projeção' : 'média efetiva' }} usada: {{ number_format($cacheBrlDetails['rate_used'], 4, ',', '.') ?? 'N/A' }})
                                        </div>

                                        {{-- NOVO: Detalhamento de como o valor BRL confirmado é composto --}}
                                        @if($cacheBrlDetails['type'] === 'confirmed' && $gig->currency !== 'BRL')
                                            <div class="mt-3 pt-2 border-t border-dashed dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 space-y-1">
                                                <p class="font-semibold">Composição do Valor BRL Confirmado:</p>
                                                @foreach($gig->payments->whereNotNull('confirmed_at') as $payment)
                                                    <div>
                                                        <span>- Parcela "{{ $payment->description }}" ({{ $payment->currency }} {{ number_format($payment->received_value_actual, 2, ',', '.') }}) com câmbio {{ number_format($payment->exchange_rate, 4, ',', '.') }} = </span>
                                                        <span class="font-medium">R$ {{ number_format($payment->received_value_actual * $payment->exchange_rate, 2, ',', '.') }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-red-500">Taxa de câmbio não disponível para cálculo</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><td class="py-2 font-semibold">Tipo Comissão Agência:</td><td>{{ $gig->agency_commission_type ?? 'N/D' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Taxa Comissão Agência (%):</td><td>{{ isset($gig->agency_commission_rate) ? number_format($gig->agency_commission_rate, 2, ',', '.') . '%' : 'N/A' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Tipo Comissão Booker:</td><td>{{ $gig->booker_id ? ($gig->booker_commission_type ?? 'N/D') : 'N/A' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Taxa Comissão Booker (%):</td><td>{{ isset($gig->booker_commission_rate) ? number_format($gig->booker_commission_rate, 2, ',', '.') . '%' : 'N/A' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ======================================================================= --}}
            {{-- Card 2: Análise de Pagamentos / Recebimentos (Com Valor BRL)         --}}
            {{-- ======================================================================= --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Análise de Pagamentos / Recebimentos</h3>
                    @if($gig->payments->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Descrição</th>
                                    <th class="px-4 py-2 text-right">Valor Devido</th>
                                    <th class="px-4 py-2 text-center">Vencimento</th>
                                    <th class="px-4 py-2 text-center">Status</th>
                                    <th class="px-4 py-2 text-right">Valor Recebido</th>
                                    <th class="px-4 py-2 text-center">Data Receb.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($gig->payments as $payment)
                                    <tr>
                                        <td class="px-4 py-2">{{ $payment->description }}</td>
                                        <td class="px-4 py-2 text-right">{{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-center">{{ $payment->due_date->isoFormat('L') }}</td>
                                        <td class="px-4 py-2 text-center"><x-status-badge :status="$payment->inferred_status" type="payment" /></td>
                                        <td class="px-4 py-2 text-right">
                                            @if($payment->confirmed_at)
                                                <span>{{ $payment->currency }} {{ number_format($payment->received_value_actual, 2, ',', '.') }}</span>
                                                {{-- NOVO: Exibe o valor convertido abaixo --}}
                                                @if($payment->currency !== 'BRL' && $payment->exchange_rate)
                                                    <small class="block italic text-gray-500 dark:text-gray-400">
                                                        (R$ {{ number_format($payment->received_value_actual * $payment->exchange_rate, 2, ',', '.') }})
                                                    </small>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-center">{{ $payment->received_date_actual?->isoFormat('L') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Nenhuma parcela de pagamento registrada para esta Gig.</p>
                    @endif
                </div>
            </div>

            {{-- Card de Despesas (como antes) --}}
            @include('gigs.debug._debug_costs_table', ['gig' => $gig])

            {{-- Card de Resultados do Service (como antes, mas agora com a exibição de moeda corrigida) --}}
            @include('gigs.debug._debug_service_results', ['calculations' => $calculations, 'gig' => $gig])

        </div>
    </div>
</x-app-layout>