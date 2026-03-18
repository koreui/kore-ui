@props([])

<div {{ $attributes->class('inline-flex [&>*]:rounded-none [&>*:first-child]:rounded-l-kore-md [&>*:last-child]:rounded-r-kore-md [&>*+*]:-ml-px') }}>
    {{ $slot }}
</div>
