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
        <a href="{{ route('gigs.index', $backUrlParams) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-3 py-1.5 rounded-md text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Voltar para Lista
            </a>
            {{-- Botão Editar - Adiciona backUrlParams também --}}
            <a href="{{ route('gigs.edit', ['gig' => $gig] + $backUrlParams) }}" class="bg-primary-500 hover:bg-primary-600 text-white px-3 py-1.5 rounded-md text-sm">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
            {{-- Botão Excluir - Adiciona backUrlParams nos hiddens --}}
             <form action="{{ route('gigs.destroy', $gig) }}" method="POST" onsubmit="return confirm('Tem certeza?');" class="inline">
                @csrf
                @method('DELETE')
                {{-- Adiciona campos hidden para cada parâmetro de volta --}}
                @foreach($backUrlParams as $key => $value)
                     @if(!is_array($value))
                         <input type="hidden" name="backParams[{{ $key }}]" value="{{ $value }}">
                     @endif
                @endforeach
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

            {{-- ============================================= --}}
             {{-- Card: Resumo Financeiro (SIMPLIFICADO FINAL) --}}
             {{-- ============================================= --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Resumo Financeiro</h3>
                </div>
                 <div class="p-6 space-y-3 text-sm">

                     {{-- Valor Total do Contrato (na Moeda Original) --}}
                     <div>
                        <span class="text-gray-500 dark:text-gray-400">Valor Contrato:</span>
                        <span class="font-semibold text-gray-900 dark:text-white ml-2">
                            {{ $gig->currency }} {{ number_format($gig->cache_value ?? 0, 2, ',', '.') }}
                        </span>
                     </div>

                     {{-- Valor Total Recebido (na Moeda Original) --}}
                      <div>
                        <span class="text-gray-500 dark:text-gray-400">Total Recebido:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400 ml-2">
                            {{-- Usa a variável calculada no controller --}}
                           {{ $gig->currency }} {{ number_format($totalReceivedOriginalCurrency, 2, ',', '.') }}
                        </span>
                         {{-- Nota: Pode haver pagamentos em outras moedas não somados aqui --}}
                         @if($gig->payments->where('currency', '!=', $gig->currency)->count() > 0)
                            <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">(+ valores em outras moedas)</span>
                         @endif
                     </div>

                    {{-- Valor Pendente (na Moeda Original) --}}
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Saldo Pendente:</span>
                        <span class="font-semibold {{ $balanceOriginalCurrency <= 0.01 ? 'text-gray-500 dark:text-gray-400' : 'text-red-600 dark:text-red-400' }} ml-2">
                            {{-- Usa a variável calculada no controller --}}
                           {{ $gig->currency }} {{ number_format($balanceOriginalCurrency, 2, ',', '.') }}
                        </span>
                    </div>

                    <hr class="my-3 border-gray-200 dark:border-gray-700">

                     {{-- Status Gerais --}}
                     <div class="flex flex-wrap gap-x-6 gap-y-2">
                         <div><strong class="text-gray-500 dark:text-gray-400">Pgto Cliente:</strong> <x-status-badge :status="$gig->payment_status" type="payment" class="ml-1"/></div>
                         <div><strong class="text-gray-500 dark:text-gray-400">Pgto Artista:</strong> <x-status-badge :status="$gig->artist_payment_status" type="payment-artist" class="ml-1"/></div>
                         <div><strong class="text-gray-500 dark:text-gray-400">Pgto Booker:</strong> <x-status-badge :status="$gig->booker_payment_status" type="payment-booker" class="ml-1"/></div>
                     </div>
                </div>
            </div>
            {{-- ============================================= --}}
            {{-- FIM Card Financeiro (SIMPLIFICADO FINAL)     --}}
            {{-- ============================================= --}}

            {{-- Card: Pagamentos Recebidos --}}
             @include('gigs._show_payments', ['payments' => $gig->payments]) 
            
            {{-- Card: Despesas --}}
             @include('gigs._show_costs', ['costs' => $gig->costs])

             {{-- Card: Acerto Final --}}
             @include('gigs._show_final_settlements', ['gig' => $gig, 'settlement' => $gig->settlement])
             @include('gigs._show_settlement', ['settlement' => $gig->settlement, 'gig' => $gig])
             @include('settlements._settle_artist_modal', ['gig' => $gig])
             @include('settlements._settle_booker_modal', ['gig' => $gig])


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