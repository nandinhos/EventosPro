<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vencimentos - {{ now()->isoFormat('L') }}</title>
    <style>
        /* CONFIGURAÇÕES GERAIS */
        @page { margin: 20px; }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            color: #374151; 
            font-size: 7px; 
            line-height: 1.3;
        }

        /* CABEÇALHO PADRONIZADO */
        .header { 
            text-align: center; 
            position: relative;
            padding-bottom: 8px; 
            margin-bottom: 20px; 
            border-bottom: 2px solid #6366f1;
        }
        .header .logo { position: absolute; top: -25px; left: 0; max-height: 100px; }
        .header .title { 
            font-size: 18px; 
            margin: 0; 
            color: #1f2937; 
            font-weight: bold;
        }
        .header .subtitle { 
            font-size: 9px; 
            margin: 4px 0 0 0; 
            color: #6b7280; 
        }
        .header .generation-date { 
            font-size: 8px; 
            color: #9ca3af; 
            margin-top: 5px;
        }

        /* ESTILO DA TABELA PRINCIPAL */
        .main-table {
            width: 100%;
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        .main-table th {
            background-color: #f3f4f6;
            color: #4b5563;
            padding: 5px 3px;
            text-align: left;
            font-size: 7px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        .main-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            font-size: 8px;
        }
        .main-table .text-right { text-align: right; }
        .main-table .font-bold { font-weight: bold; }

        /* ESTILO DOS CARDS DE RESUMO */
        .summary-table { 
            width: 100%; 
            border-collapse: separate;
            border-spacing: 0 10px;
            margin: 10px 0 20px 0;
        }
        .summary-card { 
            padding: 8px 12px;
            font-size: 9px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-card.vencido { 
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        .summary-card.a_vencer { 
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .summary-card .label { 
            font-weight: bold;
            margin-right: 5px;
        }
        .summary-card .value { 
            font-weight: bold;
            font-size: 10px;
        }

        /* ESTILO DOS BADGES DE STATUS */
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 6px;
            font-weight: bold;
            border-radius: 10px;
            text-align: center;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .status-badge.status-blue { background-color: #dbeafe; color: #2563eb; }
        .status-badge.status-yellow { background-color: #fef9c3; color: #ca8a04; }
        .status-badge.status-green { background-color: #dcfce7; color: #16a34a; }
        .status-badge.status-red { background-color: #fee2e2; color: #dc2626; }
        .status-badge.status-orange { background-color: #ffedd5; color: #f97316; }
        .status-badge.status-gray { background-color: #e5e7eb; color: #4b5563; }

        /* ESTILO PARA CARDS DE GRUPOS */
        .group-card {
            margin: 15px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .group-header {
            padding: 12px 15px;
            font-weight: bold;
            font-size: 9px;
            border-left: 4px solid;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Cores específicas para cada grupo */
        .group-red {
            background-color: #fee2e2;
            border-left-color: #ef4444;
            color: #7f1d1d;
        }
        
        .group-orange {
            background-color: #ffedd5;
            border-left-color: #f97316;
            color: #9a3412;
        }
        
        .group-yellow {
            background-color: #fef3c7;
            border-left-color: #f59e0b;
            color: #92400e;
        }
        
        .group-blue {
            background-color: #dbeafe;
            border-left-color: #3b82f6;
            color: #1e3a8a;
        }
        
        /* Sub-agrupamentos por Gig */
        .subgroup-header {
            background-color: #f8fafc;
            border-left: 3px solid #6366f1;
            padding: 8px 12px;
            font-size: 8px;
            margin: 5px 0;
        }
        
        .subgroup-title {
            font-weight: bold;
            font-size: 8px;
            color: #374151;
        }
        
        .subgroup-details {
            font-size: 7px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .subgroup-stats {
            font-size: 7px;
            color: #4338ca;
            margin-top: 2px;
        }
        
        /* Tabelas dentro dos cards */
        .group-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        
        .group-table th {
            background-color: #f9fafb;
            padding: 6px 8px;
            font-size: 7px;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .group-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 7px;
        }
        
        .subtotal-row td {
            background-color: #f9fafb;
            font-weight: bold;
            padding-top: 6px;
            padding-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/coral_360_logo.png') }}" alt="Logo" class="logo">
        <div class="title">Relatório de Vencimentos</div>
        <p class="generation-date" style="font-size: 8px; color: #9ca3af;">
            Gerado em: {{ now()->isoFormat('L LT') }}
            @if(auth()->check())
                por: {{ auth()->user()->name }}
            @endif
        </p>

    @if (!empty($filters))
        @if(isset($filters['start_date']) || isset($filters['end_date']))
            <p><strong>Período de Vencimento:</strong> {{ isset($filters['start_date']) ? \Carbon\Carbon::parse($filters['start_date'])->isoFormat('L') : 'Início' }} a {{ isset($filters['end_date']) ? \Carbon\Carbon::parse($filters['end_date'])->isoFormat('L') : 'Fim' }}</p>
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
                <th style="width: 5%;">Status Contrato</th>
                <th style="width: 10%;">Booker</th>
                <th style="width: 15%;">Artista</th>
                <th style="width: 28%;">Local</th>
                <th style="width: 10%;">Data do Evento</th>
                <th style="width: 10%;">Vencimento</th>
                <th style="width: 10%;">Parcela</th>
                <th style="width: 20%;">Valor (R$)</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($customGroupedPayments) && !empty($customGroupedPayments))
                @php
                    $groupTitles = [
                        'evento_realizado_vencimento_pendente' => [
                            'title' => 'Eventos Realizados com Vencimento Pendente',
                            'description' => 'Prioridade máxima - Eventos já aconteceram mas ainda têm parcelas em aberto',
                            'priority' => 1
                        ],
                        'evento_futuro_multiplas_vencidas' => [
                            'title' => 'Eventos Futuros com Múltiplas Parcelas Vencidas',
                            'description' => 'Alta prioridade - Eventos futuros com mais de uma parcela vencida',
                            'priority' => 2
                        ],
                        'evento_futuro_parcela_vencida' => [
                            'title' => 'Eventos Futuros com Parcela Vencida',
                            'description' => 'Média prioridade - Eventos futuros com parcela vencida',
                            'priority' => 3
                        ],
                        'evento_futuro_parcela_a_vencer' => [
                            'title' => 'Eventos Futuros com Parcela a Vencer',
                            'description' => 'Baixa prioridade - Eventos futuros com parcelas ainda não vencidas',
                            'priority' => 4
                        ]
                    ];
                @endphp
                
                @foreach($customGroupedPayments as $groupKey => $groupPayments)
                    @if($groupKey === 'evento_futuro_multiplas_vencidas' && is_array($groupPayments))
                        {{-- Grupo especial com sub-agrupamentos por Gig --}}
                        @php
                            $groupInfo = $groupTitles[$groupKey] ?? ['title' => 'Grupo Desconhecido', 'description' => '', 'priority' => 5];
                            $totalItems = collect($groupPayments)->sum(function($gig) { return count($gig['payments']); });
                            $groupColorClass = [
                                'evento_realizado_vencimento_pendente' => 'group-red',
                                'evento_futuro_multiplas_vencidas' => 'group-orange',
                                'evento_futuro_parcela_vencida' => 'group-yellow',
                                'evento_futuro_parcela_a_vencer' => 'group-blue'
                            ][$groupKey] ?? 'group-blue';
                        @endphp
                        <tr>
                            <td colspan="8" style="padding: 0; border: none;">
                                <div class="group-card">
                                    <div class="group-header {{ $groupColorClass }}">
                                        <div>
                                            <div style="font-size: 9px; font-weight: bold;">{{ $groupInfo['title'] }}</div>
                                            <div style="font-size: 6px; margin-top: 2px; opacity: 0.8;">{{ $groupInfo['description'] }}</div>
                                        </div>
                                        <div style="text-align: right; font-size: 7px;">
                                            @php
                                                $totalOverdue = collect($groupPayments)->sum('overdue_count');
                                                $totalUpcoming = collect($groupPayments)->sum('upcoming_count');
                                            @endphp
                                            <div style="color: #b91c1c; font-weight: bold;">{{ $totalOverdue }} vencidas</div>
                                            <div style="color: #1e40af; font-weight: bold; margin-top: 2px;">{{ $totalUpcoming }} à vencer</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        @foreach($groupPayments as $gigData)
                            {{-- Cabeçalho do sub-agrupamento por Gig --}}
                            <tr>
                                <td colspan="8" style="padding: 0; border: none;">
                                    <div class="subgroup-header" style="position: relative;">
                                        <div style="width: 70%; float: left;">
                                            <div class="subgroup-title">
                                                Gig #{{ $gigData['gig']->id }} - {{ $gigData['gig']->artist->name ?? 'N/A' }}
                                            </div>
                                            <div class="subgroup-details">
                                                {{ $gigData['gig']->location_event_details ?? 'Local não informado' }} •
                                                {{ $gigData['gig']->gig_date?->isoFormat('L') ?? 'Data não informada' }}
                                            </div>
                                        </div>
                                        <div style="width: 30%; float: right; text-align: right; font-size: 8px;">
                                            <div style="color: #b91c1c; font-weight: bold; margin-bottom: 2px;">
                                                Vencidas: R$ {{ number_format($gigData['overdue_total'], 2, ',', '.') }} ({{ $gigData['overdue_count'] }})
                                            </div>
                                            <div style="color: #1e40af; font-weight: bold; margin-bottom: 2px;">
                                                À Vencer: R$ {{ number_format($gigData['upcoming_total'], 2, ',', '.') }} ({{ $gigData['upcoming_count'] }})
                                            </div>
                                            <div style="color: #c2410c; font-weight: bold; border-top: 1px solid #c2410c; padding-top: 2px; margin-top: 2px;">
                                                Total: R$ {{ number_format($gigData['grand_total'], 2, ',', '.') }}
                                            </div>
                                        </div>
                                        <div style="clear: both;"></div>
                                    </div>
                                </td>
                            </tr>


                            {{-- PARCELAS VENCIDAS --}}
                            @if($gigData['overdue_payments']->count() > 0)
                                <tr>
                                    <td colspan="8" style="background-color: #fee2e2; padding: 6px 8px; font-weight: bold; font-size: 9px; color: #991b1b; border: none;">
                                        ⚠ PARCELAS VENCIDAS
                                    </td>
                                </tr>
                                @foreach($gigData['overdue_payments'] as $payment)
                                    @php
                                        $gig = $payment->gig;
                                        $contractStatus = $gig?->contract_status ?? 'rascunho';

                                        $statusMap = [
                                            'assinado' => ['title' => 'Assinado', 'color' => 'green'],
                                            'cancelado' => ['title' => 'Cancelado', 'color' => 'red'],
                                            'concluido' => ['title' => 'Concluído', 'color' => 'green'],
                                            'expirado' => ['title' => 'Expirado', 'color' => 'orange'],
                                            'n/a' => ['title' => 'N/A', 'color' => 'gray'],
                                            'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow'],
                                            'rascunho' => ['title' => 'Rascunho', 'color' => 'gray'],
                                        ];

                                        $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray'];
                                    @endphp
                                    <tr style="background-color: #fef2f2;">
                                        <td>
                                            <span class="status-badge status-{{ $statusInfo['color'] }}">
                                                {{ $statusInfo['title'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-bold">{{ $gig?->booker?->name ?? 'Agência' }}</div>
                                            <div class="text-xs">{{ $gig?->booker?->email ?? '' }}</div>
                                        </td>
                                        <td>{{ $gig?->artist?->name ?? 'N/A' }}</td>
                                        <td>
                                            <div class="text-xs">{{ $gig?->location_event_details ?? '' }}</div>
                                        </td>
                                        <td>{{ $gig?->gig_date?->isoFormat('L') ?? 'N/A' }}</td>
                                        <td class="text-red">
                                            <div>{{ $payment->due_date?->isoFormat('L') }}</div>
                                            <div class="text-xs">{{ $payment->due_date?->diffForHumans() }}</div>
                                        </td>
                                        <td style="word-wrap: break-word;">
                                            <div style="max-width: 120px; font-size: 7px; line-height: 1.2;">
                                                {{ $payment->description ?: 'Parcela' }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="font-bold">
                                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                            </div>
                                            @if($payment->currency !== 'BRL')
                                                <div class="text-xs">
                                                    ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- Subtotal Vencidas --}}
                                <tr style="background-color: #fecaca; border-top: 2px solid #b91c1c;">
                                    <td colspan="7" style="text-align: right; font-weight: bold; color: #991b1b; padding: 6px 8px;">
                                        Subtotal Vencidas ({{ $gigData['overdue_count'] }} parcelas)
                                    </td>
                                    <td class="text-center" style="font-weight: bold; color: #991b1b;">
                                        R$ {{ number_format($gigData['overdue_total'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endif

                            {{-- PARCELAS À VENCER --}}
                            @if($gigData['upcoming_payments']->count() > 0)
                                <tr>
                                    <td colspan="8" style="background-color: #dbeafe; padding: 6px 8px; font-weight: bold; font-size: 9px; color: #1e40af; border: none;">
                                        🕐 PARCELAS À VENCER
                                    </td>
                                </tr>
                                @foreach($gigData['upcoming_payments'] as $payment)
                                    @php
                                        $gig = $payment->gig;
                                        $contractStatus = $gig?->contract_status ?? 'rascunho';

                                        $statusMap = [
                                            'assinado' => ['title' => 'Assinado', 'color' => 'green'],
                                            'cancelado' => ['title' => 'Cancelado', 'color' => 'red'],
                                            'concluido' => ['title' => 'Concluído', 'color' => 'green'],
                                            'expirado' => ['title' => 'Expirado', 'color' => 'orange'],
                                            'n/a' => ['title' => 'N/A', 'color' => 'gray'],
                                            'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow'],
                                            'rascunho' => ['title' => 'Rascunho', 'color' => 'gray'],
                                        ];

                                        $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray'];
                                    @endphp
                                    <tr style="background-color: #fef9e7;">
                                        <td>
                                            <span class="status-badge status-{{ $statusInfo['color'] }}">
                                                {{ $statusInfo['title'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="font-bold">{{ $gig?->booker?->name ?? 'Agência' }}</div>
                                            <div class="text-xs">{{ $gig?->booker?->email ?? '' }}</div>
                                        </td>
                                        <td>{{ $gig?->artist?->name ?? 'N/A' }}</td>
                                        <td>
                                            <div class="text-xs">{{ $gig?->location_event_details ?? '' }}</div>
                                        </td>
                                        <td>{{ $gig?->gig_date?->isoFormat('L') ?? 'N/A' }}</td>
                                        <td class="text-yellow">
                                            <div>{{ $payment->due_date?->isoFormat('L') }}</div>
                                            <div class="text-xs">{{ $payment->due_date?->diffForHumans() }}</div>
                                        </td>
                                        <td style="word-wrap: break-word;">
                                            <div style="max-width: 120px; font-size: 7px; line-height: 1.2;">
                                                {{ $payment->description ?: 'Parcela' }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="font-bold">
                                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}
                                            </div>
                                            @if($payment->currency !== 'BRL')
                                                <div class="text-xs">
                                                    ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- Subtotal À Vencer --}}
                                <tr style="background-color: #bfdbfe; border-top: 2px solid #1e40af;">
                                    <td colspan="7" style="text-align: right; font-weight: bold; color: #1e40af; padding: 6px 8px;">
                                        Subtotal À Vencer ({{ $gigData['upcoming_count'] }} parcelas)
                                    </td>
                                    <td class="text-center" style="font-weight: bold; color: #1e40af;">
                                        R$ {{ number_format($gigData['upcoming_total'], 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @elseif(is_object($groupPayments) && $groupPayments->count() > 0)
                        {{-- Grupos normais --}}
                        @php
                            $groupInfo = $groupTitles[$groupKey] ?? ['title' => 'Grupo Desconhecido', 'description' => '', 'priority' => 5];
                            $groupColorClass = [
                                'evento_realizado_vencimento_pendente' => 'group-red',
                                'evento_futuro_multiplas_vencidas' => 'group-orange',
                                'evento_futuro_parcela_vencida' => 'group-yellow',
                                'evento_futuro_parcela_a_vencer' => 'group-blue'
                            ][$groupKey] ?? 'group-blue';
                        @endphp
                        <tr>
                            <td colspan="8" style="padding: 0; border: none;">
                                <div class="group-card">
                                    <div class="group-header {{ $groupColorClass }}">
                                        <div>
                                            <div style="font-size: 9px; font-weight: bold;">{{ $groupInfo['title'] }}</div>
                                            <div style="font-size: 6px; margin-top: 2px; opacity: 0.8;">{{ $groupInfo['description'] }}</div>
                                        </div>
                                        <div style="text-align: right; font-size: 7px;">
                                            {{ $groupPayments->count() }} {{ Str::plural('item', $groupPayments->count()) }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @foreach($groupPayments as $payment)
                            @php
                                $gig = $payment->gig;
                                $contractStatus = $gig?->contract_status ?? 'rascunho';
                                
                                // Mapeamento de status do contrato com base nos valores reais do banco
                                $statusMap = [
                                    'assinado' => ['title' => 'Assinado', 'color' => 'green'],
                                    'cancelado' => ['title' => 'Cancelado', 'color' => 'red'],
                                    'concluido' => ['title' => 'Concluído', 'color' => 'green'],
                                    'expirado' => ['title' => 'Expirado', 'color' => 'orange'],
                                    'n/a' => ['title' => 'N/A', 'color' => 'gray'],
                                    'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow'],
                                    'rascunho' => ['title' => 'Rascunho', 'color' => 'gray'], // Mantido para compatibilidade
                                ];
                                
                                $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray'];
                                $dueStatus = $payment->inferred_status;
                            @endphp
                            <tr>
                                <td>
                                    <span class="status-badge status-{{ $statusInfo['color'] }}">
                                        {{ $statusInfo['title'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-bold">{{ $gig?->booker?->name ?? 'Agência' }}</div>
                                    <div class="text-xs">{{ $gig?->booker?->email ?? '' }}</div>
                                </td>
                                <td>{{ $gig?->artist?->name ?? 'N/A' }}</td>
                                <td>
                                    <div class="text-xs">{{ $gig?->location_event_details ?? '' }}</div>
                                </td>
                                <td>{{ $gig?->gig_date?->isoFormat('L') ?? 'N/A' }}</td>
                                <td class="{{ $dueStatus === 'vencido' ? 'text-red' : 'text-yellow' }}">
                                    <div>{{ $payment->due_date?->isoFormat('L') }}</div>
                                    <div class="text-xs">{{ $payment->due_date?->diffForHumans() }}</div>
                                </td>
                                <td style="word-wrap: break-word;">
                                    <div style="max-width: 120px; font-size: 7px; line-height: 1.2;">
                                        {{ $payment->description ?: 'Parcela' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="font-bold">
                                        {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }} 
                                    </div>
                                    @if($payment->currency !== 'BRL')
                                        <div class="text-xs">
                                            ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            @else
                @forelse($payments as $payment)
                    @php
                        $gig = $payment->gig;
                        $contractStatus = $gig?->contract_status ?? 'rascunho';
                        
                        // Mapeamento de status do contrato com base nos valores reais do banco
                        $statusMap = [
                            'assinado' => ['title' => 'Assinado', 'color' => 'green'],
                            'cancelado' => ['title' => 'Cancelado', 'color' => 'red'],
                            'concluido' => ['title' => 'Concluído', 'color' => 'green'],
                            'expirado' => ['title' => 'Expirado', 'color' => 'orange'],
                            'n/a' => ['title' => 'N/A', 'color' => 'gray'],
                            'para_assinatura' => ['title' => 'Para Assinatura', 'color' => 'yellow'],
                            'rascunho' => ['title' => 'Rascunho', 'color' => 'gray'],
                        ];
                        
                        $statusInfo = $statusMap[strtolower($contractStatus)] ?? ['title' => 'Desconhecido', 'color' => 'gray'];
                        $dueStatus = $payment->inferred_status;
                    @endphp
                    <tr>
                        <td>
                            <span class="status-badge status-{{ $statusInfo['color'] }}">
                                {{ $statusInfo['title'] }}
                            </span>
                        </td>
                        <td>
                            <div class="font-bold">{{ $gig?->booker?->name ?? 'Agência' }}</div>
                            <div class="text-xs">{{ $gig?->booker?->email ?? '' }}</div>
                        </td>
                        <td>{{ $gig?->artist?->name ?? 'N/A' }}</td>
                        <td>
                            <div class="text-xs">{{ $gig?->location_event_details ?? '' }}</div>
                        </td>
                        <td>{{ $gig?->gig_date?->isoFormat('L') ?? 'N/A' }}</td>
                        <td class="{{ $dueStatus === 'vencido' ? 'text-red' : 'text-yellow' }}">
                            <div>{{ $payment->due_date?->isoFormat('L') }}</div>
                            <div class="text-xs">{{ $payment->due_date?->diffForHumans() }}</div>
                        </td>
                        <td style="word-wrap: break-word;">
                            <div style="max-width: 120px; font-size: 7px; line-height: 1.2;">
                                {{ $payment->description ?: 'Parcela' }}
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="font-bold">
                                {{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }} 
                            </div>
                            @if($payment->currency !== 'BRL')
                                <div class="text-xs">
                                    ~R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">Nenhum vencimento encontrado para os filtros selecionados.</td>
                    </tr>
                @endforelse
            @endif
        </tbody>
    </table>
</body>
</html>