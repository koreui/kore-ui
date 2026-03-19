@php
    $props = $column->getComponentProps();
    $nameValue = $column->getNameValue($row);
@endphp

@if($props['isAvatar'] ?? true)
    <x-kore::avatar
        :src="$value"
        :name="$nameValue"
        :size="$props['size'] ?? 'sm'"
        :shape="$props['shape'] ?? 'circle'"
    />
@else
    <img
        src="{{ $value }}"
        @if($nameValue) alt="{{ $nameValue }}" @else alt="" @endif
        @class([
            'object-cover',
            match($props['size'] ?? 'sm') {
                'xs' => 'size-6',
                'sm' => 'size-8',
                'lg' => 'size-12',
                'xl' => 'size-16',
                default => 'size-10',
            },
            match($props['shape'] ?? 'circle') {
                'square' => 'rounded-kore-md',
                default => 'rounded-full',
            },
        ])
    />
@endif
