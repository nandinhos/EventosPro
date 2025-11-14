<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Status de Desenvolvimento
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 border rounded">
                        <div class="text-sm text-gray-500">Branch</div>
                        <div class="text-lg font-semibold">{{ $branch ?? 'n/a' }}</div>
                        <div class="text-sm text-gray-500">Commit</div>
                        <div class="text-lg">{{ $commit ?? 'n/a' }}</div>
                    </div>
                    <div class="p-4 border rounded">
                        <div class="text-sm text-gray-500">Health</div>
                        <div class="text-lg font-semibold">{{ ($health['up'] ?? false) ? 'UP' : 'DOWN' }}</div>
                    </div>
                    <div class="p-4 border rounded">
                        <div class="text-sm text-gray-500">Watcher</div>
                        <div class="text-lg font-semibold">{{ data_get($status, 'processes.watcher.running') ? 'Rodando' : 'Parado' }}</div>
                        <div class="text-xs text-gray-500">PID: {{ data_get($status, 'processes.watcher.pid') }}</div>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="font-semibold">Última Mudança</h3>
                    <pre class="text-sm bg-gray-100 p-3 rounded">{{ json_encode($status['lastChange'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                </div>

                <div class="mt-6">
                    <h3 class="font-semibold">Última Execução</h3>
                    <pre class="text-sm bg-gray-100 p-3 rounded">{{ json_encode($status['lastRun'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                </div>

                <div class="mt-6">
                    <a href="{{ route('test-report.index') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded">Abrir Test Report</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

