{{-- ... (Início do componente) ... --}}
<div class="p-6">
    @if($payments->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum registro de pagamento recebido encontrado para esta Gig.</p>
    @else
        <ul class="space-y-3">
            @foreach($payments as $payment)
                <li class="flex justify-between items-center text-sm border-b border-gray-100 dark:border-gray-700 pb-2">
                    <div>
                        <span class="font-medium text-gray-800 dark:text-gray-200">
                            {{-- Usar 0.00 se o valor for nulo --}}
                            R$ {{ number_format($payment->received_value ?? 0, 2, ',', '.') }}
                            @if($payment->currency != 'BRL') ({{ $payment->currency }}) @endif
                        </span>
                        <span class="text-gray-500 dark:text-gray-400 ml-2">
                            {{ $payment->description ?? 'Pagamento Recebido' }}
                        </span>
                         <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{-- !! CORREÇÃO AQUI !! --}}
                            Recebido em: {{ $payment->received_date ? $payment->received_date->format('d/m/Y') : 'Data não informada' }}
                        </span>
                         {{-- Adicionar Notas do Pagamento se houver --}}
                         @if($payment->notes)
                            <span class="block text-xs text-gray-500 dark:text-gray-400 italic mt-1">Nota: {{ $payment->notes }}</span>
                         @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        {{-- TODO: Ações --}}
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
{{-- ... (Fim do componente) ... --}}