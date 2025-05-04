<x-app-layout>

    {{-- Cabeçalho da Página e Botões de Ação --}}
    <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">
                Detalhes da Gig #{{ $gig->id }}: {{ $gig->artist->name ?? 'N/A' }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $gig->gig_date->format('d/m/Y') }} - {{ $gig->location_event_details }}
            </p>
        </div>
        <div class="flex space-x-2 items-center">
             <a href="{{ route('gigs.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-md text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
            <a href="{{ route('gigs.edit', $gig) }}" class="bg-primary-500 hover:bg-primary-600 text-white px-3 py-1.5 rounded-md text-sm">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
            {{-- Botão Excluir --}}
             <form action="{{ route('gigs.destroy', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta Gig e todos os dados relacionados?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" title="Excluir" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm">
                    <i class="fas fa-trash-alt mr-1"></i> Excluir
                </button>
            </form>
        </div>
    </div>

    {{-- Grid Principal (Informações e Seções Relacionadas) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Coluna Esquerda: Detalhes Principais da Gig --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Card: Informações Gerais --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Informações Gerais</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div><strong class="text-gray-500 dark:text-gray-400">Artista:</strong> {{ $gig->artist->name ?? 'N/A' }}</div>
                    <div><strong class="text-gray-500 dark:text-gray-400">Booker:</strong> {{ $gig->booker->name ?? 'Agência/Sem Booker' }}</div>
                    <div><strong class="text-gray-500 dark:text-gray-400">Data Evento:</strong> {{ $gig->gig_date->format('d/m/Y') }}</div>
                    <div class="md:col-span-2"><strong class="text-gray-500 dark:text-gray-400">Local/Evento:</strong> {{ $gig->location_event_details }}</div>
                    <div><strong class="text-gray-500 dark:text-gray-400">Contrato Nº:</strong> {{ $gig->contract_number ?? 'N/A' }}</div>
                    <div><strong class="text-gray-500 dark:text-gray-400">Data Contrato:</strong> {{ $gig->contract_date?->format('d/m/Y') ?? 'N/A' }}</div>
                     <div class="md:col-span-2"><strong class="text-gray-500 dark:text-gray-400">Status Contrato:</strong> <x-status-badge :status="$gig->contract_status" type="contract" /></div>
                    @if($gig->notes)
                        <div class="md:col-span-2"><strong class="text-gray-500 dark:text-gray-400">Notas:</strong><br><span class="whitespace-pre-wrap">{{ $gig->notes }}</span></div>
                    @endif
                     {{-- Tags --}}
                    @if($gig->tags->isNotEmpty())
                        <div class="md:col-span-2">
                            <strong class="text-gray-500 dark:text-gray-400 block mb-1">Tags:</strong>
                             <div class="flex flex-wrap gap-1">
                                @foreach($gig->tags as $tag)
                                    <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium px-2 py-0.5 rounded">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

             {{-- Card: Detalhes Financeiros --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Financeiro</h3>
                </div>
                 <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div><strong class="text-gray-500 dark:text-gray-400">Cachê Bruto:</strong> {{ $gig->currency }} {{ number_format($gig->cache_value, 2, ',', '.') }}</div>
                    @if($gig->currency !== 'BRL')
                         <div><strong class="text-gray-500 dark:text-gray-400">Câmbio:</strong> {{ number_format($gig->exchange_rate ?? 0, 4, ',', '.') }}</div>
                         <div><strong class="text-gray-500 dark:text-gray-400">Cachê (BRL):</strong> R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}</div>
                    @else
                         <div class="md:col-span-2"></div> {{-- Ocupa espaço --}}
                    @endif

                    <div><strong class="text-gray-500 dark:text-gray-400">Despesas (BRL):</strong> R$ {{ number_format($gig->expenses_value_brl ?? 0, 2, ',', '.') }}</div>
                    <div><strong class="text-gray-500 dark:text-gray-400">Base Comissão (BRL):</strong> R$ {{ number_format($gig->cache_value_brl - ($gig->expenses_value_brl ?? 0), 2, ',', '.') }}</div>
                     <div class="md:col-span-2"><hr class="my-2 border-gray-200 dark:border-gray-700"></div>

                    <div><strong class="text-gray-500 dark:text-gray-400">Comissão Agência:</strong>
                        R$ {{ number_format($gig->agency_commission_value ?? 0, 2, ',', '.') }}
                        @if($gig->agency_commission_type === 'percent') ({{ number_format($gig->agency_commission_rate ?? 0, 2, ',', '.') }}%) @endif
                    </div>
                    <div><strong class="text-gray-500 dark:text-gray-400">Comissão Booker:</strong>
                        R$ {{ number_format($gig->booker_commission_value ?? 0, 2, ',', '.') }}
                         @if($gig->booker_commission_type === 'percent') ({{ number_format($gig->booker_commission_rate ?? 0, 2, ',', '.') }}%) @endif
                    </div>
                     <div><strong class="text-gray-500 dark:text-gray-400">Comissão Líquida:</strong> R$ {{ number_format($gig->liquid_commission_value ?? 0, 2, ',', '.') }}</div>

                     <div class="md:col-span-2"><hr class="my-2 border-gray-200 dark:border-gray-700"></div>

                     <div><strong class="text-gray-500 dark:text-gray-400">Status Pgto Cliente:</strong> <x-status-badge :status="$gig->payment_status" type="payment" /></div>
                     <div><strong class="text-gray-500 dark:text-gray-400">Status Pgto Artista:</strong> <x-status-badge :status="$gig->artist_payment_status" type="payment-artist" /></div>
                     <div><strong class="text-gray-500 dark:text-gray-400">Status Pgto Booker:</strong> <x-status-badge :status="$gig->booker_payment_status" type="payment-booker" /></div>

                </div>
            </div>

             {{-- Card: Pagamentos Recebidos --}}
             @include('gigs._show_payments', ['payments' => $gig->payments])

             {{-- Card: Acerto Final --}}
             @include('gigs._show_settlement', ['settlement' => $gig->settlement, 'gig' => $gig])

        </div>

        {{-- Coluna Direita: Histórico / Ações Rápidas --}}
        <div class="lg:col-span-1 space-y-6">
             {{-- Card: Histórico de Atividades --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Histórico</h3>
                </div>
                <div class="p-6 space-y-4">
                     @forelse($activityLogs as $log)
                        <div class="text-xs border-b border-gray-100 dark:border-gray-700 pb-2 mb-2">
                             <p class="text-gray-800 dark:text-gray-200">{{ $log->description }}</p>
                             <p class="text-gray-500 dark:text-gray-400">
                                 {{ $log->created_at->diffForHumans() }}
                                 @if($log->causer)
                                     por {{ $log->causer->name ?? 'Sistema' }}
                                 @endif
                             </p>
                             {{-- Detalhes das propriedades (se houver e quiser mostrar) --}}
                             {{-- @if($log->properties?->count())
                                 <pre class="mt-1 text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded overflow-x-auto"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</code></pre>
                             @endif --}}
                        </div>
                     @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma atividade registrada.</p>
                     @endforelse

                     {{-- Paginação dos Logs --}}
                     @if ($activityLogs->hasPages())
                        <div class="mt-4">
                            {{ $activityLogs->links('pagination::simple-tailwind') }} {{-- Estilo simples para logs --}}
                        </div>
                     @endif
                </div>
            </div>
            {{-- Outros cards podem ir aqui (ex: Ações rápidas, Eventos adicionais, etc.) --}}
        </div>

    </div>

</x-app-layout>