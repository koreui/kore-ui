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
    'bordered' => null,
    'shadow' => null,
    'padding' => null,
    'skeleton' => false,
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
        'success' => 'bg-kore-success/10 text-kore-success-text',
        'warning' => 'bg-kore-warning/10 text-kore-warning-text',
        'destructive' => 'bg-kore-destructive/10 text-kore-destructive-text',
        'info' => 'bg-kore-info/10 text-kore-info-text',
        default => 'bg-kore-primary/10 text-kore-primary-text',
    };

    $trendColor = match($trendDirection) {
        'up' => 'text-kore-success',
        'down' => 'text-kore-destructive',
        default => 'text-kore-muted-fg',
    };

    $tag = $href ? 'a' : 'div';

    // El marco lo pintaba fijo: borde sí o sí y ninguna sombra, sin forma de
    // decir otra cosa ni desde la etiqueta ni desde la configuración.
    $bordered = \KoreUi\Core\Support\Look::resolver('stats', 'bordered', $bordered, true);
    $shadow = \KoreUi\Core\Support\Look::resolver('stats', 'shadow', $shadow, false);
    $padding = \KoreUi\Core\Support\Look::resolver('stats', 'padding', $padding, true);

    $marcoClases = array_filter([
        'block rounded-kore-lg bg-kore-surface',
        $bordered ? 'border border-kore-border' : null,
        $shadow ? 'shadow-sm' : null,
    ]);

    // La silueta mientras no hay cifra. Vale `skeleton` a secas.
    $siluetaActiva = $skeleton !== false && $skeleton !== null;
@endphp

@if($siluetaActiva)
    <x-kore::skeleton.stats
        :variant="$variant"
        :icon="(bool) $icon"
        :trend="$trend !== 'none'"
        :bordered="$bordered"
        :shadow="$shadow"
        :padding="$padding"
        {{ $attributes->except(['skeleton']) }}
    />
@else

@if($variant === 'compact')
    <{{ $tag }} @if($href) href="{{ $href }}" @endif
        {{ $attributes->except(['bordered', 'shadow', 'padding'])->class([
            ...$marcoClases,
            'p-4' => $padding,
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
        {{ $attributes->except(['bordered', 'shadow', 'padding'])->class([
            ...$marcoClases,
            'p-6' => $padding,
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
@endif
