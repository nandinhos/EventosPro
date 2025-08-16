<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalhes da Auditoria') }} - {{ $gig->contract_number }}
        </h2>
    </x-slot>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-search-dollar mr-2 text-blue-500"></i>
                            Auditoria Detalhada
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $gig->contract_number ?? 'N/A' }} - {{ $gig->artist->name ?? 'N/A' }}</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('audit.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar
                        </a>
                        <a href="{{ route('gigs.show', $gig) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <i class="fas fa-eye mr-2"></i> Ver Gig
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                {{-- Informações Básicas --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                            Informações Básicas
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data da Gig</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $gig->gig_date ? $gig->gig_date->format('d/m/Y') : 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Data do Contrato</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $gig->contract_date ? $gig->contract_date->format('d/m/Y') : 'N/A' }}</dd>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Artista</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $gig->artist->name ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Booker</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $gig->booker->name ?? 'N/A' }}</dd>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Local</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $gig->location_event_details ?? 'N/A' }}</dd>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Moeda</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">{{ $gig->currency ?? 'N/A' }}</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $gig->contract_status == 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                            {{ ucfirst($gig->contract_status ?? 'N/A') }}
                                        </span>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Resumo da Auditoria --}}
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-calculator mr-2 text-green-500"></i>
                            Resumo da Auditoria
                        </h3>
                    </div>
                    <div class="px-6 py-4">
                        @php
                            $statusClass = match($auditData['status_divergencia'] ?? 'ok') {
                                'ok' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-200',
                                'falta' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200',
                                'excesso' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200',
                                'erro' => 'bg-gray-50 border-gray-200 text-gray-800 dark:bg-gray-900/20 dark:border-gray-800 dark:text-gray-200',
                                default => 'bg-gray-50 border-gray-200 text-gray-800 dark:bg-gray-900/20 dark:border-gray-800 dark:text-gray-200'
                            };
                        @endphp
                        
                        <div class="rounded-md border p-4 mb-4 {{ $statusClass }}">
                            <div class="flex items-center">
                                <i class="fas fa-{{ $auditData['status_divergencia'] == 'ok' ? 'check-circle' : 'exclamation-triangle' }} mr-2"></i>
                                <h4 class="font-medium">Status: {{ ucfirst($auditData['status_divergencia'] ?? 'N/A') }}</h4>
                            </div>
                            <p class="mt-2 text-sm">{{ $auditData['observacao'] ?? 'N/A' }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-1">{{ $gig->currency }} {{ number_format($auditData['valor_contrato'] ?? 0, 2, ',', '.') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Valor do Contrato</div>
                            </div>
                            <div class="{{ abs($auditData['divergencia'] ?? 0) <= 0.01 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : (($auditData['divergencia'] ?? 0) > 0 ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800') }} border rounded-lg p-4 text-center">
                                @php
                                    $divergencia = $auditData['divergencia'] ?? 0;
                                    $divergenciaClass = abs($divergencia) <= 0.01 ? 'text-green-600 dark:text-green-400' : ($divergencia > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400');
                                @endphp
                                <div class="text-2xl font-bold {{ $divergenciaClass }} mb-1">{{ $gig->currency }} {{ number_format($divergencia, 2, ',', '.') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Divergência</div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mb-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pago</dt>
                                    <dd class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">{{ $gig->currency }} {{ number_format($auditData['total_pago'] ?? 0, 2, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pendente</dt>
                                    <dd class="mt-1 text-lg font-semibold text-yellow-600 dark:text-yellow-400">{{ $gig->currency }} {{ number_format($auditData['total_pendente'] ?? 0, 2, ',', '.') }}</dd>
                                </div>
                            </div>
                        </div>

                        @if(isset($auditData['divergencia_percentual']) && abs($auditData['divergencia_percentual']) > 0.1)
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 text-center">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Divergência Percentual</dt>
                                <dd class="mt-1 text-2xl font-bold {{ $divergenciaClass }}">{{ number_format($auditData['divergencia_percentual'], 2) }}%</dd>
                            </div>
                        @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Análise Detalhada --}}
            @if(isset($auditData['analise_detalhada']) && !empty($auditData['analise_detalhada']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-chart-line mr-2 text-purple-500"></i>
                        Análise Detalhada
                    </h3>
                </div>
                <div class="px-6 py-4">
                    @php $analise = $auditData['analise_detalhada']; @endphp
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-1">{{ $analise['total_pagamentos'] ?? 0 }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total de Pagamentos</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400 mb-1">{{ $analise['pagamentos_confirmados'] ?? 0 }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Confirmados</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mb-1">{{ $analise['pagamentos_pendentes'] ?? 0 }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Pendentes</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400 mb-1">{{ $analise['pagamentos_vencidos'] ?? 0 }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Vencidos</div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="text-center">
                                @php $percentual = $analise['percentual_pago'] ?? 0; @endphp
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-1">{{ number_format($percentual, 1) }}%</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Percentual Pago</div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $percentual }}%"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-yellow-600 dark:text-yellow-400 mb-1">{{ $analise['proximo_vencimento'] ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Próximo Vencimento</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-semibold text-blue-600 dark:text-blue-400 mb-1">{{ $analise['ultimo_pagamento'] ?? 'N/A' }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Último Pagamento</div>
                            </div>
                        </div>

                        @if(isset($analise['tem_multiplas_moedas']) && $analise['tem_multiplas_moedas'])
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                <span class="font-medium text-yellow-800 dark:text-yellow-200">Atenção:</span>
                                <span class="ml-1 text-yellow-700 dark:text-yellow-300">Esta gig possui pagamentos em múltiplas moedas: {{ implode(', ', $analise['moedas_envolvidas'] ?? []) }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Detalhes dos Pagamentos --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-credit-card mr-2 text-green-500"></i>
                        Detalhes dos Pagamentos
                    </h3>
                </div>
                <div class="px-6 py-4">
                    @if($gig->payments->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Descrição</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data Vencimento</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Devido</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Recebido</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data Recebimento</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Observações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($gig->payments->sortBy('due_date') as $payment)
                                        @php
                                            $isPaid = $payment->is_paid;
                                            $isOverdue = !$isPaid && $payment->due_date < now();
                                            $rowClass = $isPaid ? 'bg-green-50 dark:bg-green-900/20' : ($isOverdue ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20');
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $payment->description ?? 'N/A' }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900 dark:text-white">{{ $payment->due_date ? $payment->due_date->format('d/m/Y') : 'N/A' }}</div>
                                                @if($isOverdue)
                                                    <div class="text-sm text-red-600 dark:text-red-400">{{ $payment->due_date->diffForHumans() }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $payment->currency }} {{ number_format($payment->due_value, 2, ',', '.') }}</div>
                                                @if($payment->exchange_rate && $payment->exchange_rate != 1)
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">Taxa: {{ number_format($payment->exchange_rate, 4) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                @if($isPaid)
                                                    <div class="font-medium text-green-600 dark:text-green-400">{{ $payment->currency }} {{ number_format($payment->received_value_actual, 2, ',', '.') }}</div>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($isPaid)
                                                    <div class="text-sm text-gray-900 dark:text-white">{{ $payment->received_date_actual ? $payment->received_date_actual->format('d/m/Y') : 'N/A' }}</div>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if($isPaid)
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Confirmado</span>
                                                @elseif($isOverdue)
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Vencido</span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Pendente</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->notes ?? '-' }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-credit-card text-4xl text-gray-400 dark:text-gray-600 mb-4"></i>
                            <h6 class="text-lg font-medium text-gray-500 dark:text-gray-400 mb-2">Nenhum pagamento registrado</h6>
                            <p class="text-gray-500 dark:text-gray-400">Esta gig não possui pagamentos cadastrados.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Dados Financeiros Complementares --}}
            @if(isset($financialData) && !empty($financialData))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-purple-500"></i>
                        Dados Financeiros Complementares (BRL)
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mb-1">R$ {{ number_format($financialData['contractValueBrl'] ?? 0, 2, ',', '.') }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Valor Contrato (BRL)</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400 mb-1">R$ {{ number_format($financialData['totalReceivedBrl'] ?? 0, 2, ',', '.') }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Recebido (BRL)</div>
                        </div>
                        <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg p-4 text-center">
                            <div class="text-xl font-bold text-cyan-600 dark:text-cyan-400 mb-1">R$ {{ number_format($financialData['grossCashBrl'] ?? 0, 2, ',', '.') }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Gross Cash (BRL)</div>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-center">
                                <div class="text-xl font-bold text-yellow-600 dark:text-yellow-400 mb-1">R$ {{ number_format($financialData['agencyCommissionBrl'] ?? 0, 2, ',', '.') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Comissão Agência (BRL)</div>
                            </div>
                            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4 text-center">
                                <div class="text-xl font-bold text-orange-600 dark:text-orange-400 mb-1">R$ {{ number_format($financialData['bookerCommissionBrl'] ?? 0, 2, ',', '.') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Comissão Booker (BRL)</div>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-center">
                                <div class="text-xl font-bold text-red-600 dark:text-red-400 mb-1">R$ {{ number_format($financialData['totalExpensesBrl'] ?? 0, 2, ',', '.') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Total Despesas (BRL)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Rodapé com informações de atualização --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-clock mr-1"></i>
                        Última atualização: {{ $auditData['ultima_atualizacao'] ?? now()->format('d/m/Y H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>