@props([
    'icon' => null,
    'color' => null,
    'label' => null,
    'timestamp' => null,
])

@php
    $resolvedColor = $color ?? 'primary';

    $markerBorder = match($resolvedColor) {
        'success' => 'border-kore-success',
        'warning' => 'border-kore-warning',
        'destructive' => 'border-kore-destructive',
        'info' => 'border-kore-info',
        'muted' => 'border-kore-border',
        default => 'border-kore-primary',
    };

    $markerText = match($resolvedColor) {
        'success' => 'text-kore-success',
        'warning' => 'text-kore-warning',
        'destructive' => 'text-kore-destructive',
        'info' => 'text-kore-info',
        'muted' => 'text-kore-muted-fg',
        default => 'text-kore-primary',
    };

    $dotColor = match($resolvedColor) {
        'success' => 'bg-kore-success',
        'warning' => 'bg-kore-warning',
        'destructive' => 'bg-kore-destructive',
        'info' => 'bg-kore-info',
        'muted' => 'bg-kore-muted-fg',
        default => 'bg-kore-primary',
    };
@endphp

<li {{ $attributes->class(['relative flex gap-4 pb-8 last:pb-0 kore-timeline-item']) }}>
    {{-- Spacer (hidden by default, visible in alternate mode) --}}
    <div class="hidden flex-1 min-w-0 kore-timeline-spacer" aria-hidden="true"></div>

    {{-- Connector line --}}
    <div class="absolute left-[15px] top-8 bottom-0 w-px bg-kore-border kore-timeline-connector"
         aria-hidden="true"></div>

    {{-- Marker --}}
    <div class="relative z-10 shrink-0 size-8 rounded-full border-2 {{ $markerBorder }} flex items-center justify-center bg-kore-surface kore-timeline-marker">
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="size-4 {{ $markerText }}" />
        @else
            <div class="size-2.5 rounded-full {{ $dotColor }}"></div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0 pt-0.5 kore-timeline-content">
        @if($label)
            <p class="text-sm font-semibold text-kore-fg">{{ $label }}</p>
        @endif
        @if($timestamp)
            <p class="text-xs text-kore-muted-fg">{{ $timestamp }}</p>
        @endif
        @if($slot->isNotEmpty())
            <div class="mt-1 text-sm text-kore-muted-fg">{{ $slot }}</div>
        @endif
    </div>
</li>
