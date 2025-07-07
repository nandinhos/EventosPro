<x-app-layout>
    {{-- Header da Página (Opcional, pode ser definido no slot do layout) --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Linha de Cards de KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            {{-- ***** NOVO CARD: Total de Gigs ***** --}}
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

            {{-- Card Pagamentos de Cliente Vencidos --}}
            <a href="{{ route('gigs.index', ['payment_status' => 'vencido']) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
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

            {{-- Card Pagamentos de Booker Pendentes --}}
            <a href="{{ route('gigs.index', ['booker_payment_status' => 'pendente']) }}" class="block bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
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

        {{-- Linha de Cards Financeiros (Resumo Mês Atual) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Cachê Bruto (Mês Atual)</h4>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalCacheThisMonth, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Gigs com data do evento em {{ \Carbon\Carbon::now()->translatedFormat('F/Y') }}</p>
            </div>
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Comissão Agência (Mês)</h4>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalAgencyCommissionThisMonth, 2, ',', '.') }}</p>
                 <p class="text-xs text-gray-400 dark:text-gray-500">Estimativa baseada nas Gigs do mês</p>
            </div>
             <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Comissão Bookers (Mês)</h4>
                <p class="text-2xl font-bold text-gray-800 dark:text-white">R$ {{ number_format($totalBookerCommissionThisMonth, 2, ',', '.') }}</p>
                 <p class="text-xs text-gray-400 dark:text-gray-500">Estimativa baseada nas Gigs do mês</p>
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
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Faturamento Mensal (Gigs Pagas/Realizadas nos últimos 12 meses)</h3>
                <div class="h-72"> {{-- Definir altura do container do gráfico --}}
                    <canvas id="monthlyRevenueChart"></canvas> {{-- Canvas para o gráfico --}}
                </div>
            </div>
        </div>
    </div>

{{-- Adiciona o script do Chart.js no final da página --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> {{-- Inclui Chart.js via CDN --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctxMonthlyRevenue = document.getElementById('monthlyRevenueChart');
        if (ctxMonthlyRevenue) {
            // Dados passados do PHP/Controller
            const chartLabels = @json($chartLabels);
            const chartData = @json($chartData);

            new Chart(ctxMonthlyRevenue, {
                type: 'bar', // Ou 'line'
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Faturamento (R$)',
                        data: chartData,
                        backgroundColor: 'rgba(93, 92, 222, 0.5)', // Cor primária com opacidade
                        borderColor: 'rgba(93, 92, 222, 1)',   // Cor primária sólida
                        borderWidth: 1,
                        borderRadius: 4, // Bordas arredondadas para barras
                        hoverBackgroundColor: 'rgba(93, 92, 222, 0.7)',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 0 });
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false // Remove linhas de grade do eixo X
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true, // Pode ocultar se só tiver um dataset
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += 'R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                    return label;
                                }
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