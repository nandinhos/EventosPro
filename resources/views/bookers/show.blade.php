<x-app-layout>
    <x-slot name="header">
        {{-- ***** 1. CABEÇALHO REORGANIZADO COM FLEXBOX ***** --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            {{-- Título --}}
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-white leading-tight">
                    Central de Desempenho: <span class="text-primary-600 dark:text-primary-400">{{ $booker->name }}</span>
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Hub de informações de performance, comissões e atividades.</p>
            </div>
            
            {{-- Ações Rápidas (Alinhadas à Direita) --}}
            <div class="flex items-center space-x-2 mt-4 md:mt-0">
                <a href="{{ route('bookers.index') }}" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-md text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left fa-fw"></i>
                </a>
                <a href="{{ route('gigs.create', ['booker_id' => $booker->id]) }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-semibold flex items-center">
                    <i class="fas fa-plus mr-2"></i> Adicionar Gig
                </a>
                <a href="{{ route('bookers.edit', $booker) }}" class="bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-800 dark:text-white px-4 py-2 rounded-md text-sm font-semibold">
                    Editar Cadastro
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- ***** 2. CARDS DE RESUMO (COLORIDOS E CLICÁVEIS) ***** --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Card: Total Vendido --}}
                <a href="{{ route('gigs.index', ['booker_id' => $booker->id, 'start_date' => now()->subYear()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="block bg-blue-100 dark:bg-blue-900/20 p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Total Vendido (12 meses)</h3>
                    <p class="text-lg font-semibold text-blue-800 dark:text-blue-300 mt-1">R$ {{ number_format($totalSoldValue, 2, ',', '.') }}</p>
                </a>
                
                {{-- Card: Gigs Vendidas --}}
                <a href="{{ route('gigs.index', ['booker_id' => $booker->id, 'start_date' => now()->subYear()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="block bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Gigs Vendidas (12 meses)</h3>
                    <p class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mt-1">{{ $totalGigsSold }}</p>
                </a>

                {{-- Card: Comissão Recebida --}}
                <a href="{{ route('reports.index', ['tab' => 'commissions', 'booker_id' => $booker->id]) }}" class="block bg-green-100 dark:bg-green-900/20 p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão Total Recebida</h3>
                    <p class="text-lg font-semibold text-green-800 dark:text-green-300 mt-1">R$ {{ number_format($commissionReceived, 2, ',', '.') }}</p>
                </a>

                {{-- Card: Comissão a Receber --}}
                <a href="{{ route('reports.index', ['tab' => 'commissions', 'booker_id' => $booker->id]) }}" class="block bg-orange-100 dark:bg-orange-900/20 p-4 rounded-lg shadow hover:shadow-lg transition-shadow">
                    <h3 class="text-sm text-gray-500 dark:text-gray-400">Comissão a Receber</h3>
                    <p class="text-lg font-semibold text-orange-800 dark:text-orange-300 mt-1">R$ {{ number_format($commissionToReceive, 2, ',', '.') }}</p>
                </a>
            </div>

            {{-- Componente Gráfico (sem alterações) --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Evolução de Comissões Pagas (Últimos 12 Meses)</h3>
                <div class="h-72">
                    <canvas id="commissionsChart"></canvas>
                </div>
            </div>

            {{-- Componente Tabelas (sem alterações) --}}
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
{{-- Script do Chart.js (sem alterações) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('commissionsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
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