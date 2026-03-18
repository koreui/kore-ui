@props([
    'value' => 0,
    'size' => null,
    'color' => null,
    'showValue' => true,
    'strokeWidth' => 8,
])

@php
    $size = $size ?? config('kore-ui.ui.progress.size', 'md');
    $color = $color ?? config('kore-ui.ui.progress.color', 'primary');

    $percent = min(100, max(0, $value));

    $resolvedColor = $color === 'auto' ? match(true) {
        $percent >= 75 => 'success',
        $percent >= 40 => 'warning',
        default => 'destructive',
    } : $color;

    $strokeColor = match($resolvedColor) {
        'success' => 'stroke-kore-success',
        'warning' => 'stroke-kore-warning',
        'destructive' => 'stroke-kore-destructive',
        'info' => 'stroke-kore-info',
        default => 'stroke-kore-primary',
    };

    $svgSize = match($size) {
        'sm' => 'size-16',
        'lg' => 'size-28',
        'xl' => 'size-36',
        default => 'size-20',
    };

    $textSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-lg',
        'xl' => 'text-2xl',
        default => 'text-sm',
    };

    $radius = (100 - $strokeWidth) / 2;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference - ($percent / 100) * $circumference;
@endphp

<div
    {{ $attributes
        ->except(['value', 'size', 'color', 'showValue', 'strokeWidth'])
        ->class(['relative inline-flex items-center justify-center', $svgSize])
    }}
    role="progressbar"
    aria-valuenow="{{ round($percent) }}"
    aria-valuemin="0"
    aria-valuemax="100"
>
    <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
        {{-- Track --}}
        <circle
            class="stroke-kore-muted fill-none"
            cx="50" cy="50"
            r="{{ $radius }}"
            stroke-width="{{ $strokeWidth }}"
        />
        {{-- Progress --}}
        <circle
            class="{{ $strokeColor }} fill-none transition-all duration-500"
            cx="50" cy="50"
            r="{{ $radius }}"
            stroke-width="{{ $strokeWidth }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}"
        />
    </svg>

    @if($showValue)
        <span class="absolute inset-0 flex items-center justify-center font-semibold text-kore-fg {{ $textSize }}">
            {{ round($percent) }}%
        </span>
    @endif
</div>
