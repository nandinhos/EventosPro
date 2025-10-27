@props(['payment'])

@php
    $gig = $payment->gig;
    $contractStatus = $gig?->contract_status ?? 'rascunho';

    // Mapeamento de status do contrato
    $statusMap = [
        'assinado' => ['title' => 'Assinado', 'color' => 'green'],
        'pendente' => ['title' => 'Pendente', 'color' => 'orange'],
        'cancelado' => ['title' => 'Cancelado', 'color' => 'red'],
        'rascunho' => ['title' => 'Rascunho', 'color' => 'gray'],
    ];

    $statusInfo = $statusMap[$contractStatus] ?? $statusMap['rascunho'];
@endphp

<tr class="border-b">
    {{-- Nome --}}
    <td class="px-4 py-2 text-sm text-gray-800">
        {{ $payment->user->name ?? 'Usuário não informado' }}
    </td>

    {{-- Valor --}}
    <td class="px-4 py-2 text-sm text-gray-800">
        R$ {{ number_format($payment->amount, 2, ',', '.') }}
    </td>

    {{-- Data --}}
    <td class="px-4 py-2 text-sm text-gray-800">
        {{ $payment->created_at?->isoFormat('L') ?? '-' }}
    </td>

    {{-- Status --}}
    <td class="px-4 py-2 text-sm">
        <span class="inline-block px-2 py-1 rounded text-white bg-{{ $statusInfo['color'] }}-500">
            {{ $statusInfo['title'] }}
        </span>
    </td>

    {{-- Observação (com quebra de linha) --}}
    <td class="px-4 py-2 text-sm text-gray-800 whitespace-pre-line">
        {{ $payment->note ?? '-' }}
    </td>
</tr>
