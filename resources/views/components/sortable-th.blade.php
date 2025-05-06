{{--
    Componente para criar um cabeçalho de tabela ordenável.

    Props:
    - column (string, required): O nome da coluna no banco de dados para ordenar.
    - currentSortBy (string, required): A coluna pela qual a tabela está ordenada atualmente.
    - currentSortDirection (string, required): A direção atual ('asc' ou 'desc').
    - defaultDirection (string, optional, default: 'desc'): Direção padrão ao clicar pela primeira vez.
    - label (string, optional): O texto a ser exibido no header (se omitido, usa o nome da coluna capitalizado).
    - class (string, optional): Classes CSS adicionais para o <th>.
--}}
@props([
    'column',
    'currentSortBy',
    'currentSortDirection',
    'defaultDirection' => 'desc', // Default para datas é geralmente desc
    'label' => null,
])

@php
    // Determina a próxima direção de ordenação ao clicar neste header
    $nextSortDirection = 'asc'; // Começa com asc por padrão
    if ($currentSortBy === $column) {
        $nextSortDirection = ($currentSortDirection === 'asc') ? 'desc' : 'asc'; // Alterna se já estiver ordenando por esta coluna
    } elseif ($defaultDirection === 'desc') {
         // Se for a primeira vez clicando e o default for desc, ordena desc primeiro
         $nextSortDirection = 'desc';
    }


    // Define o ícone a ser exibido (nenhum, seta para cima ou seta para baixo)
    $iconClass = '';
    if ($currentSortBy === $column) {
        $iconClass = $currentSortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    }

    // Texto do label (usa nome da coluna se não for passado)
    $headerLabel = $label ?? Str::ucfirst(str_replace('_', ' ', $column));

    // Monta a URL com os parâmetros de ordenação e mantendo os filtros atuais
    $url = route('gigs.index', array_merge(
        request()->except(['sort_by', 'sort_direction', 'page']), // Mantém filtros, remove paginação e ordenação antiga
        ['sort_by' => $column, 'sort_direction' => $nextSortDirection] // Adiciona nova ordenação
    ));
@endphp

{{-- O cabeçalho da tabela (th) --}}
<th {{ $attributes->merge(['class' => 'px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700']) }}>
    <a href="{{ $url }}" class="flex items-center">
        <span>{{ $headerLabel }}</span>
        {{-- Mostra o ícone apenas se esta coluna estiver ativa --}}
        @if($iconClass)
            <i class="fas {{ $iconClass }} ml-1"></i>
        @else
            {{-- Opcional: Ícone 'neutro' para indicar que é ordenável --}}
            <i class="fas fa-sort opacity-30 ml-1"></i>
        @endif
    </a>
</th>