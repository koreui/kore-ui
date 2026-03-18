@props([
    'variant' => null,
    'justify' => 'between',
])

@php
    $variant = $variant ?? config('kore-ui.ui.toolbar.variant', 'default');

    $justifyClass = match($justify) {
        'start' => 'justify-start',
        'end' => 'justify-end',
        'center' => 'justify-center',
        default => 'justify-between',
    };
@endphp

<div
    {{ $attributes
        ->except(['variant', 'justify'])
        ->class([
            'flex items-center gap-2',
            $justifyClass,
            'border border-kore-border rounded-kore-md bg-kore-surface px-3 py-2' => $variant === 'bordered',
            'px-1 py-1' => $variant === 'default',
        ])
    }}
    role="toolbar"
>
    @if(isset($start))
        <div class="flex items-center gap-2 shrink-0">
            {{ $start }}
        </div>
    @endif

    @if($slot->isNotEmpty())
        <div class="flex items-center gap-2">
            {{ $slot }}
        </div>
    @endif

    @if(isset($end))
        <div class="flex items-center gap-2 shrink-0">
            {{ $end }}
        </div>
    @endif
</div>
