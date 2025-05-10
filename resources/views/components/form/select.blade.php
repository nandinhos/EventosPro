@props([
    'id',
    'label' => '',
    'options' => [],
    'selected' => null,
    'empty' => null,
])

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
        </label>
    @endif

    <select 
        id="{{ $id }}" 
        name="{{ $id }}" 
        {{ $attributes->merge([
            'class' => 'w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-primary-500 focus:border-primary-500',
        ]) }}
    >
        @if ($empty)
            <option value="">{{ $empty }}</option>
        @endif

        @foreach ($options as $key => $value)
            <option value="{{ $key }}" {{ (string)$selected === (string)$key ? 'selected' : '' }}>
                {{ $value }}
            </option>
        @endforeach

        {{-- Slot para opções extras como "sem_booker" --}}
        {{ $slot }}
    </select>
</div>
