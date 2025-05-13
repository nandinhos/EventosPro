<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                Solicitação de Nota Fiscal para Artista - Gig #{{ $gig->id }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Artista: <span class="font-medium">{{ $gig->artist->name ?? 'N/A' }}</span> |
                Evento: {{ $gig->gig_date->format('d/m/Y') }} - {{ $gig->location_event_details }}
            </p>
        </div>
        <a href="{{ route('gigs.show', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para Detalhes da Gig
        </a>
    </div>

    {{-- Card com Detalhes para NF --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Detalhes para Emissão da Nota Fiscal</h3>
        </div>

        <div class="p-6 space-y-4 text-sm">
            {{-- Resumo Financeiro para NF --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-2">
                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Cálculo do Valor da NF:</h4>

                {{-- Valor do Contrato/Cachê Bruto --}}
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Valor Contrato (Cachê Bruto {{ $gig->currency }}):</span>
                    <span class="font-medium text-gray-800 dark:text-white">
                        {{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}
                    </span>
                </div>
                @if($gig->currency !== 'BRL')
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400 ml-4">(Equivalente em BRL para cálculo):</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gigCacheValueBrl, 2, ',', '.') }}</span>
                    </div>
                @endif

                {{-- Despesas Confirmadas Detalhadas --}}
                @if($confirmedExpensesGrouped->isNotEmpty())
                    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-400 block mb-1">(-) Despesas Confirmadas (Reembolsáveis pela Agência/Cliente):</span>
                        <div class="pl-4 space-y-1">
                            @foreach($confirmedExpensesGrouped as $costCenterName => $totalCostCenterValue)
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $costCenterName }}:</span>
                                    <span class="font-medium text-red-500 dark:text-red-400">R$ {{ number_format($totalCostCenterValue, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Total Despesas:</span>
                                <span class="text-red-500 dark:text-red-400">R$ {{ number_format($totalConfirmedExpensesBrl, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-400">(-) Despesas Confirmadas: R$ 0,00</span>
                    </div>
                @endif

                {{-- Cachê Base para Comissões --}}
                <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                    <span class="text-gray-600 dark:text-gray-400">= Cachê Base (após despesas):</span>
                    <span class="font-medium text-gray-800 dark:text-white">R$ {{ number_format($gigCacheValueBrl - $totalConfirmedExpensesBrl, 2, ',', '.') }}</span>
                </div>

                {{-- Comissão Agência --}}
                @if(($agencyTotalCommissionOnGig ?? 0) > 0 || ($gig->agency_commission_rate ?? 0) > 0)
                    <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-400">
                            (-) Comissão Agência
                            @if($gig->agency_commission_type === 'percent' && $gig->agency_commission_rate)
                                ({{ number_format($gig->agency_commission_rate, 1) }}%):
                            @elseif($gig->agency_commission_type === 'fixed')
                                (Valor Fixo):
                            @endif
                        </span>
                        <span class="font-medium text-red-500 dark:text-red-400">R$ {{ number_format($agencyTotalCommissionOnGig, 2, ',', '.') }}</span>
                    </div>
                @endif

                {{-- Valor Líquido Final para Nota Fiscal --}}
                <div class="flex justify-between items-center py-3 mt-3 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-3 -mx-3 rounded-b-md">
                    <span class="text-md font-semibold text-gray-700 dark:text-gray-200">VALOR LÍQUIDO PARA NOTA FISCAL DO ARTISTA:</span>
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">R$ {{ number_format($netArtistCacheToReceive, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Informações Adicionais para a NF (se necessário) --}}
            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-200 mb-2">Dados Adicionais para NF:</h4>
                <p class="text-gray-600 dark:text-gray-400"><strong>Artista (Prestador):</strong> {{ $gig->artist->name ?? 'N/A' }}</p>
                <p class="text-gray-600 dark:text-gray-400"><strong>CPF/CNPJ Artista:</strong> [CAMPO A ADICIONAR AO MODELO ARTIST OU BUSCAR DE OUTRO LOCAL]</p>
                <p class="text-gray-600 dark:text-gray-400"><strong>Referência:</strong> Apresentação artística em {{ $gig->location_event_details }} no dia {{ $gig->gig_date->format('d/m/Y') }}. Contrato: {{ $gig->contract_number ?? 'N/A' }}.</p>
                {{-- Outros dados como CNPJ da agência, etc. --}}
            </div>

            {{-- Ações (Ex: Botão "Marcar como NF Solicitada", "Imprimir", etc.) --}}
            <div class="mt-6 flex justify-end space-x-3">
                {{-- <form action="{{ route('gigs.request-nf.store', $gig) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm">
                        <i class="fas fa-check-circle mr-2"></i> Marcar como NF Solicitada
                    </button>
                </form> --}}
                <button type="button" onclick="window.print();" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                    <i class="fas fa-print mr-2"></i> Imprimir / Gerar PDF
                </button>
            </div>
        </div>
    </div>
</x-app-layout>