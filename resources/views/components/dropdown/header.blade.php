@props([
    'label' => null,
])

<div {{ $attributes->class('px-3 py-2 text-xs font-semibold text-kore-muted-fg uppercase tracking-wider') }}>
    @if($label)
        {{ $label }}
    @endif

    {{ $slot }}
</div>
