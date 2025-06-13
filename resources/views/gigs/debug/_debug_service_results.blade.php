@props(['calculations', 'gig'])

<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <h3 class="text-lg font-medium border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">Resultados do `GigFinancialCalculatorService`</h3>
         <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-2 text-left w-1/3 font-medium text-gray-500 dark:text-gray-400 uppercase">Método do Service</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Valor Calculado</th>
                        <th class="px-4 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Observação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($calculations as $methodName => $value)
                        @if (str_starts_with($methodName, 'divider'))
                            <tr><td colspan="3" class="py-1 bg-gray-100 dark:bg-gray-700/50"></td></tr>
                        @else
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs">{{ $methodName }}()</td>
                                <td class="px-4 py-2 text-right font-semibold">
                                    @if(str_contains($methodName, 'InOriginalCurrency'))
                                        {{ $gig->currency }} {{ number_format($value, 2, ',', '.') }}
                                    @else
                                        R$ {{ number_format($value, 2, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-500 italic text-xs">
                                    @switch($methodName)
                                        @case('calculateTotalReceivedInOriginalCurrency')
                                            Total efetivamente recebido de pagamentos confirmados.
                                            @break
                                        @case('calculateTotalReceivableInOriginalCurrency')
                                            Soma das parcelas com pagamento ainda pendente.
                                            @break
                                        @case('calculatePendingBalanceInOriginalCurrency')
                                            Valor do Contrato - Total Recebido. O que falta para quitar o contrato.
                                            @break
                                        @case('calculateTotalConfirmedExpensesBrl')
                                            Soma de todas as despesas com status "Confirmada".
                                            @break
                                        @case('calculateTotalReimbursableExpensesBrl')
                                            Soma das despesas confirmadas E marcadas como "Reembolsável (NF)".
                                            @break
                                        @case('calculateGrossCashBrl')
                                            Valor Contrato BRL - Total Despesas Confirmadas BRL. **(Base das Comissões)**
                                            @break
                                        @case('calculateAgencyGrossCommissionBrl')
                                            Comissão da agência sobre o Cachê Bruto.
                                            @break
                                        @case('calculateBookerCommissionBrl')
                                            Comissão do booker sobre o Cachê Bruto.
                                            @break
                                        @case('calculateAgencyNetCommissionBrl')
                                            Comissão Agência Bruta - Comissão Booker.
                                            @break
                                        @case('calculateArtistNetPayoutBrl')
                                            Cachê Bruto - Comissão Agência Bruta. (Valor para o artista antes dos reembolsos)
                                            @break
                                        @case('calculateArtistInvoiceValueBrl')
                                            Cachê Líquido Artista + Despesas Reembolsáveis. (Valor Final da NF)
                                            @break
                                        @default
                                            -
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