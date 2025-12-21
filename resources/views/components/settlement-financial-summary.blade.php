{{--
    Componente: x-settlement-financial-summary
    Exibe o cálculo do valor da NF de forma consistente.
    Usa GigFinancialCalculatorService para todos os cálculos (Single Source of Truth).
    
    Props:
    - $gig: Modelo Gig (com relações gigCosts carregadas)
    - $compact: Se true, versão compacta sem título (default: false)
--}}
@props([
    'gig',
    'compact' => false
])

@php
    use App\Services\GigFinancialCalculatorService;
    use Illuminate\Support\Facades\App;
    
    // Carregar custos se não estiverem carregados
    if (!$gig->relationLoaded('gigCosts')) {
        $gig->load('gigCosts.costCenter');
    }
    
    // === CÁLCULOS via Service (Single Source of Truth) ===
    $financialCalculator = App::make(GigFinancialCalculatorService::class);
    
    $gigCacheValueBrl = $gig->cache_value_brl ?? $gig->cache_value;
    $totalConfirmedExpensesBrl = $financialCalculator->calculateTotalConfirmedExpensesBrl($gig);
    $calculatedGrossCashBrl = $financialCalculator->calculateGrossCashBrl($gig);
    $artistNetPayoutBeforeReimbursement = $financialCalculator->calculateArtistNetPayoutBrl($gig);
    $totalReimbursableExpensesBrl = $financialCalculator->calculateTotalReimbursableExpensesBrl($gig);
    $finalArtistInvoiceValueBrl = $financialCalculator->calculateArtistInvoiceValueBrl($gig);
    
    // Despesas confirmadas para exibição detalhada
    $confirmedCosts = $gig->gigCosts->where('is_confirmed', true);
    $reimbursableCosts = $confirmedCosts->where('is_invoice', true);
@endphp

<div class="space-y-3 text-xs sm:text-sm" {{ $attributes }}>
    @if(!$compact)
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Cálculo do Valor da NF:</h4>
    @endif

    {{-- Valor do Contrato --}}
    <div class="flex justify-between">
        <span class="text-gray-600 dark:text-gray-400">Valor Contrato ({{ $gig->currency }}):</span>
        <span class="font-medium text-gray-800 dark:text-white">
            {{ $gig->currency }} {{ number_format($gig->cache_value ?? 0, 2, ',', '.') }}
        </span>
    </div>

    {{-- Total Despesas Confirmadas --}}
    <div class="pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
        <span class="text-gray-600 dark:text-gray-400 block mb-1">(-) Total Despesas Confirmadas (Deduzidas da Base):</span>
        <div class="pl-2 space-y-1">
            @forelse($confirmedCosts as $cost)
                <div class="flex justify-between text-[11px] sm:text-xs">
                    <span class="text-gray-500 dark:text-gray-400">- {{ $cost->costCenter->name ?? 'N/A' }}:</span>
                    <span class="font-medium text-red-500 dark:text-red-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</span>
                </div>
            @empty
                <div class="text-gray-500 dark:text-gray-400 text-[11px] sm:text-xs">- Nenhuma despesa confirmada.</div>
            @endforelse
            <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                <span class="text-gray-500 dark:text-gray-400">Total Geral Despesas Confirmadas:</span>
                <span class="text-red-500 dark:text-red-400">R$ {{ number_format($totalConfirmedExpensesBrl, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Cachê Bruto --}}
    <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50">
        <span class="text-gray-600 dark:text-gray-400">= Cachê Bruto (Base para Comissões):</span>
        <span class="font-medium text-gray-800 dark:text-white">R$ {{ number_format($calculatedGrossCashBrl, 2, ',', '.') }}</span>
    </div>

    {{-- Cachê Líquido do Artista --}}
    <div class="flex justify-between pt-2 mt-2 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-3 -mx-3 rounded-t-md">
        <span class="text-gray-600 dark:text-gray-400 font-semibold">= Cachê Líquido do Artista (para NF):</span>
        <span class="font-semibold text-gray-800 dark:text-white">R$ {{ number_format($artistNetPayoutBeforeReimbursement, 2, ',', '.') }}</span>
    </div>

    {{-- Despesas Reembolsáveis (se houver) --}}
    @if($totalReimbursableExpensesBrl > 0)
        <div class="pt-2 mt-0 border-t border-gray-100 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-700/30 px-3 -mx-3">
            <span class="text-gray-600 dark:text-gray-400 block mb-1 font-semibold">(+) Reembolso Despesas Pagas pelo Artista:</span>
            <div class="pl-2 space-y-1">
                @foreach($reimbursableCosts as $cost)
                    <div class="flex justify-between text-[11px] sm:text-xs">
                        <span class="text-gray-500 dark:text-gray-400">- {{ $cost->costCenter->name ?? 'N/A' }}:</span>
                        <span class="font-medium text-green-600 dark:text-green-400">R$ {{ number_format($cost->value, 2, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex justify-between text-xs font-semibold pt-1 border-t border-dashed border-gray-200 dark:border-gray-600 mt-1">
                    <span class="text-gray-500 dark:text-gray-400">Total Reembolsável ao Artista:</span>
                    <span class="text-green-600 dark:text-green-400">R$ {{ number_format($totalReimbursableExpensesBrl, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- VALOR NOTA FISCAL --}}
    <div class="flex justify-between items-center py-3 mt-0 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700/50 px-3 -mx-3 rounded-b-md">
        <span class="text-md font-semibold text-gray-700 dark:text-gray-200">VALOR NOTA FISCAL:</span>
        <span class="text-xl font-bold text-primary-600 dark:text-primary-400">
            R$ {{ number_format($finalArtistInvoiceValueBrl, 2, ',', '.') }}
        </span>
    </div>
</div>
