@props([
    'id',
    'label' => '',
    'value' => '',
    'type' => 'text',
    'placeholder' => '',
])

<div>
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
        </label>
    @endif

    <input 
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $id }}"
        value="{{ old($id, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge([
            'class' => 'w-full text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:ring-primary-500 focus:border-primary-500',
        ]) }}
    >
</div>
