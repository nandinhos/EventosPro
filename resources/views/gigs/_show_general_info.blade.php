@props(['gig'])
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Informações Gerais</h3>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
        <div><strong class="text-gray-500 dark:text-gray-400">Artista:</strong> {{ $gig->artist->name ?? 'N/A' }}</div>
        <div><strong class="text-gray-500 dark:text-gray-400">Booker:</strong> {{ $gig->booker->name ?? 'Agência/Sem Booker' }}</div>
        <div><strong class="text-gray-500 dark:text-gray-400">Data Evento:</strong> {{ $gig->gig_date->format('d/m/Y') }}</div>
        <div class="md:col-span-2"><strong class="text-gray-500 dark:text-gray-400">Local/Evento:</strong> {{ $gig->location_event_details }}</div>
        <div><strong class="text-gray-500 dark:text-gray-400">Contrato Nº:</strong> {{ $gig->contract_number ?? 'N/A' }}</div>
        <div><strong class="text-gray-500 dark:text-gray-400">Data Contrato:</strong> {{ $gig->contract_date?->format('d/m/Y') ?? 'N/A' }}</div>
        <div class="md:col-span-2"><strong class="text-gray-500 dark:text-gray-400">Status Contrato:</strong> <x-status-badge :status="$gig->contract_status" type="contract" /></div>
        @if($gig->notes)
            <div class="md:col-span-2"><strong class="text-gray-500 dark:text-gray-400">Notas:</strong><br><span class="whitespace-pre-wrap">{{ $gig->notes }}</span></div>
        @endif
        @if($gig->tags->isNotEmpty())
            <div class="md:col-span-2">
                <strong class="text-gray-500 dark:text-gray-400 block mb-1">Tags:</strong>
                 <div class="flex flex-wrap gap-1">
                    @foreach($gig->tags as $tag)
                        <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium px-2 py-0.5 rounded">{{ $tag->name }}</span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>