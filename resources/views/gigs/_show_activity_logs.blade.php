@props(['activityLogs'])
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
                     @if($log->causer) por {{ $log->causer->name ?? 'Sistema' }} @endif
                 </p>
            </div>
         @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma atividade registrada.</p>
         @endforelse

         @if ($activityLogs->hasPages())
            <div class="mt-4">
                {{ $activityLogs->links() }}
            </div>
         @endif
    </div>
</div>