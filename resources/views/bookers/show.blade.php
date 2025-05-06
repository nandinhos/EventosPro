<x-app-layout>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ $booker->name }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Detalhes e Gigs do Booker</p>
             @if($booker->default_commission_rate)
                 <p class="text-xs text-gray-500 dark:text-gray-400">Comissão Padrão: {{ number_format($booker->default_commission_rate, 2, ',', '.') }}%</p>
             @endif
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('bookers.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150 ease-in-out"> <i class="fas fa-arrow-left mr-1"></i> Voltar</a>
            <a href="{{ route('bookers.edit', $booker) }}" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150 ease-in-out"> <i class="fas fa-edit mr-1"></i> Editar Booker</a>
        </div>
    </div>

    {{-- Cards de Métricas ATUALIZADOS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {{-- Card Total de Gigs (Mantido) --}}
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Total de Gigs</h4>
            <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['total_gigs'] }}</p>
        </div>
         {{-- NOVO Card: Comissão Recebida --}}
         <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Comissão Recebida (BRL)</h4>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                R$ {{ number_format($metrics['commission_received_brl'], 2, ',', '.') }}
            </p>
        </div>
         {{-- NOVO Card: Comissão Pendente --}}
         <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Comissão Pendente (BRL)</h4>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                 R$ {{ number_format($metrics['commission_pending_brl'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Tabela de Gigs (Ajustar cabeçalho se necessário - já removemos cachê total antes) --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
         <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Gigs Agendadas por {{ $booker->name }}</h3>
             {{-- TODO: Adicionar filtros específicos --}}
         </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                 <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Artista</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local / Evento</th>
                        {{-- <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê (BRL)</th> --}} {{-- Removido? Ou manter? --}}
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comissão (BRL)</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pgto Cliente</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pgto Comissão</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                     {{-- Loop @forelse (sem alterações na lógica interna, apenas colunas) --}}
                     @forelse ($gigs as $gig)
                         <tr class="{{ $gig->payment_status == 'vencido' ? 'bg-red-50 dark:bg-red-900/20' : ($gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '') }}">
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $gig->gig_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap font-semibold">{{ $gig->artist->name ?? 'N/A' }}</td>
                            <td class="px-3 py-1.5 whitespace-normal">{{ $gig->location_event_details }}</td>
                            {{-- <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($gig->cache_value_brl, 2, ',', '.') }}</td> --}} {{-- Cachê BRL --}}
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($gig->booker_commission_value ?? 0, 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 text-center"><x-status-badge :status="$gig->payment_status" type="payment" /></td>
                            <td class="px-3 py-1.5 text-center"><x-status-badge :status="$gig->booker_payment_status" type="payment-booker" /></td>
                            <td class="px-3 py-1.5 whitespace-nowrap">
                                <a href="{{ route('gigs.show', $gig) }}" title="Ver Gig Completa" class="text-blue-500 hover:text-blue-700"><i class="fas fa-eye fa-fw"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Nenhuma gig encontrada para este booker.</td> {{-- Ajustar colspan --}}
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
         {{-- Paginação --}}
         <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6 mt-4">
             @if ($gigs->hasPages())
                {{ $gigs->appends(request()->query())->links() }}
            @endif
        </div>
    </div>
</x-app-layout>