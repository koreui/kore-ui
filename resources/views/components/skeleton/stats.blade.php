{{-- La silueta de una <x-kore::stats>, con las dos variantes del componente. --}}
@props([
    'variant' => null,
    'icon' => true,
    'trend' => true,
    'bordered' => true,
    'shadow' => false,
    'padding' => true,
])

@php
    $variant = $variant ?? config('kore-ui.ui.stats.variant', 'default');
    $compacta = $variant === 'compact';
@endphp

<div
    {{ $attributes->class([
        'block rounded-kore-lg bg-kore-surface',
        'border border-kore-border' => $bordered,
        'shadow-sm' => $shadow,
        'p-4' => $padding && $compacta,
        'p-6' => $padding && ! $compacta,
    ]) }}
    role="status"
    aria-busy="true"
>
    <span class="sr-only">{{ config('kore-ui.ui.translations.loading', 'Cargando') }}</span>

    @if($compacta)
        <div class="flex items-center gap-3">
            @if($icon)
                <x-kore::skeleton shape="circle" size="2.5rem" rounded="rounded-kore-md" class="shrink-0" />
            @endif
            <div class="flex-1 min-w-0 space-y-2">
                <x-kore::skeleton shape="text" height="0.75rem" width="45%" />
                <x-kore::skeleton shape="text" height="1.25rem" width="30%" />
            </div>
            @if($trend)
                <x-kore::skeleton width="2.5rem" height="0.875rem" class="shrink-0" />
            @endif
        </div>
    @else
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0 space-y-2">
                <x-kore::skeleton shape="text" height="0.875rem" width="40%" />
                <x-kore::skeleton shape="text" height="1.875rem" width="55%" />
            </div>
            @if($icon)
                <x-kore::skeleton shape="circle" size="3rem" rounded="rounded-kore-lg" class="ml-4 shrink-0" />
            @endif
        </div>

        @if($trend)
            <div class="mt-2">
                <x-kore::skeleton width="3.5rem" height="0.875rem" />
            </div>
        @endif
    @endif
</div>
