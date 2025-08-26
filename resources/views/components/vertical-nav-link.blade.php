@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center w-full px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border-r-4 border-blue-600 rounded-l-md focus:outline-none focus:text-blue-800 focus:bg-blue-100 focus:border-blue-700 transition duration-150 ease-in-out'
            : 'flex items-center w-full px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:text-gray-900 focus:bg-gray-100 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
