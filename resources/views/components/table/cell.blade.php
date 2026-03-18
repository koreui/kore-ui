@props([
    'align' => 'left',
    'wrap' => true,
    'densityClasses' => 'px-4 py-2.5 text-sm',
])

@php
    $alignClass = match($align) {
        'center' => 'text-center',
        'right'  => 'text-right',
        default  => 'text-left',
    };
@endphp

<td {{ $attributes->class([
    $densityClasses,
    $alignClass,
    'text-kore-fg',
    'whitespace-nowrap' => !$wrap,
]) }}>
    {{ $slot }}
</td>
