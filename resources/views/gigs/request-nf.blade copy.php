<x-app-layout>
    {{-- Cabeçalho da Página --}}
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                Solicitação de Nota Fiscal para Artista - Gig #{{ $gig->id }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Artista: <span class="font-medium">{{ $gig->artist->name ?? 'N/A' }}</span> |
                Evento: {{ $gig->gig_date->isoFormat('L') }} - {{ $gig->location_event_details }}
            </p>
        </div>
        <a href="{{ route('gigs.show', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Voltar para Detalhes da Gig
        </a>
    </div>

    {{-- Card com Detalhes para NF --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden max-w-lg mx-auto">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Detalhes para Emissão da Nota Fiscal</h3>
        </div>

        <div class="p-4 space-y-3 text-xs sm:text-sm">
            {{-- Resumo Financeiro para NF --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 space-y-2">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Cálculo do Valor da NF:</h4>

                {{-- Valor do Contrato/Cachê Bruto --}}
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Valor Contrato (Cachê Bruto {{ $gig->currency }}):</span>
                    <span class="font-medium text-gray-800 dark:text-white">
                        {{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}
                    </span>
                </div>
                @if($gig->currency !== 'BRL')
                    <div class="flex justify-between text-[11px] sm:text-xs">
                        <span class="text-gray-500 dark:text-gray-400 ml-4">(Equivalente em BRL para cálculo):</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">R$ {{ number_format($gigCacheValueBrl, 2, ',', '.') }}</span>
                    </div>
                @endif

                {{-- Despesas Confirmadas --}}
                @php
                    // Filtra todas as despesas confirmadas
                    $confirmedExpenses = $gig->costs
                        ->where('is_confirmed', true)
                        ->groupBy('costCenter.name');
                    
                    $totalConfirmedExpenses = $confirmedExpenses
                        ->map(function($group) {
                            return $group->sum('value');
                        })->sum();

                    // Filtra apenas as despesas confirmadas que serão reembolsadas via NF
                    $reimbursableExpenses = $gig->costs
                        ->where('is_confirmed', true)
                        ->where('is_invoice', true)
                        ->groupBy('costCenter.name');
                    
                    $totalReimbursableExpenses = $reimbursableExpenses
                        ->map(function($group) {
                            return $group->sum('value');
                        })->sum();
                @endphp

                @if($confirmedExpenses->isNotEmpty())
                    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-400 block mb-1">(-) Despesas Confirmadas:</span>
                        <div class="pl-2 space-y-1">
                            @foreach($confirmedExpenses as $costCenterName => $costsInGroup)
                                <div class="flex justify-between text-[11px] sm:text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $costCenterName }}:</span>
                                    <span class="font-medium text-red-500 dark:text-red-400">R$ {{ number_format($costsInGroup->sum('value'), 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Total Despesas Confirmadas:</span>
                                <span class="text-red-500 dark:text-red-400">R$ {{ number_format($totalConfirmedExpenses, 2, ',', '.') }}</span>
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
                    <span class="font-medium text-gray-800 dark:text-white">R$ {{ number_format($gigCacheValueBrl - $totalConfirmedExpenses, 2, ',', '.') }}</span>
                </div>

                {{-- Cachê do Artista --}}
                <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                    <span class="text-gray-600 dark:text-gray-400">
                        Cachê Artista ({{ number_format($gig->artist_commission_rate ?? 80, 1) }}%):
                    </span>
                    <span class="font-medium text-gray-800 dark:text-white">R$ {{ number_format(($gigCacheValueBrl - $totalConfirmedExpenses) * ($gig->artist_commission_rate ?? 80) / 100, 2, ',', '.') }}</span>
                </div>

                {{-- Despesas Reembolsáveis --}}
                @if($reimbursableExpenses->isNotEmpty())
                    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-400 block mb-1">(+) Despesas Reembolsáveis:</span>
                        <div class="pl-2 space-y-1">
                            @foreach($reimbursableExpenses as $costCenterName => $costsInGroup)
                                <div class="flex justify-between text-[11px] sm:text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">- {{ $costCenterName }}:</span>
                                    <span class="font-medium text-green-500 dark:text-green-400">R$ {{ number_format($costsInGroup->sum('value'), 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                                <span class="text-gray-500 dark:text-gray-400">Total Despesas Reembolsáveis:</span>
                                <span class="text-green-500 dark:text-green-400">R$ {{ number_format($totalReimbursableExpenses, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Valor Líquido Final para Nota Fiscal --}}
                <div class="flex justify-between items-center py-3 mt-3 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-3 -mx-3 rounded-b-md">
                    <span class="text-md font-semibold text-gray-700 dark:text-gray-200">VALOR NOTA FISCAL:</span>
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
                        R$ {{ number_format(
                            ($gigCacheValueBrl - $totalConfirmedExpenses) * ($gig->artist_commission_rate ?? 80) / 100 + $totalReimbursableExpenses,
                            2,
                            ',',
                            '.'
                        ) }}
                    </span>
                </div>
            </div>

           

        </div>
    </div>

    {{-- Ações (Ex: Botão "Marcar como NF Solicitada", "Imprimir", etc.) --}}
    <div class="mt-6 flex justify-end space-x-3">
        <button
            type="button"
            onclick="captureSnapshot()"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm"
        >
            <i class="fas fa-camera mr-2"></i> Capturar Snapshot
        </button>
    </div>

            {{-- Scripts para HTML2Canvas --}}
            <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
            <script>
                function captureSnapshot() {
                    const element = document.querySelector('.bg-white.dark\\:bg-gray-800.rounded-xl');
                    
                    html2canvas(element, {
                        scale: 2, // Melhor qualidade
                        useCORS: true,
                        backgroundColor: null
                    }).then(canvas => {
                        // Converter para imagem
                        const image = canvas.toDataURL('image/png');
                        
                        // Criar link temporário para download
                        const link = document.createElement('a');
                        link.download = 'demonstrativo-nf-gig-{{ $gig->id }}.png';
                        link.href = image;
                        
                        // Simular clique para download
                        link.click();
                    });
                }
            </script>
        </div>
    </div>
</x-app-layout>