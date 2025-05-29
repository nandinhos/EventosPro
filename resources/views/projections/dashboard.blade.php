<x-app-layout>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Projeções Financeiras</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Visualize as previsões de receitas e despesas para os próximos períodos</p>
        </div>
    </div>

    {{-- Seção de Filtros --}}
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-md">
        <form action="{{ route('projections.index') }}" method="GET">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                {{-- Filtro de Período --}}
                <x-form.select 
                    id="period" 
                    label="Período" 
                    :selected="$period" 
                    :options="[
                        '30_days' => 'Próximos 30 dias',
                        '60_days' => 'Próximos 60 dias',
                        '90_days' => 'Próximos 90 dias',
                        'next_quarter' => 'Próximo Trimestre',
                    ]"
                />
                {{-- Botões --}}
                <div class="flex items-end justify-end space-x-2">
                    <a href="{{ route('projections.index') }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-md text-sm">
                        Limpar
                    </a>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded-md text-sm">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Cards de Indicadores com Cores --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-100 dark:bg-green-900/20 rounded-xl shadow-md p-4">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Contas a Receber</h3>
            <p class="text-lg font-semibold text-green-800 dark:text-green-300">R$ {{ number_format($accounts_receivable, 2, ',', '.') }}</p>
        </div>
        <div class="bg-red-100 dark:bg-red-900/20 rounded-xl shadow-md p-4">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Contas a Pagar (Artistas)</h3>
            <p class="text-lg font-semibold text-red-800 dark:text-red-300">R$ {{ number_format($accounts_payable_artists, 2, ',', '.') }}</p>
        </div>
        <div class="bg-yellow-100 dark:bg-yellow-900/20 rounded-xl shadow-md p-4">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Contas a Pagar (Bookers)</h3>
            <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">R$ {{ number_format($accounts_payable_bookers, 2, ',', '.') }}</p>
        </div>
        <div class="bg-orange-100 dark:bg-orange-900/20 rounded-xl shadow-md p-4">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Despesas Previstas</h3>
            <p class="text-lg font-semibold text-orange-800 dark:text-orange-300">R$ {{ number_format($accounts_payable_expenses, 2, ',', '.') }}</p>
        </div>
        <div class="bg-blue-100 dark:bg-blue-900/20 rounded-xl shadow-md p-4 col-span-full">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Fluxo de Caixa Projetado</h3>
            <p class="text-lg font-semibold {{ $projected_cash_flow >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($projected_cash_flow, 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Tabela de Próximos Pagamentos (Clientes) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white">Próximos Pagamentos de Clientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gig</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descrição</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data de Vencimento</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor (BRL)</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($upcoming_client_payments as $payment)
                        <tr class="{{ $payment->inferred_status == 'vencido' ? 'bg-red-50 dark:bg-red-900/20' : ($payment->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '') }}">
                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $payment->gig->contract_number ?? 'N/A' }}</td>
                            <td class="px-3 py-1.5 whitespace-normal text-gray-600 dark:text-gray-400">{{ $payment->description }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $payment->due_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($payment->due_value_brl, 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center">
                                <x-status-badge :status="$payment->inferred_status" type="payment" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Nenhum pagamento de cliente previsto para o período selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabela de Próximos Pagamentos (Artistas) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white">Próximos Pagamentos a Artistas</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data do Evento</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor (BRL)</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($upcoming_artist_payments as $gig)
                        <tr class="{{ $gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $gig->contract_number }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $gig->gig_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $gig->artist->name ?? 'N/A' }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">
                                R$ {{ number_format($gig->calculated_artist_net_payout_brl, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Nenhum pagamento a artistas previsto para o período selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabela de Próximas Comissões (Bookers) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white">Próximas Comissões a Bookers</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contrato</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data do Evento</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booker</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comissão (BRL)</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($upcoming_booker_payments as $gig)
                        <tr class="{{ $gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $gig->contract_number }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-700 dark:text-gray-300">{{ $gig->gig_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $gig->booker->name ?? 'N/A' }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">
                                R$ {{ number_format($gig->calculated_booker_commission_brl, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Nenhuma comissão a bookers prevista para o período selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tabela de Despesas Previstas por Centro de Custo --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <h3 class="text-md font-semibold text-gray-800 dark:text-white">Despesas Previstas por Centro de Custo</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Centro de Custo</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total (BRL)</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Moeda Estrangeira</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($projected_expenses_by_cost_center as $expense)
                        <tr class="{{ $expense['has_foreign_currency'] ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                            <td class="px-3 py-1.5 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">{{ $expense['cost_center_name'] }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right text-gray-700 dark:text-gray-300">R$ {{ number_format($expense['total_brl'], 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-center text-gray-600 dark:text-gray-400">
                                {{ $expense['has_foreign_currency'] ? 'Sim' : 'Não' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                Nenhuma despesa prevista para o período selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-app-layout>