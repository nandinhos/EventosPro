@props([
    'type' // O tipo de relatório, ex: 'overview', 'profitability'
])

<div>
    {{-- Gera a URL para Excel, mesclando os filtros atuais da URL com os parâmetros de exportação --}}
    <a href="{{ route('reports.export', array_merge(request()->all(), ['type' => $type, 'format' => 'xlsx'])) }}" 
       class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md flex items-center text-sm mb-2 transition-colors duration-200">
        <i class="fas fa-file-excel mr-2"></i> Exportar para Excel
    </a>
    
    {{-- Gera a URL para PDF, mesclando os filtros atuais da URL com os parâmetros de exportação --}}
    <a href="{{ route('reports.export', array_merge(request()->all(), ['type' => $type, 'format' => 'pdf'])) }}" 
       class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md flex items-center text-sm transition-colors duration-200">
        <i class="fas fa-file-pdf mr-2"></i> Exportar para PDF
    </a>
</div>