@props([
    'size' => 'md',
])

@php
    $spacing = match($size) {
        'xs' => '-space-x-1.5',
        'sm' => '-space-x-2',
        'lg' => '-space-x-4',
        'xl' => '-space-x-5',
        default => '-space-x-3',
    };
@endphp

<div {{ $attributes->class([
    'flex items-center',
    $spacing,
    '[&>*]:ring-2 [&>*]:ring-kore-surface [&>*]:rounded-full',
]) }}>
    {{ $slot }}

    @if(isset($overflow))
        <div class="relative z-0">{{ $overflow }}</div>
    @endif
</div>
