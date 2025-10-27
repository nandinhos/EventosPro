<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Pendências</title>
    <style>
        @page { margin: 25px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #333; font-size: 10px; }
        
        .header { position: relative; text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .header .logo { position: absolute; top: -10px; left: 0; max-height: 100px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; color: #4a5568; }
        .header .filters { font-size: 11px; margin-top: 0; color: #718096; }
        .header .generation-date { font-size: 9px; font-style: italic; color: #a0aec0; margin-top: 5px; }
        
        .booker-header { background-color: #f7fafc; border: 1px solid #e2e8f0; padding: 8px 12px; font-size: 14px; font-weight: bold; margin-top: 20px; color: #2d3748; page-break-before: auto; }
        
        /* ***** INÍCIO DA CORREÇÃO NO CSS ***** */
        .gig-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; /* Espaçamento entre as tabelas de gig */
        }
        .gig-table > tbody > tr {
            page-break-inside: avoid !important; /* A diretiva importante está na linha (tr) */
        }
        .gig-table > tbody > tr > td { 
            border-bottom: 2px solid #e2e8f0; 
            padding: 10px 8px; 
            vertical-align: top;
        }
        /* ***** FIM DA CORREÇÃO NO CSS ***** */
        
        .artist-info-cell { width: 35%; }
        .artist-name { font-size: 14px; font-weight: bold; color: #4c1d95; margin-bottom: 2px; }
        .event-date { font-size: 10px; color: #718096; margin-bottom: 10px; }
        .financial-summary { border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 8px; font-size: 9px; }
        .financial-summary .label { color: #4a5568; }
        .financial-summary .value { font-weight: bold; }
        .financial-summary .received { color: #38a169; }
        .financial-summary .pending { color: #e53e3e; font-size: 12px; }
        .location-cell { width: 30%; font-size: 10px; color: #4a5568; }
        .payments-cell { width: 35%; }
        .payments-table { width: 100%; }
        .payments-table th, .payments-table td { font-size: 8px; padding: 2px 0; text-align: left; border: none; }
        .payments-table th { color: #a0aec0; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; font-weight: normal; }
        .payments-table .text-right { text-align: right; }
        .payments-table .text-center { text-align: center; }
        .due-date-past { color: #d9534f; font-weight: bold; }
        .status-badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-weight: bold; }
        .status-vencido { background-color: #fed7d7; color: #c53030; }
        .status-a_vencer { background-color: #ccebfb; color: #2b6cb0; }
        .status-confirmado { background-color: #c6f6d5; color: #2f855a; }
        
        /* Estilos para badges de status de contrato */
        .contract-status-badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-weight: bold; font-size: 8px; }
        .contract-status-assinado { background-color: #c6f6d5; color: #2f855a; }
        .contract-status-para_assinatura { background-color: #fef3c7; color: #d97706; }
        .contract-status-concluido { background-color: #c6f6d5; color: #2f855a; }
        .contract-status-expirado { background-color: #fed7d7; color: #c53030; }
        .contract-status-cancelado { background-color: #fed7d7; color: #c53030; }
        .contract-status-n_a { background-color: #e5e7eb; color: #6b7280; }
        .contract-status-rascunho { background-color: #e5e7eb; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
        <h1>Relatório de Gigs</h1>
        @if(!empty(array_filter($filters)))
            <p class="filters">
                Período: 
                {{ isset($filters['event_start_date']) ? \Carbon\Carbon::parse($filters['event_start_date'])->isoFormat('L') : 'N/A' }}
                a 
                {{ isset($filters['event_end_date']) ? \Carbon\Carbon::parse($filters['event_end_date'])->isoFormat('L') : 'N/A' }}
            </p>
        @endif
        <p class="generation-date" style="font-size: 8px; color: #9ca3af;">
            Gerado em: {{ now()->isoFormat('L LT') }}
            @if(auth()->check())
                por: {{ auth()->user()->name }}
            @endif
        </p>
    </div>

    @forelse ($gigsGroupedByBooker as $bookerName => $gigs)
        <div class="booker-header">{{ $bookerName }}</div>
        
        {{-- ***** INÍCIO DA CORREÇÃO NO HTML ***** --}}
        {{-- Agora o loop de gigs cria uma tabela para CADA gig --}}
        @foreach ($gigs as $gig)
            <table class="gig-table">
                <tbody>
                    @php
                        // Lógica de cálculo movida para dentro do loop
                        $totalReceivedThisGigBRL = $gig->payments->whereNotNull('confirmed_at')->sum(fn($p) => $p->currency === 'BRL' ? $p->received_value_actual : ($p->received_value_actual * ($p->exchange_rate_received_actual ?: ($p->exchange_rate ?: 1))));
                        $pendingOriginalCurrency = $gig->cache_value - $gig->payments->whereNotNull('confirmed_at')->where('currency', $gig->currency)->sum('received_value_actual');
                    @endphp
                    <tr>
                        <td class="artist-info-cell">
                            <div class="artist-name">{{ $gig->artist->name ?? 'N/A' }}</div>
                            <div class="event-date">{{ $gig->gig_date->isoFormat('L') }}</div>
                            <div class="financial-summary">
                                <div>
                                    <span class="label">Recebido:</span>
                                    <span class="value received">BRL {{ number_format($totalReceivedThisGigBRL, 2, ',', '.') }}</span>
                                </div>
                                <div>
                                    <span class="label">Pendente:</span>
                                    <span class="value pending">
                                        {{ $gig->currency }} {{ number_format(max(0, $pendingOriginalCurrency), 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="location-cell">
                            <div><strong>Local</strong></div>
                            <div>{{ $gig->location_event_details }}</div>
                            @php
                                $contractStatus = $gig->contract_status ?? 'rascunho';
                                $statusMap = [
                                    'assinado' => 'Assinado',
                                    'para_assinatura' => 'Para Assinatura',
                                    'concluido' => 'Concluído',
                                    'expirado' => 'Expirado',
                                    'cancelado' => 'Cancelado',
                                    'n/a' => 'N/A',
                                    'rascunho' => 'Rascunho'
                                ];
                                $statusTitle = $statusMap[strtolower($contractStatus)] ?? 'Desconhecido';
                                $statusClass = 'contract-status-' . str_replace('/', '_', strtolower($contractStatus));
                            @endphp
                            <div>
                                <span class="contract-status-badge {{ $statusClass }}">
                                    {{ $statusTitle }}
                                </span>
                            </div>
                        </td>

                        <td class="payments-cell">
                            <table class="payments-table">
                                <thead>
                                    <tr>
                                        <th>Vencimento</th>
                                        <th class="text-right">Valor</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($gig->payments->sortBy('due_date') as $payment)
                                        <tr>
                                            <td class="{{ $payment->inferred_status === 'vencido' ? 'due-date-past' : '' }}">
                                                {{ $payment->due_date->isoFormat('L') }}
                                            </td>
                                            <td class="text-right">
                                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                            </td>
                                            <td class="text-center">
                                                <span class="status-badge status-{{$payment->inferred_status}}">
                                                    {{ ucfirst(str_replace('_', ' ', $payment->inferred_status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        {{-- ***** FIM DA CORREÇÃO NO HTML ***** --}}
        
    @empty
        <p style="text-align: center; padding: 20px;">Nenhuma pendência encontrada para os filtros aplicados.</p>
    @endforelse
</body>
</html>