<x-app-layout>
    {{-- Header --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">{{ $artist->name }}</h2>
            {{-- Colocar info de contato aqui --}}
            @if($artist->contact_info)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><i class="fas fa-phone-alt fa-fw mr-1 opacity-75"></i>{{ $artist->contact_info }}</p>
            @endif
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('artists.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 px-4 py-2 rounded-md text-sm"> <i class="fas fa-arrow-left mr-1"></i> Voltar</a>
            <a href="{{ route('artists.edit', $artist) }}" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-md text-sm"> <i class="fas fa-edit mr-1"></i> Editar Artista</a>
        </div>
    </div>

     {{-- Tags --}}
     @if($artist->tags->isNotEmpty())
        <div class="mb-6 flex flex-wrap gap-1">
             <span class="text-sm font-medium text-gray-500 dark:text-gray-400 mr-1">Tags:</span>
            @foreach($artist->tags as $tag)
                <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium px-2 py-0.5 rounded">
                    {{ $tag->name }}
                </span>
            @endforeach
        </div>
    @endif

    {{-- Cards de Métricas Focados no Artista --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Total de Gigs</h4>
            <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['total_gigs'] }}</p>
        </div>
         <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Cachê Líquido Recebido (BRL)*</h4>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                R$ {{ number_format($metrics['cache_received_brl'], 2, ',', '.') }}
            </p>
        </div>
         <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Cachê Líquido Pendente (BRL)*</h4>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                 R$ {{ number_format($metrics['cache_pending_brl'], 2, ',', '.') }}
            </p>
        </div>
        <p class="md:col-span-3 text-xs text-gray-500 dark:text-gray-400 italic">* Valores líquidos aproximados (Cachê BRL - Comissão Agência) baseados no status de pagamento do artista.</p>
    </div>

    {{-- Tabela de Gigs do Artista --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
         <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
             <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Gigs de {{ $artist->name }}</h3>
             {{-- TODO: Adicionar filtros (Período, Status Pagamento Cliente, Status Pagamento Artista) --}}
         </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Local / Evento</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Booker</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cachê (BRL)</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pgto Cliente</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pgto Artista</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($gigs as $gig)
                         <tr class="{{ $gig->payment_status == 'vencido' ? 'bg-red-50 dark:bg-red-900/20' : ($gig->currency != 'BRL' ? 'bg-blue-50 dark:bg-blue-900/10' : '') }}">
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $gig->gig_date->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 whitespace-normal">{{ $gig->location_event_details }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $gig->booker->name ?? 'Agência' }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap text-right">{{ number_format($gig->cache_value_brl, 2, ',', '.') }}</td>
                            <td class="px-3 py-1.5 text-center"><x-status-badge :status="$gig->payment_status" type="payment" /></td>
                            <td class="px-3 py-1.5 text-center"><x-status-badge :status="$gig->artist_payment_status" type="payment-artist" /></td>
                            <td class="px-3 py-1.5 whitespace-nowrap">
                                <a href="{{ route('gigs.show', $gig) }}" title="Ver Gig Completa" class="text-blue-500 hover:text-blue-700"><i class="fas fa-eye fa-fw"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Nenhuma gig encontrada para este artista.</td> {{-- Ajustar colspan --}}
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
         {{-- Paginação --}}
        {{-- ... --}}
    </div>
</x-app-layout>