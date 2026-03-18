@props([
    'value' => null,
    'max' => 100,
    'size' => null,
    'color' => null,
    'showValue' => false,
    'indeterminate' => false,
    'striped' => false,
    'animated' => false,
    'label' => null,
])

@php
    $size = $size ?? config('kore-ui.ui.progress.size', 'md');
    $color = $color ?? config('kore-ui.ui.progress.color', 'primary');

    $percent = $indeterminate ? 0 : min(100, max(0, ($value / max($max, 1)) * 100));

    $resolvedColor = $color === 'auto' ? match(true) {
        $percent >= 75 => 'success',
        $percent >= 40 => 'warning',
        default => 'destructive',
    } : $color;

    $barColor = match($resolvedColor) {
        'success' => 'bg-kore-success',
        'warning' => 'bg-kore-warning',
        'destructive' => 'bg-kore-destructive',
        'info' => 'bg-kore-info',
        default => 'bg-kore-primary',
    };

    $trackHeight = match($size) {
        'sm' => 'h-1.5',
        'lg' => 'h-4',
        default => 'h-2.5',
    };
@endphp

<div
    {{ $attributes
        ->except(['value', 'max', 'size', 'color', 'showValue', 'indeterminate', 'striped', 'animated', 'label'])
        ->class(['w-full'])
    }}
>
    {{-- Label row --}}
    @if($label || $showValue)
        <div class="flex items-center justify-between mb-1.5">
            @if($label)
                <span class="text-sm font-medium text-kore-fg">{{ $label }}</span>
            @else
                <span></span>
            @endif
            @if($showValue && !$indeterminate)
                <span class="text-sm text-kore-muted-fg">{{ round($percent) }}%</span>
            @endif
        </div>
    @endif

    {{-- Track --}}
    <div
        class="bg-kore-muted rounded-full overflow-hidden {{ $trackHeight }}"
        role="progressbar"
        @if(!$indeterminate)
            aria-valuenow="{{ round($percent) }}"
            aria-valuemin="0"
            aria-valuemax="100"
        @endif
        @if($label) aria-label="{{ $label }}" @endif
    >
        <div @class([
            'h-full rounded-full transition-all duration-500',
            $barColor,
            'kore-progress-striped' => $striped || $animated,
            'kore-progress-animated' => $animated,
            'kore-progress-indeterminate' => $indeterminate,
        ]) @if(!$indeterminate) style="width: {{ $percent }}%" @endif></div>
    </div>
</div>
