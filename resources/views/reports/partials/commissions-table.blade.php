@php
    $commissionGroups = $commissionsReport['groups'] ?? collect([]);
    $commissionsSummary = $commissionsReport['summary'] ?? [];
@endphp

<div class="space-y-6 mt-4">
    {{-- Cards de Resumo para Comissões --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-indigo-100 dark:bg-indigo-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Total de Comissões (Bookers)</h3>
            <p class="text-lg font-semibold text-indigo-800 dark:text-indigo-300">R$ {{ number_format($commissionsSummary['total_commissions'] ?? 0, 2, ',', '.') }}</p>
        </div>
        <div class="bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg">
            <h3 class="text-sm text-gray-500 dark:text-gray-400">Eventos com Comissão</h3>
            <p class="text-lg font-semibold text-blue-800 dark:text-blue-300">{{ $commissionsSummary['events_with_commissions'] ?? 0 }}</p>
        </div>
    </div>

    {{-- Tabela Agrupada por Booker --}}
    @if ($commissionGroups->isNotEmpty())
        <div class="space-y-6">
            @foreach ($commissionGroups as $group)
                <div class="bg-white dark:bg-gray-800/50 rounded-lg shadow-sm border dark:border-gray-700">
                    {{-- Cabeçalho do Grupo com Subtotal --}}
                    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-t-lg flex justify-between items-center">
                        <h4 class="text-md font-semibold text-gray-800 dark:text-white">{{ $group['booker_name'] }}</h4>
                        <span class="text-sm font-bold text-gray-600 dark:text-gray-300">
                            Subtotal: R$ {{ number_format($group['subtotal'], 2, ',', '.') }}
                        </span>
                    </div>

                    {{-- Tabela de Comissões do Grupo --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Data da Gig</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Artista</th>
                                    {{-- ***** ALTERAÇÃO NO TÍTULO DA COLUNA ***** --}}
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">Gig / Local</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Base de Cálculo (R$)</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">Comissão (R$)</th>
                                    <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400 uppercase">Status Pgto.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($group['gigs'] as $gig)
                                    <tr>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $gig->gig_date ? $gig->gig_date->format('d/m/Y') : 'N/A' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap font-semibold">{{ $gig->artist->name ?? 'N/A' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            {{-- ***** ALTERAÇÃO AQUI PARA EXIBIR LOCAL ***** --}}
                                            <a href="{{ route('gigs.show', $gig) }}" class="text-primary-600 hover:underline" title="Ver detalhes da Gig">
                                                Gig #{{ $gig->id }}
                                            </a>
                                            @if($gig->location_event_details)
                                                <span class="block text-gray-500 dark:text-gray-400 italic text-xxs truncate max-w-[150px]" title="{{ $gig->location_event_details }}">
                                                    {{ Str::limit($gig->location_event_details, 50) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right text-gray-500">{{ number_format($gig->calculated_gross_cash_brl, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right font-bold">{{ number_format($gig->calculated_booker_commission, 2, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-center">
                                            <x-status-badge :status="$gig->booker_payment_status" type="payment-internal" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500 dark:text-gray-400 mt-6">
            Nenhuma comissão de booker encontrada para os filtros selecionados.
        </p>
    @endif
</div>