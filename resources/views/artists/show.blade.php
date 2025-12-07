<x-app-layout>
    @push('styles')
    <style>
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
    </style>
    @endpush

    <div class="max-w-9xl mx-auto py-6 sm:px-6 lg:px-8" x-data="artistDashboard()">
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg">
            {{-- Cabeçalho Aprimorado --}}
            <div class="p-4 sm:px-6 bg-gradient-to-r from-gray-800 to-gray-900 dark:from-gray-900 dark:to-black rounded-t-lg">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <div class="flex items-center gap-4">
                        {{-- Avatar --}}
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                            {{ strtoupper(substr($artist->name, 0, 2)) }}
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">{{ $artist->name }}</h1>
                            <p class="text-sm text-gray-300">Painel administrativo do artista</p>
                            {{-- Tags --}}
                            @if($artist->tags->count() > 0)
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach($artist->tags as $tag)
                                        <span class="px-2 py-0.5 text-xs rounded-full" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($artist->phone)
                            <a href="https://wa.me/55{{ preg_replace('/\D/', '', $artist->phone) }}" 
                               target="_blank"
                               class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 rounded-md text-xs font-medium text-white">
                                <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                            </a>
                        @endif
                        <x-back-button :fallback="route('artists.index')" class="inline-flex items-center px-3 py-1.5 bg-white/10 hover:bg-white/20 border border-white/20 rounded-md text-xs font-medium text-white" />
                        <a href="{{ route('artists.edit', $artist) }}" class="inline-flex items-center px-3 py-1.5 bg-primary-600 hover:bg-primary-700 rounded-md text-xs font-medium text-white">
                            <i class="fas fa-pen mr-2"></i>Editar
                        </a>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="p-4 sm:px-6 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <form method="GET" action="{{ route('artists.show', $artist) }}" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-grow">
                        <label for="search" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Busca Livre</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                               placeholder="Booker, local, contrato..."
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label for="start_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">De</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" 
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label for="end_date" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Até</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" 
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-xs font-medium">
                            <i class="fas fa-search mr-2"></i>Filtrar
                        </button>
                        <a href="{{ route('artists.show', $artist) }}" class="inline-flex items-center px-3 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md text-xs font-medium">
                            <i class="fas fa-times mr-1"></i>Limpar
                        </a>
                    </div>
                </form>
            </div>

            {{-- Cards de Métricas (6 cards) --}}
            <div class="p-4 sm:px-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    {{-- Total de Gigs --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border-l-4 border-blue-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total de Gigs</p>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $metrics['total_gigs'] }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">
                            <span class="text-green-600">{{ $metrics['realized_gigs'] ?? $realizedGigs->count() }}</span> realizadas · 
                            <span class="text-blue-600">{{ $metrics['future_gigs'] ?? $futureGigs->count() }}</span> futuras
                        </p>
                    </div>
                    
                    {{-- Cachê Bruto --}}
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3 border-l-4 border-purple-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Cachê Bruto</p>
                        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">R$ {{ number_format($metrics['totalGrossFee'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    
                    {{-- Líquido Recebido --}}
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border-l-4 border-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Líquido Recebido</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">R$ {{ number_format($metrics['cache_received_brl'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    
                    {{-- A Receber --}}
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 border-l-4 border-yellow-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">A Receber</p>
                        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">R$ {{ number_format($metrics['cache_pending_brl'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                    
                    {{-- Taxa Média --}}
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-3 border-l-4 border-indigo-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Média/Show</p>
                        <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">R$ {{ number_format($metrics['average_fee'] ?? 0, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Gráfico de Performance Mensal --}}
            <div class="p-4 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white mb-4">
                        <i class="fas fa-chart-bar mr-2 text-primary-500"></i>Performance Mensal
                    </h3>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Tabela de Eventos --}}
            <div class="p-4 sm:px-6">
                @php
                    // Combina gigs realizados + futuros e aplica busca
                    $allGigs = $realizedGigs->concat($futureGigs)->sortByDesc('gig_date');
                    
                    // Aplica filtro de busca se houver
                    if (request('search')) {
                        $search = strtolower(request('search'));
                        $allGigs = $allGigs->filter(function ($gig) use ($search) {
                            return str_contains(strtolower($gig->booker->name ?? ''), $search) ||
                                   str_contains(strtolower($gig->location_event_details ?? ''), $search) ||
                                   str_contains(strtolower($gig->contract_number ?? ''), $search) ||
                                   str_contains((string) $gig->id, $search);
                        });
                    }
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Cabeçalho da Tabela --}}
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                                <i class="fas fa-calendar-alt mr-2 text-primary-500"></i>Eventos do Período
                                <span class="ml-2 text-xs font-normal text-gray-500">({{ $allGigs->count() }} registros)</span>
                            </h3>
                        </div>
                    </div>

                    {{-- Cabeçalho das Colunas --}}
                    <div class="hidden sm:block px-4 py-2 bg-gray-100 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            <div class="col-span-1">Data</div>
                            <div class="col-span-2">Booker</div>
                            <div class="col-span-3">Gig / Local</div>
                            <div class="col-span-2 text-right">Cachê</div>
                            <div class="col-span-2 text-center">Status</div>
                            <div class="col-span-2 text-right">Ações</div>
                        </div>
                    </div>

                    {{-- Lista de Eventos --}}
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($allGigs as $gig)
                            @php
                                $gigCosts = $gig->gigCosts;
                                $confirmedCosts = $gigCosts->where('is_confirmed', true);
                                $totalConfirmed = $confirmedCosts->sum('value_brl');
                                $hasExpenses = $gigCosts->count() > 0;
                                $isFuture = $gig->gig_date->isFuture();
                                $settlementStage = $gig->settlement?->settlement_stage ?? 'aguardando_conferencia';
                                
                                $stageConfig = [
                                    'aguardando_conferencia' => ['label' => 'Conferir', 'color' => 'gray', 'icon' => 'clipboard-check'],
                                    'fechamento_enviado' => ['label' => 'Ag. NF', 'color' => 'blue', 'icon' => 'paper-plane'],
                                    'documentacao_recebida' => ['label' => 'Pronto', 'color' => 'yellow', 'icon' => 'file-invoice'],
                                    'pago' => ['label' => 'Pago', 'color' => 'green', 'icon' => 'check-circle'],
                                ];
                                $stage = $stageConfig[$settlementStage] ?? $stageConfig['aguardando_conferencia'];
                            @endphp

                            <div x-data="{ open: false }" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                {{-- Linha Principal --}}
                                <div @click="open = !open" class="px-4 py-3 cursor-pointer">
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        {{-- Data --}}
                                        <div class="col-span-3 sm:col-span-1">
                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $gig->gig_date->isoFormat('DD/MM') }}
                                            </p>
                                            <p class="text-[10px] text-gray-400">{{ $gig->gig_date->isoFormat('ddd') }}</p>
                                            @if($isFuture)
                                                <span class="inline-block px-1.5 py-0.5 text-[9px] bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded mt-0.5">Futuro</span>
                                            @endif
                                        </div>
                                        
                                        {{-- Booker --}}
                                        <div class="col-span-3 sm:col-span-2">
                                            <p class="text-xs font-semibold text-gray-900 dark:text-white uppercase truncate">{{ $gig->booker->name ?? 'N/A' }}</p>
                                        </div>
                                        
                                        {{-- Gig / Local --}}
                                        <div class="col-span-6 sm:col-span-3">
                                            <a href="{{ route('gigs.show', $gig->id) }}" @click.stop class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                #{{ $gig->contract_number ?: $gig->id }}
                                            </a>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($gig->location_event_details, 30) ?: '-' }}</p>
                                        </div>
                                        
                                        {{-- Cachê --}}
                                        <div class="hidden sm:block col-span-2 text-right">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">
                                                R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 0, ',', '.') }}
                                            </p>
                                            @if($hasExpenses)
                                                <p class="text-[10px] text-gray-400">
                                                    <i class="fas fa-receipt"></i> R$ {{ number_format($totalConfirmed, 0, ',', '.') }}
                                                </p>
                                            @endif
                                        </div>
                                        
                                        {{-- Status do Workflow - Interativo --}}
                                        <div class="hidden sm:flex col-span-2 justify-center" x-data="{ showActions: false }">
                                            @if(!$isFuture)
                                                <div class="relative">
                                                    {{-- Badge clicável --}}
                                                    <button @click.stop="showActions = !showActions" @click.away="showActions = false"
                                                            class="inline-flex items-center px-2 py-1 text-[10px] font-medium rounded-full cursor-pointer transition-all hover:ring-2 hover:ring-offset-1 hover:ring-{{ $stage['color'] }}-400 bg-{{ $stage['color'] }}-100 text-{{ $stage['color'] }}-700 dark:bg-{{ $stage['color'] }}-900/50 dark:text-{{ $stage['color'] }}-300">
                                                        <i class="fas fa-{{ $stage['icon'] }} mr-1"></i>{{ $stage['label'] }}
                                                        <i class="fas fa-chevron-down ml-1 text-[8px]" :class="showActions ? 'rotate-180' : ''"></i>
                                                    </button>

                                                    {{-- Dropdown de ações --}}
                                                    <div x-show="showActions" x-transition:enter="transition ease-out duration-200"
                                                         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                         x-transition:leave="transition ease-in duration-150"
                                                         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                                         class="absolute z-50 mt-1 right-0 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1"
                                                         style="display: none;">
                                                        
                                                        @if($settlementStage === 'aguardando_conferencia')
                                                            {{-- Enviar Fechamento --}}
                                                            <form action="{{ route('artists.settlements.send', $gig) }}" method="POST" class="block" @click.stop>
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="redirect_to" value="artist">
                                                                <input type="hidden" name="artist_id" value="{{ $artist->id }}">
                                                                <button type="submit" class="w-full text-left px-3 py-2 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 flex items-center">
                                                                    <i class="fas fa-paper-plane mr-2 w-4"></i>Enviar Fechamento
                                                                </button>
                                                            </form>
                                                        @elseif($settlementStage === 'fechamento_enviado')
                                                            {{-- Registrar NF/Recibo - Modal Inline --}}
                                                            <button type="button" @click.stop="$dispatch('open-nf-modal-{{ $gig->id }}')"
                                                               class="w-full text-left px-3 py-2 text-xs text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 flex items-center">
                                                                <i class="fas fa-file-invoice mr-2 w-4"></i>Registrar NF/Recibo
                                                            </button>
                                                            {{-- Reverter para Conferir --}}
                                                            <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST" class="block" @click.stop>
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="redirect_to" value="artist">
                                                                <input type="hidden" name="artist_id" value="{{ $artist->id }}">
                                                                <button type="submit" onclick="return confirm('Reverter para Aguardando Conferência?')"
                                                                        class="w-full text-left px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center">
                                                                    <i class="fas fa-undo mr-2 w-4"></i>Reverter Envio
                                                                </button>
                                                            </form>
                                                        @elseif($settlementStage === 'documentacao_recebida')
                                                            {{-- Registrar Pagamento - Modal Inline --}}
                                                            <button type="button" @click.stop="$dispatch('open-payment-modal-{{ $gig->id }}')"
                                                               class="w-full text-left px-3 py-2 text-xs text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 flex items-center">
                                                                <i class="fas fa-check-circle mr-2 w-4"></i>Registrar Pagamento
                                                            </button>
                                                            {{-- Reverter NF --}}
                                                            <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST" class="block" @click.stop>
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="redirect_to" value="artist">
                                                                <input type="hidden" name="artist_id" value="{{ $artist->id }}">
                                                                <button type="submit" onclick="return confirm('Reverter para Ag. NF/Recibo?')"
                                                                        class="w-full text-left px-3 py-2 text-xs text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center">
                                                                    <i class="fas fa-undo mr-2 w-4"></i>Reverter NF
                                                                </button>
                                                            </form>
                                                        @elseif($settlementStage === 'pago')
                                                            {{-- Ver Comprovante --}}
                                                            <a href="{{ route('gigs.request-nf', $gig) }}" @click.stop 
                                                               class="block px-3 py-2 text-xs text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 flex items-center">
                                                                <i class="fas fa-receipt mr-2 w-4"></i>Ver Comprovante
                                                            </a>
                                                            {{-- Reverter Pagamento --}}
                                                            <form action="{{ route('artists.settlements.revert', $gig) }}" method="POST" class="block" @click.stop>
                                                                @csrf @method('PATCH')
                                                                <input type="hidden" name="redirect_to" value="artist">
                                                                <input type="hidden" name="artist_id" value="{{ $artist->id }}">
                                                                <button type="submit" onclick="return confirm('Reverter pagamento? Status voltará para Pronto p/ Pagar.')"
                                                                        class="w-full text-left px-3 py-2 text-xs text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center">
                                                                    <i class="fas fa-undo mr-2 w-4"></i>Reverter Pagamento
                                                                </button>
                                                            </form>
                                                        @endif
                                                        
                                                        <hr class="my-1 border-gray-200 dark:border-gray-700">
                                                        <a href="{{ route('gigs.request-nf', $gig) }}" @click.stop 
                                                           class="block px-3 py-2 text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center">
                                                            <i class="fas fa-external-link-alt mr-2 w-4"></i>Abrir Detalhes NF
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                                    <i class="fas fa-clock mr-1"></i>Futuro
                                                </span>
                                            @endif
                                        </div>
                                        
                                        {{-- Ações --}}
                                        <div class="hidden sm:flex col-span-2 justify-end items-center space-x-1">
                                            <a href="{{ route('gigs.show', $gig->id) }}" @click.stop 
                                               class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded" title="Ver Gig">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(!$isFuture)
                                                <a href="{{ route('gigs.request-nf', $gig) }}" @click.stop 
                                                   class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded" title="Detalhes NF">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            @endif
                                            <button @click.stop="open = !open" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                                <i class="fas fa-chevron-down transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Área Expandida --}}
                                <div x-show="open" x-collapse class="px-4 pb-4">
                                    <div class="ml-0 sm:ml-8 p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                            {{-- Coluna 1: Status --}}
                                            <div class="space-y-2">
                                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Status</h4>
                                                <div class="flex items-center">
                                                    <span class="w-3 h-3 rounded-full {{ $gig->payment_status === 'pago' ? 'bg-green-500' : ($gig->payment_status === 'vencido' ? 'bg-red-500' : 'bg-gray-400') }} mr-2"></span>
                                                    <span class="text-gray-600 dark:text-gray-400">Cliente: </span>
                                                    <span class="font-medium text-gray-800 dark:text-white ml-1">{{ ucfirst($gig->payment_status) }}</span>
                                                </div>
                                                <div class="flex items-center">
                                                    <span class="w-3 h-3 rounded-full {{ $gig->artist_payment_status === 'pago' ? 'bg-green-500' : 'bg-gray-400' }} mr-2"></span>
                                                    <span class="text-gray-600 dark:text-gray-400">Artista: </span>
                                                    <span class="font-medium text-gray-800 dark:text-white ml-1">{{ ucfirst($gig->artist_payment_status) }}</span>
                                                </div>
                                            </div>
                                            
                                            {{-- Coluna 2: Valores --}}
                                            <div class="space-y-2">
                                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Valores</h4>
                                                <p><span class="text-gray-500">Cachê Líquido:</span> <span class="font-semibold text-green-600">R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</span></p>
                                                @if($hasExpenses)
                                                    <p><span class="text-gray-500">Despesas Conf.:</span> <span class="font-medium">R$ {{ number_format($totalConfirmed, 2, ',', '.') }}</span></p>
                                                @endif
                                            </div>
                                            
                                            {{-- Coluna 3: Ações Rápidas --}}
                                            <div class="space-y-2">
                                                <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Ações</h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="{{ route('gigs.show', $gig->id) }}" class="inline-flex items-center px-2 py-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded text-xs">
                                                        <i class="fas fa-eye mr-1"></i>Ver Gig
                                                    </a>
                                                    @if(!$isFuture)
                                                        <a href="{{ route('gigs.request-nf', $gig) }}" class="inline-flex items-center px-2 py-1 bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/50 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded text-xs">
                                                            <i class="fas fa-file-invoice mr-1"></i>NF/Fechamento
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Despesas (se houver) --}}
                                        @if($hasExpenses)
                                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                                <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Despesas do Evento</h4>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    @foreach($gigCosts->take(4) as $cost)
                                                        <div class="flex items-center justify-between text-xs {{ !$cost->is_confirmed ? 'bg-yellow-50 dark:bg-yellow-900/10 p-2 rounded' : '' }}">
                                                            <span class="text-gray-600 dark:text-gray-400">{{ $cost->costCenter->name ?? 'N/A' }}</span>
                                                            <span class="font-mono text-gray-700 dark:text-gray-300">
                                                                {{ $cost->currency }} {{ number_format($cost->value, 2, ',', '.') }}
                                                                @if($cost->is_confirmed)
                                                                    <i class="fas fa-check-circle text-green-500 ml-1"></i>
                                                                @else
                                                                    <i class="fas fa-clock text-yellow-500 ml-1"></i>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                    @if($gigCosts->count() > 4)
                                                        <p class="text-xs text-gray-400 col-span-2">+ {{ $gigCosts->count() - 4 }} despesa(s)</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <i class="fas fa-calendar-times text-gray-300 text-5xl mb-4"></i>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum evento encontrado para este período.</p>
                                <a href="{{ route('artists.show', $artist) }}" class="inline-flex items-center mt-4 text-primary-600 hover:text-primary-700 text-sm">
                                    <i class="fas fa-times mr-1"></i>Limpar filtros
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function artistDashboard() {
            return {
                init() {
                    this.initChart();
                },
                initChart() {
                    const ctx = document.getElementById('performanceChart');
                    if (!ctx) return;
                    
                    const chartData = @json($chartData);
                    
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 15,
                                        font: { size: 11 }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 0});
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: { display: false }
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return 'R$ ' + (value / 1000).toFixed(0) + 'k';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            };
        }
    </script>
    @endpush

    {{-- Modais Inline para cada Gig --}}
    @foreach($realizedGigs->merge($futureGigs) as $gig)
        @php
            $settlementStage = $gig->settlement?->settlement_stage ?? 'aguardando_conferencia';
        @endphp
        
        {{-- Modal de NF/Recibo (para status fechamento_enviado) --}}
        @if($settlementStage === 'fechamento_enviado')
        <div x-data="{ open: false }" 
             @open-nf-modal-{{ $gig->id }}.window="open = true"
             @keydown.escape.window="open = false"
             x-show="open" 
             class="fixed inset-0 z-[100] overflow-y-auto" 
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="open = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-auto shadow-2xl p-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            <i class="fas fa-file-invoice text-yellow-500 mr-2"></i>Registrar NF/Recibo
                        </h3>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 my-3">
                        Gig #{{ $gig->id }} - {{ $gig->artist->name ?? 'N/A' }}<br>
                        <span class="text-xs text-gray-500">{{ $gig->location_event_details }}</span>
                    </p>
                    <form action="{{ route('artists.settlements.receiveDocument', $gig) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf @method('PATCH')
                        <input type="hidden" name="redirect_to" value="artist">
                        <input type="hidden" name="artist_id" value="{{ $artist->id }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Documento <span class="text-red-500">*</span></label>
                            <select name="documentation_type" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option value="">Selecione...</option>
                                <option value="nf">Nota Fiscal</option>
                                <option value="rpa">RPA</option>
                                <option value="recibo">Recibo</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Número do Documento</label>
                            <input type="text" name="documentation_number" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Anexar Documento (PDF, JPG, PNG)</label>
                            <input type="file" name="documentation_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-yellow-50 file:text-yellow-700">
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 rounded-md">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-md">Registrar NF/Recibo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Modal de Pagamento (para status documentacao_recebida) --}}
        @if($settlementStage === 'documentacao_recebida')
        <div x-data="{ open: false }" 
             @open-payment-modal-{{ $gig->id }}.window="open = true"
             @keydown.escape.window="open = false"
             x-show="open" 
             class="fixed inset-0 z-[100] overflow-y-auto" 
             style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="open = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-md w-full mx-auto shadow-2xl p-6">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>Registrar Pagamento
                        </h3>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 my-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md">
                        <strong>{{ $gig->artist->name ?? 'N/A' }}</strong> - Gig #{{ $gig->id }}<br>
                        <span class="text-green-600 font-semibold">Valor Líquido: R$ {{ number_format($gig->calculated_artist_net_payout_brl ?? 0, 2, ',', '.') }}</span>
                    </p>
                    <form action="{{ route('artists.settlements.settle', $gig) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <input type="hidden" name="redirect_to" value="artist">
                        <input type="hidden" name="artist_id" value="{{ $artist->id }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data do Pagamento <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Pago <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="payment_value" value="{{ $gig->calculated_artist_net_payout_brl ?? 0 }}" required class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comprovante (PDF, JPG, PNG)</label>
                            <input type="file" name="payment_proof_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:bg-green-50 file:text-green-700">
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 rounded-md">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md">Registrar Pagamento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</x-app-layout>