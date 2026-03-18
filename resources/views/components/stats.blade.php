@props([
    'label' => null,
    'value' => null,
    'previousValue' => null,
    'icon' => null,
    'href' => null,
    'trend' => 'auto',
    'animated' => null,
    'variant' => null,
    'color' => 'primary',
])

@php
    $variant = $variant ?? config('kore-ui.ui.stats.variant', 'default');
    $animated = $animated ?? config('kore-ui.ui.stats.animated', true);

    $trendValue = null;
    $trendDirection = $trend;
    if ($trend === 'auto' && $previousValue !== null && $previousValue != 0) {
        $trendValue = round((($value - $previousValue) / abs($previousValue)) * 100, 1);
        $trendDirection = $trendValue >= 0 ? 'up' : 'down';
    }

    $iconBgColor = match($color) {
        'success' => 'bg-kore-success/10 text-kore-success',
        'warning' => 'bg-kore-warning/10 text-kore-warning',
        'destructive' => 'bg-kore-destructive/10 text-kore-destructive',
        'info' => 'bg-kore-info/10 text-kore-info',
        default => 'bg-kore-primary/10 text-kore-primary',
    };

    $trendColor = match($trendDirection) {
        'up' => 'text-kore-success',
        'down' => 'text-kore-destructive',
        default => 'text-kore-muted-fg',
    };

    $tag = $href ? 'a' : 'div';
@endphp

@if($variant === 'compact')
    <{{ $tag }} @if($href) href="{{ $href }}" @endif
        {{ $attributes->class([
            'block rounded-kore-lg border border-kore-border bg-kore-surface p-4',
            'hover:shadow-md transition-shadow' => $href,
        ]) }}>
        <div class="flex items-center gap-3">
            @if($icon)
                <div class="size-10 rounded-kore-md {{ $iconBgColor }} flex items-center justify-center shrink-0">
                    <x-dynamic-component :component="'lucide-' . $icon" class="size-5" />
                </div>
            @endif
            <div class="flex-1 min-w-0">
                @if($label)
                    <p class="text-xs font-medium text-kore-muted-fg truncate">{{ $label }}</p>
                @endif
                <p class="text-xl font-bold text-kore-fg"
                   @if($animated)
                       x-data="KoreStats({ value: @js($value), animated: true })"
                       x-text="displayValue"
                   @endif
                >{{ $value }}</p>
            </div>
            @if($trendDirection !== 'none' && $trendValue !== null)
                <div class="flex items-center gap-0.5 {{ $trendColor }} shrink-0">
                    @if($trendDirection === 'up')
                        <x-lucide-trending-up class="size-4" />
                    @else
                        <x-lucide-trending-down class="size-4" />
                    @endif
                    <span class="text-xs font-medium">{{ abs($trendValue) }}%</span>
                </div>
            @endif
        </div>
    </{{ $tag }}>
@else
    <{{ $tag }} @if($href) href="{{ $href }}" @endif
        {{ $attributes->class([
            'block rounded-kore-lg border border-kore-border bg-kore-surface p-6',
            'hover:shadow-md transition-shadow' => $href,
        ]) }}>
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                @if($label)
                    <p class="text-sm font-medium text-kore-muted-fg">{{ $label }}</p>
                @endif
                <p class="mt-1 text-3xl font-bold text-kore-fg"
                   @if($animated)
                       x-data="KoreStats({ value: @js($value), animated: true })"
                       x-text="displayValue"
                   @endif
                >{{ $value }}</p>
            </div>
            @if($icon)
                <div class="size-12 rounded-kore-lg {{ $iconBgColor }} flex items-center justify-center ml-4">
                    <x-dynamic-component :component="'lucide-' . $icon" class="size-6" />
                </div>
            @endif
        </div>

        @if($trendDirection !== 'none' && $trendValue !== null)
            <div class="mt-2 flex items-center gap-1 {{ $trendColor }}">
                @if($trendDirection === 'up')
                    <x-lucide-trending-up class="size-4" />
                @else
                    <x-lucide-trending-down class="size-4" />
                @endif
                <span class="text-sm font-medium">{{ abs($trendValue) }}%</span>
            </div>
        @endif

        @if($slot->isNotEmpty())
            <div class="mt-2 text-sm text-kore-muted-fg">{{ $slot }}</div>
        @endif
    </{{ $tag }}>
@endif
