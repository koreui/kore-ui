@props([
    'label' => null,
])

<div {{ $attributes->class('px-2.5 pt-2 pb-1 text-xs font-medium text-kore-muted-fg') }}>
    @if($label)
        {{ $label }}
    @endif

    {{ $slot }}
</div>
