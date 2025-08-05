<x-app-layout>
    {{-- Header da Página (Opcional, pode ser definido no slot do layout) --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Seção de Resumo Fixo --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Visão Geral</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">Atualizado em {{ now()->format('d/m/Y H:i') }}</span>
            </div>
        
        {{-- Linha de Cards de KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            {{-- Card Total de Gigs --}}
            <a href="{{ route('gigs.index') }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="bg-indigo-100 dark:bg-indigo-900/30 p-3 rounded-lg">
                        <i class="fas fa-compact-disc text-indigo-600 dark:text-indigo-400 text-xl fa-fw"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Gigs</h3>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $totalGigsCount }}</p>
                    </div>
                </div>
            </a>

            {{-- Card Gigs Ativas/Futuras --}}
            <a href="{{ route('gigs.index', ['start_date' => today()->format('Y-m-d')]) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                        <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400 text-xl fa-fw"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Gigs Ativas/Futuras</h3>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $activeFutureGigsCount }}</p>
                    </div>
                </div>
            </a>

            {{-- Card Pagamentos de Cliente Vencidos - Redireciona para Status Financeiro --}}
            <a href="{{ route('reports.delinquency') }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="bg-red-100 dark:bg-red-900/30 p-3 rounded-lg">
                        <i class="fas fa-file-invoice-dollar text-red-600 dark:text-red-400 text-xl fa-fw"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pgto. Cliente Vencido</h3>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $overdueClientPaymentsCount }}</p>
                    </div>
                </div>
            </a>

            {{-- Card Pagamentos de Artista Pendentes --}}
            <a href="{{ route('gigs.index', ['artist_payment_status' => 'pendente']) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <div class="flex items-center">
                    <div class="bg-yellow-100 dark:bg-yellow-900/30 p-3 rounded-lg">
                        <i class="fas fa-user-clock text-yellow-600 dark:text-yellow-400 text-xl fa-fw"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pgto. Artista Pendente</h3>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $pendingArtistPaymentsCount }}</p>
                    </div>
                </div>
            </a>

            {{-- Card Pagamentos de Booker Pendentes - Redireciona para Comissões --}}
            <a href="{{ route('reports.index') }}?tab=commissions" class="block bg-orange-50 dark:bg-orange-900/20 p-6 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:scale-[1.02] border-l-4 border-orange-500">
                <div class="flex items-center">
                    <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-lg">
                        <i class="fas fa-user-tag text-orange-600 dark:text-orange-400 text-xl fa-fw"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pgto. Booker Pendente</h3>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $pendingBookerPaymentsCount }}</p>
                    </div>
                </div>
            </a>
        </div>

        </div>

        {{-- Seção de Filtros --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Filtros de Período</h3>
            <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Inicial</label>
                    <input type="date" name="start_date" id="start_date" 
                           value="{{ $filters['start_date'] ?? '' }}" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Final</label>
                    <input type="date" name="end_date" id="end_date" 
                           value="{{ $filters['end_date'] ?? '' }}" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-filter mr-2"></i>Filtrar
                    </button>
                    @if(isset($filters['start_date']) || isset($filters['end_date']))
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                            <i class="fas fa-times mr-2"></i>Limpar
                        </a>
                    @endif
                </div>
            </form>
            
            @if(isset($filters['start_date']) || isset($filters['end_date']))
                <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    @if(isset($filters['start_date']) && isset($filters['end_date']))
                        Exibindo dados de {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}
                    @elseif(isset($filters['start_date']))
                        Exibindo dados a partir de {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }}
                    @elseif(isset($filters['end_date']))
                        Exibindo dados até {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}
                    @endif
                </div>
            @endif
        </div>

        {{-- Seção de Métricas Dinâmicas --}}
        <div class="space-y-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Métricas do Período</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Card Venda (Mês) - Baseado na data de contrato --}}
                <a href="{{ $performanceReportUrl }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Venda (Mês)</h4>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalSalesThisMonth, 2, ',', '.') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $salesThisMonthCount }} {{ Str::plural('gig', $salesThisMonthCount) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Baseado na data de contrato em {{ \Carbon\Carbon::now()->translatedFormat('F/Y') }}</p>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-lg">
                            <i class="fas fa-file-contract text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                    </div>
                </a>

                {{-- Card Cachê Bruto (Mês) - Baseado na data do evento --}}
                <a href="{{ route('gigs.index', ['start_date' => $startOfMonth->format('Y-m-d'), 'end_date' => $endOfMonth->format('Y-m-d')]) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Cachê Bruto (Mês)</h4>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalCacheThisMonth, 2, ',', '.') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $gigsThisMonthCount }} {{ Str::plural('gig', $gigsThisMonthCount) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Gigs com data de evento em {{ \Carbon\Carbon::now()->translatedFormat('F/Y') }}</p>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
                            <i class="fas fa-calendar-check text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </a>

                {{-- Card Comissão Agência (Mês) --}}
                <a href="{{ route('reports.performance.index') }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Comissão Agência (Mês)</h4>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalAgencyCommissionThisMonth, 2, ',', '.') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $gigsThisMonthCount }} {{ Str::plural('gig', $gigsThisMonthCount) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Projeção baseada nas Gigs do mês</p>
                        </div>
                        <div class="bg-purple-100 dark:bg-purple-900/30 p-2 rounded-lg">
                            <i class="fas fa-percentage text-purple-600 dark:text-purple-400 text-xl"></i>
                        </div>
                    </div>
                </a>

                {{-- Card Comissão Bookers (Mês) --}}
                <a href="{{ route('reports.index') }}?tab=commissions" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow border-l-4 border-orange-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Comissão Bookers (Mês)</h4>
                            <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalBookerCommissionThisMonth, 2, ',', '.') }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $gigsThisMonthCount }} {{ Str::plural('gig', $gigsThisMonthCount) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Projeção baseada nas Gigs do mês</p>
                        </div>
                        <div class="bg-orange-100 dark:bg-orange-900/30 p-2 rounded-lg">
                            <i class="fas fa-user-tag text-orange-600 dark:text-orange-400 text-xl"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Seção de Listas Rápidas e Gráficos --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Próximas Gigs --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Próximas Gigs</h3>
                    <a href="{{ route('gigs.index') }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 text-sm font-medium">Ver Todas</a>
                </div>
                @if($nextGigs->isNotEmpty())
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($nextGigs as $gig)
                    <li class="py-3">
                        <a href="{{ route('gigs.show', $gig) }}" class="block hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 -m-2 rounded-md">
                            <div class="flex items-center justify-between">
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $gig->artist->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $gig->location_event_details }}</p>
                                </div>
                                <div class="text-sm text-right">
                                    <p class="text-gray-700 dark:text-gray-300">{{ $gig->gig_date->format('d/m/Y') }}</p>
                                    <x-status-badge :status="$gig->payment_status" type="payment" />
                                </div>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @else
                 <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma gig futura agendada.</p>
                @endif
            </div>

            {{-- Gráfico de Faturamento Mensal --}}
            <a href="{{ route('reports.performance.index') }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow transform hover:scale-[1.005] group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Faturamento Mensal</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->subMonths(11)->format('M/Y') }} - {{ now()->format('M/Y') }}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                        <i class="fas fa-external-link-alt mr-1"></i> Ver relatório
                    </span>
                </div>
                <div class="h-72 relative">
                    <canvas id="monthlyRevenueChart"></canvas>
                    <div id="chartTooltip" class="absolute bg-white dark:bg-gray-900 p-3 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10 pointer-events-none opacity-0 transition-opacity duration-200 w-48">
                        <div class="text-sm font-medium text-gray-900 dark:text-white"><span id="tooltipMonth"></span></div>
                        <div class="mt-1">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-medium">Faturamento:</span> <span id="tooltipValue" class="font-semibold text-gray-800 dark:text-gray-200"></span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-medium">Gigs:</span> <span id="tooltipGigs" class="font-semibold text-gray-800 dark:text-gray-200"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

{{-- Adiciona o script do Chart.js no final da página --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> {{-- Inclui Chart.js via CDN --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctxMonthlyRevenue = document.getElementById('monthlyRevenueChart');
        const chartTooltip = document.getElementById('chartTooltip');
        const tooltipMonth = document.getElementById('tooltipMonth');
        const tooltipValue = document.getElementById('tooltipValue');
        const tooltipGigs = document.getElementById('tooltipGigs');
        
        // Dados passados do PHP/Controller
        const chartLabels = @json($chartLabels);
        const chartData = @json($chartData);
        const chartGigsCount = @json($chartGigsCount);
        
        // Função para formatar valor em reais
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL',
                minimumFractionDigits: 2
            }).format(value);
        };
        
        // Configuração do tooltip
        const positionTooltip = (tooltipEl, position) => {
            if (!tooltipEl || !position) return;
            
            // Posiciona o tooltip ao lado do cursor
            tooltipEl.style.opacity = 1;
            tooltipEl.style.left = position.x + 'px';
            tooltipEl.style.top = position.y + 'px';
            
            // Ajusta para não sair da tela
            const tooltipRect = tooltipEl.getBoundingClientRect();
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            
            if (position.x + tooltipRect.width > windowWidth) {
                tooltipEl.style.left = (position.x - tooltipRect.width - 10) + 'px';
            }
            
            if (position.y + tooltipRect.height > windowHeight) {
                tooltipEl.style.top = (position.y - tooltipRect.height - 10) + 'px';
            }
        };
        
        // Configuração do gráfico
        if (ctxMonthlyRevenue) {
            // Mapeia os meses para facilitar a conversão
            const monthMap = {
                'jan': '01', 'fev': '02', 'mar': '03', 'abr': '04', 'mai': '05', 'jun': '06',
                'jul': '07', 'ago': '08', 'set': '09', 'out': '10', 'nov': '11', 'dez': '12'
            };
            
            // Cria um array de chaves no formato 'YYYY-MM' correspondente a cada mês no gráfico
            const monthKeys = chartLabels.map(label => {
                const [monthAbbr, year] = label.split('/');
                const monthNumber = monthMap[monthAbbr.toLowerCase()];
                return `20${year}-${monthNumber}`; // Formato: 'YYYY-MM'
            });

            const chart = new Chart(ctxMonthlyRevenue, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Faturamento (R$)',
                        data: chartData,
                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(93, 92, 222, 0.7)',
                        hoverBorderColor: 'rgba(79, 70, 229, 1)',
                        borderRadius: 4,
                        barThickness: 'flex',
                        maxBarThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    onHover: (event, chartElement) => {
                        const target = event.native?.target;
                        if (target) {
                            target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                        }
                    },
                    onClick: (event, elements) => {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const label = chartLabels[index];
                            
                            // Extrai o mês e ano do label (formato: 'Mmm/YY')
                            const [monthAbbr, yearSuffix] = label.split('/');
                            const month = monthMap[monthAbbr.toLowerCase()];
                            const year = '20' + yearSuffix; // Converte para ano completo
                            
                            // Cria datas para o primeiro e último dia do mês
                            const startDate = new Date(year, parseInt(month) - 1, 1);
                            const endDate = new Date(year, parseInt(month), 0); // Último dia do mês
                            
                            // Formata as datas para o formato YYYY-MM-DD
                            const formattedStartDate = startDate.toISOString().split('T')[0];
                            const formattedEndDate = endDate.toISOString().split('T')[0];
                            
                            console.log('Navegando para:', formattedStartDate, 'até', formattedEndDate);
                            
                            // Redireciona para a rota de relatórios com os parâmetros de data
                            window.location.href = `{{ route('reports.performance.index') }}?start_date=${formattedStartDate}&end_date=${formattedEndDate}`;
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 0 });
                                }
                            },
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            enabled: false, // Desativa o tooltip padrão
                            external: (context) => {
                                // Tooltip personalizado
                                const tooltipEl = chartTooltip;
                                
                                // Ocultar se não houver tooltip
                                if (context.tooltip.opacity === 0) {
                                    tooltipEl.style.opacity = 0;
                                    return;
                                }
                                
                                // Atualizar conteúdo do tooltip
                                if (context.tooltip.dataPoints) {
                                    const dataIndex = context.tooltip.dataPoints[0].dataIndex;
                                    const value = context.tooltip.dataPoints[0].raw;
                                    const monthYear = chartLabels[dataIndex];
                                    
                                    // Encontra a contagem de gigs para este mês
                                    const [monthAbbr, yearSuffix] = monthYear.toLowerCase().split('/');
                                    const monthKey = monthKeys[dataIndex];
                                    const gigsCount = chartGigsCount[monthKey] || 0;
                                    
                                    console.log('Tooltip - Mês:', monthYear, 'Chave:', monthKey, 'Gigs:', gigsCount);
                                    
                                    // Atualiza o conteúdo do tooltip
                                    tooltipMonth.textContent = monthYear;
                                    tooltipValue.textContent = formatCurrency(value);
                                    tooltipGigs.textContent = `${gigsCount} ${gigsCount === 1 ? 'gig' : 'gigs'}`;
                                }
                                
                                // Posiciona o tooltip
                                positionTooltip(tooltipEl, {
                                    x: context.tooltip.caretX,
                                    y: context.tooltip.caretY - 10
                                });
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
</x-app-layout>