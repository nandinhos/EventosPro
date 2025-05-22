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
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden max-w-lg mx-auto">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white">Detalhes para Emissão da Nota Fiscal</h3>
        </div>

        <div class="p-4 space-y-3 text-xs sm:text-sm">
    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Cálculo do Valor da NF:</h4>

    {{-- Valor do Contrato (Original BRL) --}}
    <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-400">Valor Contrato ({{ $gig->currency }}):</span>
        <span class="font-medium text-gray-800 dark:text-white">
            {{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}
            @if($gig->currency !== 'BRL')
                (aprox. R$ {{ number_format($gigCacheValueBrl, 2, ',', '.') }}) {{-- $gigCacheValueBrl é o Valor Contrato em BRL --}}
            @endif
        </span>
    </div>

    {{-- Total de TODAS as Despesas Confirmadas --}}
    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
        <span class="text-gray-600 dark:text-gray-400 block mb-1">(-) Total Despesas Confirmadas (Deduzidas da Base):</span>
        <div class="pl-2 space-y-1">
            @forelse($gig->costs->where('is_confirmed', true) as $cost)
                <div class="flex justify-between text-[11px] sm:text-xs">
                    <span class="text-gray-500 dark:text-gray-400">- {{ $cost->costCenter->name ?? 'N/A' }}: {{ $cost->description }}</span>
                    <span class="font-medium text-red-500 dark:text-red-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</span>
                </div>
            @empty
                <div class="text-gray-500 dark:text-gray-400 text-[11px] sm:text-xs">- Nenhuma despesa confirmada.</div>
            @endforelse
            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                <span class="text-gray-500 dark:text-gray-400">Total Geral Despesas Confirmadas:</span>
                <span class="text-red-500 dark:text-red-400">R$ {{ number_format($totalConfirmedExpensesBrl, 2, ',', '.') }}</span> {{-- Esta variável vem do controller --}}
            </div>
        </div>
    </div>

    {{-- Cachê Bruto (Base para Comissões) --}}
    <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
        <span class="text-gray-600 dark:text-gray-400">= Cachê Bruto (Base para Comissões):</span>
        <span class="font-medium text-gray-800 dark:text-white">R$ {{ number_format($calculatedGrossCashBrl, 2, ',', '.') }}</span> {{-- Esta variável vem do controller --}}
    </div>

    

    {{-- Cachê Líquido do Artista (antes do reembolso) --}}
    <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-3 -mx-3 rounded-t-md">
        <span class="text-gray-600 dark:text-gray-400 font-semibold">= Cachê Líquido do Artista (para NF):</span>
        <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($artistNetPayoutBeforeReimbursement, 2, ',', '.') }}</span> {{-- Esta variável vem do controller --}}
    </div>

    {{-- Despesas Pagas pelo Artista (Reembolsáveis, is_invoice = true) --}}
    @if($totalReimbursableExpensesBrl > 0)
        <div class="pt-2 mt-0 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-3 -mx-3">
            <span class="text-gray-600 dark:text-gray-400 block mb-1 font-semibold">(+) Reembolso Despesas Pagas pelo Artista:</span>
            <div class="pl-2 space-y-1">
                @foreach($gig->costs->where('is_confirmed', true)->where('is_invoice', true) as $cost)
                    <div class="flex justify-between text-[11px] sm:text-xs">
                        <span class="text-gray-500 dark:text-gray-400">- {{ $cost->costCenter->name ?? 'N/A' }}: {{ $cost->description }}</span>
                        <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                    <span class="text-gray-500 dark:text-gray-400">Total Reembolsável ao Artista:</span>
                    <span class="text-green-600 dark:text-green-400">R$ {{ number_format($totalReimbursableExpensesBrl, 2, ',', '.') }}</span> {{-- Esta variável vem do controller --}}
                </div>
            </div>
        </div>
    @endif

    {{-- VALOR FINAL DA NOTA FISCAL --}}
    <div class="flex justify-between items-center py-3 mt-0 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-3 -mx-3 rounded-b-md">
        <span class="text-md font-semibold text-gray-700 dark:text-gray-200">VALOR NOTA FISCAL:</span>
        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
            R$ {{ number_format($finalArtistInvoiceValueBrl, 2, ',', '.') }} {{-- Esta variável vem do controller --}}
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