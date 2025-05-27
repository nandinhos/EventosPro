{{-- resources/views/components/header-notifications.blade.php --}}

@php
    $activities = \App\Models\ActivityLog::with(['subject', 'causer'])
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get();
@endphp

<div class="relative" x-data="{ notificationsOpen: false }">
    {{-- Botão do Sino --}}
    <button @click="notificationsOpen = !notificationsOpen" class="relative p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 rounded-full">
        <i class="fas fa-bell"></i>
        @if($activities->isNotEmpty())
        <span class="absolute top-0 right-0 block h-2 w-2 transform -translate-y-1/2 translate-x-1/2 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-800"></span>
        @endif
    </button>

    {{-- Dropdown de Notificações --}}
    <div x-show="notificationsOpen"
         @click.away="notificationsOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="absolute right-0 mt-2 w-80 origin-top-right bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-20"
         style="display: none;">
        <div class="py-1">
            {{-- Cabeçalho do Dropdown --}}
            <div class="px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Atividades Recentes</h3>
            </div>

            {{-- Lista de Notificações --}}
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                @forelse($activities as $activity)
                    <div class="block px-4 py-3">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 bg-primary-500 rounded-full p-1.5">
                                @if(str_contains(strtolower($activity->description), 'pagamento'))
                                    <i class="fas fa-money-bill-wave text-white text-xs fa-fw"></i>
                                @elseif(str_contains(strtolower($activity->description), 'gig'))
                                    <i class="fas fa-calendar-check text-white text-xs fa-fw"></i>
                                @else
                                    <i class="fas fa-info-circle text-white text-xs fa-fw"></i>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($activity->subject)
                                        {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">Nenhuma atividade recente.</div>
                @endforelse
            </div>

            {{-- Rodapé do Dropdown --}}
            @if($activities->isNotEmpty())
            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                <a href="#" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                    Ver todas as atividades
                </a>
            </div>
            @endif
        </div>
    </div>
</div>