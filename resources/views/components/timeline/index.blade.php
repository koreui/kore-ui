@props([
    'variant' => null,
    'color' => 'primary',
])

@php
    $variant = $variant ?? config('kore-ui.ui.timeline.variant', 'left');

    $containerClass = match($variant) {
        'right' => 'kore-timeline-right',
        'alternate' => 'kore-timeline-alternate',
        default => 'kore-timeline-left',
    };
@endphp

<ol {{ $attributes->class(['relative list-none m-0 p-0', $containerClass]) }}
    role="list"
    data-timeline-variant="{{ $variant }}"
    data-timeline-color="{{ $color }}">
    {{ $slot }}
</ol>
