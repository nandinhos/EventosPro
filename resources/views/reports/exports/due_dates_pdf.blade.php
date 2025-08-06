<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relatório de Vencimentos - {{ now()->format('d/m/Y') }}</title>
    <style>
        @page { margin: 25px; }
        * {
            font-family: 'DejaVu Sans', sans-serif !important;
            color: #374151; /* Tom de cinza mais escuro */
        }

        body { font-size: 9px; }

        .header { text-align: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; position: relative; }
        .header .logo { position: absolute; top: -25px; left: 0; max-height: 100px; }
        .header .title { font-size: 18px; font-weight: bold; color: #1f2937; }
        .header .subtitle { font-size: 10px; color: #6b7280; }

        .filters { margin-bottom: 15px; font-size: 10px; border: 1px solid #e2e8f0; background-color: #f9fafb; padding: 8px; border-radius: 4px; }
        .filters p { margin: 2px 0; }
        .filters strong { color: #1f2937; }

        .summary-table { width: 100%; border-spacing: 10px 0; margin-bottom: 20px; }
        .summary-card { width: 100%; padding: 8px; font-size: 10px; border-left: 4px solid; }
        .summary-card.vencido { border-color: #ef4444; background-color: #fee2e2; }
        .summary-card.a_vencer { border-color: #f59e0b; background-color: #fef3c7; }
        .summary-card .label { font-weight: bold; }
        .summary-card .value { font-size: 11px; }

        .main-table { width: 100%; border-collapse: collapse; }
        .main-table th { background-color: #f3f4f6; text-align: left; padding: 6px; border: 1px solid #e5e7eb; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        .main-table td { padding: 6px; border: 1px solid #e5e7eb; vertical-align: top; }
        .main-table .text-right { text-align: right; }
        .main-table .font-semibold { font-weight: bold; }
        .text-red { color: #dc2626; }
        .text-yellow { color: #d97706; }
        .text-xs { font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
        <div class="title">Relatório de Vencimentos</div>
        <p class="generation-date" style="font-size: 8px; color: #9ca3af;">
            Gerado em: {{ now()->format('d/m/Y H:i') }}
            @if(auth()->check())
                por: {{ auth()->user()->name }}
            @endif
        </p>
    

    @if (!empty($filters))
        
            
            @if(isset($filters['start_date']) || isset($filters['end_date']))
                <p><strong>Período de Vencimento:</strong> {{ isset($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'Início' }} a {{ isset($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'Fim' }}</p>
            @endif
            @if(isset($filters['status']))
                <p><strong>Status:</strong> {{ ['a_vencer' => 'A Vencer', 'vencido' => 'Vencido'][$filters['status']] ?? 'Todos (Pendentes)' }}</p>
            @endif
            @if(isset($filters['currency']))
                <p><strong>Moeda:</strong> {{ $filters['currency'] }}</p>
            @endif
        
    @endif
    </div>

    {{-- Cards de Resumo em uma tabela --}}
    <table class="summary-table">
        <tr>
            <td style="width: 50%; padding: 0;">
                <div class="summary-card vencido">
                    <span class="label">Vencidos:</span>
                    {{ $totals['vencido']['count'] ?? 0 }} {{ Str::plural('parcela', $totals['vencido']['count'] ?? 0) }} |
                    <span class="label">Total:</span>
                    <span class="value">R$ {{ number_format($totals['vencido']['amount_brl'] ?? 0, 2, ',', '.') }}</span>
                </div>
            </td>
            <td style="width: 50%; padding: 0;">
                <div class="summary-card a_vencer">
                    <span class="label">A Vencer:</span>
                    {{ $totals['a_vencer']['count'] ?? 0 }} {{ Str::plural('parcela', $totals['a_vencer']['count'] ?? 0) }} |
                    <span class="label">Total:</span>
                    <span class="value">R$ {{ number_format($totals['a_vencer']['amount_brl'] ?? 0, 2, ',', '.') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Vencimento</th>
                <th>Booker</th>
                <th>Artista</th>
                <th style="width: 25%;">Local / Evento</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groupedPayments as $status => $payments)
                @foreach($payments as $payment)
                    @php
                        $gig = $payment->gig;
                        $statusInfo = ['vencido' => 'Vencido', 'a_vencer' => 'A Vencer'][$status] ?? 'Desconhecido';
                    @endphp
                    <tr>
                        <td class="{{ $status === 'vencido' ? 'text-red' : 'text-yellow' }} font-semibold">{{ $statusInfo }}</td>
                        <td>
                            {{ $payment->due_date?->format('d/m/Y') }}
                            <div class="text-xs">{{ $payment->due_date?->diffForHumans() }}</div>
                        </td>
                        <td>{{ $gig?->booker?->name ?? 'Agência' }}</td>
                        <td>{{ $gig?->artist?->name ?? 'N/A' }}</td>
                        <td>
                            {{ $payment->description ?: 'Parcela' }}
                            <br><span class="text-xs">{{ Str::limit($gig?->location_event_details, 60) }}</span>
                        </td>
                        <td class="text-right">
                            <div class="font-semibold">{{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}</div>
                            @if($payment->currency !== 'BRL')
                                <div class="text-xs">~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">Nenhum vencimento encontrado para os filtros selecionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>