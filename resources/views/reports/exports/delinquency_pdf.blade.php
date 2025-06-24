<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Pendências</title>
    <style>
        @page { margin: 20px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #333; font-size: 10px; }
        .header h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .header p { font-size: 11px; text-align: center; margin-top: 0; }
        .booker-header { background-color: #e3e3ff; padding: 8px; font-size: 14px; font-weight: bold; margin-top: 20px; }
        .gig-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; page-break-inside: avoid; }
        .gig-table td { border-bottom: 1px solid #eee; padding: 6px 4px; vertical-align: top; }
        .gig-info { font-weight: bold; }
        .gig-location { font-size: 9px; font-style: italic; color: #555; }
        .artist-info { font-weight: bold; }
        .date-info { font-size: 9px; color: #555; }
        .sub-table { width: 100%; }
        .sub-table th, .sub-table td { font-size: 9px; padding: 2px; border: none; }
        .sub-table th { text-align: left; color: #777; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        .sub-table .text-right { text-align: right; }
        .sub-table .text-center { text-align: center; }
        .due-date-past { color: #d9534f; }
        .summary-footer { font-size: 9px; }
        .summary-footer .label { color: #555; }
        .summary-footer .value { font-weight: bold; }
        .summary-footer .received { color: #5cb85c; }
        .summary-footer .pending { color: #d9534f; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Pendências por Booker</h1>
        @if(!empty(array_filter($filters)))
            <p>Filtros Aplicados | Período do Evento: {{ $filters['event_start_date'] ?? 'N/A' }} a {{ $filters['event_end_date'] ?? 'N/A' }}</p>
        @endif
    </div>

    @forelse ($gigsGroupedByBooker as $bookerName => $gigs)
        <div class="booker-header">{{ $bookerName }}</div>
        
        @foreach ($gigs as $gig)
            @php
                // Lógica de cálculo copiada da view principal para consistência
                $cacheBrlDetails = $gig->cacheValueBrlDetails;
                $totalReceivedThisGigBRL = $gig->payments->whereNotNull('confirmed_at')->sum(fn($p) => $p->currency === 'BRL' ? $p->received_value_actual : ($p->received_value_actual * ($p->exchange_rate_received_actual ?: ($p->exchange_rate ?: 1))));
                $pendingOriginalCurrency = $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual');
            @endphp
            <table class="gig-table">
                <tr>
                    <td style="width: 40%;">
                        <div class="gig-info">{{ $gig->contract_number ?: 'Gig #'.$gig->id }}</div>
                        <div class="gig-location">{{ Str::limit($gig->location_event_details, 60) }}</div>
                    </td>
                    <td style="width: 20%;">
                        <div class="artist-info">{{ $gig->artist->name ?? 'N/A' }}</div>
                        <div class="date-info">{{ $gig->gig_date->format('d/m/Y') }}</div>
                    </td>
                    <td style="width: 40%;">
                        <table class="sub-table">
                            <thead>
                                <tr>
                                    <th>Vencimento</th>
                                    <th class="text-right">Valor</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gig->payments->whereNull('confirmed_at') as $payment)
                                    <tr>
                                        <td class="{{ $payment->due_date->isPast() ? 'due-date-past' : '' }}">{{ $payment->due_date->format('d/m/Y') }}</td>
                                        <td class="text-right">{{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ $payment->inferred_status === 'vencido' ? 'Vencido' : 'A Vencer' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="summary-footer">
                                <tr>
                                    <td class="label pt-2">Total Recebido:</td>
                                    <td class="value received text-right pt-2" colspan="2">R$ {{ number_format($totalReceivedThisGigBRL, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Saldo Pendente Gig:</td>
                                    <td class="value pending text-right" colspan="2">{{ $gig->currency }} {{ number_format(max(0, $pendingOriginalCurrency), 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>
            </table>
        @endforeach
    @empty
        <p>Nenhuma pendência encontrada para os filtros aplicados.</p>
    @endforelse
</body>
</html>