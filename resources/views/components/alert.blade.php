@props([
    'title' => null,
    'description' => null,
    'type' => 'info',
    'icon' => null,
    'variant' => null,
    'closeable' => false,
    'timeout' => null,
    'showIcon' => true,
])

@php
    $variant = $variant ?? config('kore-ui.ui.alert.variant', 'soft');

    $resolvedIcon = $icon ?? match($type) {
        'success' => 'check-circle',
        'warning' => 'alert-triangle',
        'destructive' => 'x-circle',
        default => 'info',
    };

    $colorClasses = match($variant) {
        'solid' => match($type) {
            'success' => 'bg-kore-success text-kore-success-fg border border-kore-success',
            'warning' => 'bg-kore-warning text-kore-warning-fg border border-kore-warning',
            'destructive' => 'bg-kore-destructive text-kore-destructive-fg border border-kore-destructive',
            default => 'bg-kore-info text-kore-info-fg border border-kore-info',
        },
        'outline' => match($type) {
            'success' => 'border border-kore-success text-kore-success bg-transparent',
            'warning' => 'border border-kore-warning text-kore-warning bg-transparent',
            'destructive' => 'border border-kore-destructive text-kore-destructive bg-transparent',
            default => 'border border-kore-info text-kore-info bg-transparent',
        },
        default => match($type) {
            'success' => 'bg-kore-success/10 text-kore-success border border-kore-success/20',
            'warning' => 'bg-kore-warning/10 text-kore-warning border border-kore-warning/20',
            'destructive' => 'bg-kore-destructive/10 text-kore-destructive border border-kore-destructive/20',
            default => 'bg-kore-info/10 text-kore-info border border-kore-info/20',
        },
    };

    $needsAlpine = $closeable || $timeout;
@endphp

<div
    {{ $attributes
        ->except(['title', 'description', 'type', 'icon', 'variant', 'closeable', 'timeout', 'showIcon'])
        ->class([
            'relative rounded-kore-md px-4 py-3',
            $colorClasses,
        ])
    }}
    role="alert"
    @if($needsAlpine)
        x-data="{ show: true }"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        @if($timeout)
            x-init="setTimeout(() => show = false, {{ $timeout * 1000 }})"
        @endif
    @endif
>
    <div class="flex gap-3">
        @if($showIcon)
            <div class="shrink-0 mt-0.5">
                @if(isset($iconSlot))
                    {{ $iconSlot }}
                @else
                    <x-dynamic-component :component="'lucide-' . $resolvedIcon" class="size-5" />
                @endif
            </div>
        @endif

        <div class="flex-1 min-w-0">
            @if($title)
                <h5 class="font-medium text-sm">{{ $title }}</h5>
            @endif

            @if($description)
                <p class="text-sm {{ $title ? 'mt-1' : '' }} opacity-90">{{ $description }}</p>
            @endif

            @if($slot->isNotEmpty())
                <div class="text-sm {{ $title || $description ? 'mt-1' : '' }} opacity-90">{{ $slot }}</div>
            @endif

            @if(isset($action))
                <div class="mt-3">{{ $action }}</div>
            @endif
        </div>

        @if($closeable)
            <button
                type="button"
                class="shrink-0 rounded-kore-md p-0.5 opacity-70 hover:opacity-100 transition-opacity"
                x-on:click="show = false"
                aria-label="Cerrar"
            >
                <x-lucide-x class="size-4" />
            </button>
        @endif
    </div>
</div>
