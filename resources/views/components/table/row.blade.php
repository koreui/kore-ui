@props([
    'striped' => false,
    'hoverable' => true,
    'index' => 0,
])

<tr {{ $attributes->class([
    'bg-kore-muted/30' => $striped && $index % 2 === 1,
    'hover:bg-kore-muted/40 transition-colors' => $hoverable,
]) }}>
    {{ $slot }}
</tr>
