<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Depuração Financeira da Gig #{{ $gig->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Botão para voltar à página show da Gig --}}
            <a href="{{ route('gigs.show', $gig) }}" class="inline-block bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm mb-6">
                <i class="fas fa-arrow-left mr-1"></i> Voltar para Detalhes da Gig
            </a>

            {{-- Card 1: Inputs para os Cálculos --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Inputs Usados nos Cálculos</h3>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr><td class="py-2 font-semibold w-1/3">Valor Contrato Original:</td><td>{{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}</td></tr>
                            <tr><td class="py-2 font-semibold">Valor Contrato (BRL):</td><td>R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}</td></tr>
                            <tr><td class="py-2 font-semibold">Tipo Comissão Agência:</td><td>{{ $gig->agency_commission_type ?? 'N/D' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Taxa Comissão Agência (%):</td><td>{{ isset($gig->agency_commission_rate) ? number_format($gig->agency_commission_rate, 2, ',', '.') . '%' : 'N/A' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Valor Fixo Agência (Input):</td><td>{{ $gig->agency_commission_type == 'FIXED' ? 'R$ ' . number_format($gig->agency_commission_value, 2, ',', '.') : 'N/A' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Tipo Comissão Booker:</td><td>{{ $gig->booker_id ? ($gig->booker_commission_type ?? 'N/D') : 'N/A' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Taxa Comissão Booker (%):</td><td>{{ isset($gig->booker_commission_rate) ? number_format($gig->booker_commission_rate, 2, ',', '.') . '%' : 'N/A' }}</td></tr>
                            <tr><td class="py-2 font-semibold">Valor Fixo Booker (Input):</td><td>{{ $gig->booker_commission_type == 'FIXED' ? 'R$ ' . number_format($gig->booker_commission_value, 2, ',', '.') : 'N/A' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            {{-- NOVO Card: Detalhes das Parcelas/Pagamentos --}}
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
                                        <td class="px-4 py-2 text-center">{{ $payment->due_date->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <x-status-badge :status="$payment->inferred_status" type="payment" />
                                        </td>
                                        <td class="px-4 py-2 text-right">{{ $payment->received_value_actual ? $payment->currency.' '.number_format($payment->received_value_actual, 2, ',', '.') : '-' }}</td>
                                        <td class="px-4 py-2 text-center">{{ $payment->received_date_actual?->format('d/m/Y') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Nenhuma parcela de pagamento registrada para esta Gig.</p>
                    @endif
                </div>
            </div>

            {{-- Card 2: Detalhes das Despesas (Costs) --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Despesas Consideradas</h3>
                    @if($gig->costs->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-2 text-left">Descrição</th>
                                    <th class="px-4 py-2 text-right">Valor (BRL)</th>
                                    <th class="px-4 py-2 text-center">Confirmada?</th>
                                    <th class="px-4 py-2 text-center">Reembolsável? (NF)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($gig->costs as $cost)
                                    <tr>
                                        <td class="px-4 py-2">{{ $cost->description }}</td>
                                        <td class="px-4 py-2 text-right">R$ {{ number_format($cost->value, 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-center">{{ $cost->is_confirmed ? 'Sim' : 'Não' }}</td>
                                        <td class="px-4 py-2 text-center">{{ $cost->is_invoice ? 'Sim' : 'Não' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>Nenhuma despesa registrada para esta Gig.</p>
                    @endif
                </div>
            </div>

            {{-- Card 3: Resultados dos Cálculos do Service --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Resultados do `GigFinancialCalculatorService`</h3>
                     <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-2 text-left w-1/3">Método do Service</th>
                                <th class="px-4 py-2 text-right">Valor Calculado</th>
                                <th class="px-4 py-2 text-left">Observação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($calculations as $methodName => $value)
                                @if (str_starts_with($methodName, 'divider'))
                                    <tr><td colspan="3" class="py-1 bg-gray-100 dark:bg-gray-700/50"></td></tr>
                                @else
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $methodName }}()</td>
                                        <td class="px-4 py-2 text-right font-semibold">{{ $gig->currency }} {{ number_format($value, 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-gray-500 italic text-xs">
                                            @switch($methodName)
                                                @case('calculateTotalReceivedInOriginalCurrency') Total efetivamente recebido de pagamentos confirmados. @break
                                                @case('calculateTotalReceivableInOriginalCurrency') Soma das parcelas com pagamento ainda pendente. @break
                                                @case('calculatePendingBalanceInOriginalCurrency') Valor do Contrato - Total Recebido. O que falta para quitar o contrato. @break
                                                @case('calculateTotalConfirmedExpensesBrl') Soma de todas as despesas com status "Confirmada". @break
                                                @case('calculateTotalReimbursableExpensesBrl') Soma das despesas confirmadas E marcadas como "Reembolsável (NF)". @break
                                                @case('calculateGrossCashBrl') Valor Contrato BRL - Total Despesas Confirmadas BRL. **(Base das Comissões)** @break
                                                @case('calculateAgencyGrossCommissionBrl') Comissão da agência sobre o Cachê Bruto. @break
                                                @case('calculateBookerCommissionBrl') Comissão do booker sobre o Cachê Bruto. @break
                                                @case('calculateAgencyNetCommissionBrl') Comissão Agência Bruta - Comissão Booker. @break
                                                @case('calculateArtistNetPayoutBrl') Cachê Bruto - Comissão Agência Bruta. (Valor para o artista antes dos reembolsos) @break
                                                @case('calculateArtistInvoiceValueBrl') Cachê Líquido Artista + Despesas Reembolsáveis. (Valor Final da NF) @break
                                                @default -
                                            @endswitch
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>