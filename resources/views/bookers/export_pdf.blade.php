<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Desempenho - {{ $booker->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            color: #4F46E5;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            color: #666;
        }

        .period-info {
            text-align: center;
            background-color: #f3f4f6;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 10px;
        }

        .summary-card {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }

        .summary-card h3 {
            font-size: 9px;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        .summary-card p {
            font-size: 14px;
            font-weight: bold;
        }

        .summary-card.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .summary-card.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .summary-card.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .summary-card.yellow {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #4F46E5;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background-color: #f3f4f6;
        }

        table thead th {
            padding: 8px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #d1d5db;
        }

        table tbody td {
            padding: 6px 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        table tbody tr:hover {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-green {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-yellow {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-orange {
            background-color: #fed7aa;
            color: #9a3412;
        }

        .badge-gray {
            background-color: #e5e7eb;
            color: #374151;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .group-header {
            background-color: #eef2ff;
            font-weight: bold;
            color: #4F46E5;
        }

        .total-row {
            background-color: #f9fafb;
            font-weight: bold;
            border-top: 2px solid #4F46E5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Relatório de Desempenho - Booker</h1>
        <p>{{ $booker->name }}</p>
    </div>

    @if(!empty($filters['start_date']) || !empty($filters['end_date']))
    <div class="period-info">
        <strong>Período:</strong>
        {{ !empty($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') : 'Início' }}
        a
        {{ !empty($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') : 'Hoje' }}
    </div>
    @else
    <div class="period-info">
        <strong>Período:</strong> Todos os registros (Lifetime)
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card blue">
            <h3>Total Vendido</h3>
            <p>R$ {{ number_format($salesKpis['total_sold_value'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="summary-card yellow">
            <h3>Gigs Vendidas</h3>
            <p>{{ $salesKpis['total_gigs_sold'] ?? 0 }}</p>
        </div>
        <div class="summary-card green">
            <h3>Comissão Recebida</h3>
            <p>R$ {{ number_format($commissionKpis['commission_received'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="summary-card orange">
            <h3>Comissão a Receber</h3>
            <p>R$ {{ number_format($commissionKpis['commission_to_receive'] ?? 0, 2, ',', '.') }}</p>
        </div>
    </div>

    <!-- Eventos Realizados -->
    @if($realizedEvents->isNotEmpty())
    <div class="section-title">Eventos Realizados ({{ $realizedEvents->count() }})</div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Artista</th>
                <th>Local</th>
                <th class="text-right">Valor</th>
                <th class="text-right">Comissão</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($realizedEvents as $event)
            <tr>
                <td>{{ $event['gig_date'] }}</td>
                <td>{{ $event['artist_name'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($event['location'], 40) }}</td>
                <td class="text-right">R$ {{ number_format($event['cache_value_brl'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($event['booker_commission_brl'], 2, ',', '.') }}</td>
                <td class="text-center">
                    <span class="badge {{ $event['booker_payment_status'] === 'pago' ? 'badge-green' : 'badge-yellow' }}">
                        {{ ucfirst($event['booker_payment_status']) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>Subtotal Eventos Realizados</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($realizedEvents->sum('cache_value_brl'), 2, ',', '.') }}</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($realizedEvents->sum('booker_commission_brl'), 2, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Eventos Futuros -->
    @if($futureEvents->isNotEmpty())
    <div class="section-title">Eventos Futuros ({{ $futureEvents->count() }})</div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Artista</th>
                <th>Local</th>
                <th class="text-right">Valor</th>
                <th class="text-right">Comissão</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($futureEvents as $event)
            <tr>
                <td>{{ $event['gig_date'] }}</td>
                <td>{{ $event['artist_name'] }}</td>
                <td>{{ \Illuminate\Support\Str::limit($event['location'], 40) }}</td>
                <td class="text-right">R$ {{ number_format($event['cache_value_brl'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($event['booker_commission_brl'], 2, ',', '.') }}</td>
                <td class="text-center">
                    <span class="badge {{ $event['is_exception'] ? 'badge-orange' : ($event['booker_payment_status'] === 'pago' ? 'badge-green' : 'badge-gray') }}">
                        {{ $event['is_exception'] ? 'Exceção' : ucfirst($event['booker_payment_status']) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>Subtotal Eventos Futuros</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($futureEvents->sum('cache_value_brl'), 2, ',', '.') }}</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($futureEvents->sum('booker_commission_brl'), 2, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Total Geral -->
    @if($realizedEvents->isNotEmpty() || $futureEvents->isNotEmpty())
    <table>
        <tfoot>
            <tr class="total-row" style="background-color: #4F46E5; color: white;">
                <td colspan="3"><strong>TOTAL GERAL</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($realizedEvents->sum('cache_value_brl') + $futureEvents->sum('cache_value_brl'), 2, ',', '.') }}</strong></td>
                <td class="text-right"><strong>R$ {{ number_format($realizedEvents->sum('booker_commission_brl') + $futureEvents->sum('booker_commission_brl'), 2, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="footer">
        Relatório gerado em {{ now()->format('d/m/Y H:i') }} | EventosPro - Sistema de Gestão de Eventos
    </div>
</body>
</html>
