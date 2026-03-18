@props([
    'multiple' => false,
    'variant' => null,
])

@php
    $variant = $variant ?? config('kore-ui.ui.accordion.variant', 'bordered');
@endphp

<div
    {{ $attributes
        ->except(['multiple', 'variant'])
        ->class([
            'border border-kore-border rounded-kore-lg divide-y divide-kore-border' => $variant === 'bordered',
            'divide-y divide-kore-border' => $variant === 'flush',
            'space-y-3' => $variant === 'separated',
        ])
    }}
    x-data="KoreAccordion({ multiple: @js($multiple), variant: @js($variant) })"
>
    <input type="hidden" x-ref="hiddenInput" {{ $attributes->wire('model') }} />
    {{ $slot }}
</div>
