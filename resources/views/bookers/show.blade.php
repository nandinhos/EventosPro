<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            {{-- Título --}}
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
                    Central de Desempenho: <span class="text-primary-600 dark:text-primary-400">{{ $booker->name }}</span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Hub de informações de performance, comissões e atividades.</p>
            </div>
            
            {{-- Ações Rápidas --}}
            <div class="flex space-x-2 mt-4 md:mt-0">
                <a href="{{ route('gigs.create', ['booker_id' => $booker->id]) }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-semibold flex items-center">
                    <i class="fas fa-plus mr-2"></i> Adicionar Nova Gig
                </a>
                <a href="{{ route('bookers.edit', $booker) }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white px-4 py-2 rounded-md text-sm font-semibold">
                    Editar Cadastro
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- Componente 1: Cards de Resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Vendido (12 meses)</h4>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">R$ {{ number_format($totalSoldValue, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Gigs Vendidas (12 meses)</h4>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $totalGigsSold }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Comissão Total Recebida</h4>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">R$ {{ number_format($commissionReceived, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400">Comissão a Receber</h4>
                    <p class="text-2xl font-bold text-yellow-500 dark:text-yellow-400 mt-1">R$ {{ number_format($commissionToReceive, 2, ',', '.') }}</p>
                </div>
            </div>

            {{-- Componente 2: Gráfico --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Evolução de Comissões Pagas (Últimos 12 Meses)</h3>
                <div class="h-72">
                    <canvas id="commissionsChart"></canvas>
                </div>
            </div>

            {{-- Componente 3: Tabelas --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Coluna Esquerda: Artistas em Destaque --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Artistas em Destaque</h3>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($topArtists as $item)
                            <li class="py-3 flex justify-between items-center">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $item->artist->name }}</span>
                                <div class="text-right">
                                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $item->gigs_count }} Gigs</span>
                                    <span class="text-xs block text-gray-500 dark:text-gray-400">R$ {{ number_format($item->total_value, 2, ',', '.') }}</span>
                                </div>
                            </li>
                        @empty
                            <p class="text-sm text-gray-500">Nenhum dado de artista para exibir.</p>
                        @endforelse
                    </ul>
                </div>

                {{-- Coluna Direita: Gigs Recentes --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Gigs Recentes</h3>
                     <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentGigs as $gig)
                            <li class="py-3">
                                <a href="{{ route('gigs.show', $gig) }}" class="block hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 -m-2 rounded-md">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm">
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $gig->artist->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($gig->location_event_details, 35) }}</p>
                                        </div>
                                        <div class="text-sm text-right">
                                            <p class="text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($gig->sale_date)->format('d/m/Y') }}</p>
                                            <p class="text-xs font-semibold text-gray-500">R$ {{ number_format($gig->cache_value_brl, 2, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @empty
                             <p class="text-sm text-gray-500">Nenhuma gig recente para exibir.</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('commissionsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line', // ou 'bar'
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Comissão Paga (R$)',
                        data: @json($chartData),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.2)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    });
</script>
@endpush
</x-app-layout>