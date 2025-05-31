<div>
    @if ($type && $filters)
        <a href="{{ route('reports.export', ['type' => $type, 'format' => 'xlsx'] + (array) $filters) }}" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded-md flex items-center text-sm mb-2">
            <i class="fas fa-file-excel mr-1"></i> Excel
        </a>
        <a href="{{ route('reports.export', ['type' => $type, 'format' => 'pdf'] + (array) $filters) }}" class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded-md flex items-center text-sm">
            <i class="fas fa-file-pdf mr-1"></i> PDF
        </a>
    @endif
</div>