@props([
    'icon' => null,
    'color' => null,
    'label' => null,
    'timestamp' => null,
    'datetime' => null,
])

@php
    $resolvedColor = $color ?? 'primary';

    // Un disco con el tinte del color y un filo, no un aro de dos píxeles sobre
    // blanco: es el mismo lenguaje que el badge suave y la tesela del icono de
    // stats. El tinte va en una capa interior porque el disco tiene que ser
    // OPACO —tapa la línea que une los marcadores—.
    $markerBorder = match($resolvedColor) {
        'success' => 'border-kore-success/35',
        'warning' => 'border-kore-warning/45',
        'destructive' => 'border-kore-destructive/35',
        'info' => 'border-kore-info/35',
        'muted' => 'border-kore-border',
        default => 'border-kore-primary/35',
    };

    $markerTint = match($resolvedColor) {
        'success' => 'bg-kore-success/12',
        'warning' => 'bg-kore-warning/15',
        'destructive' => 'bg-kore-destructive/12',
        'info' => 'bg-kore-info/12',
        'muted' => 'bg-kore-muted',
        default => 'bg-kore-primary/12',
    };

    $markerText = match($resolvedColor) {
        'success' => 'text-kore-success-text',
        'warning' => 'text-kore-warning-text',
        'destructive' => 'text-kore-destructive-text',
        'info' => 'text-kore-info-text',
        'muted' => 'text-kore-muted-fg',
        default => 'text-kore-primary-text',
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
    <div class="relative z-10 shrink-0 size-8 rounded-full border {{ $markerBorder }} flex items-center justify-center bg-kore-surface kore-timeline-marker">
        <span class="absolute inset-0 rounded-full {{ $markerTint }}" aria-hidden="true"></span>
        @if($icon)
            <x-dynamic-component :component="'lucide-' . $icon" class="relative size-4 {{ $markerText }}" />
        @else
            <div class="relative size-2 rounded-full {{ $dotColor }}"></div>
        @endif
    </div>

    {{-- Content --}}
    <div class="flex-1 min-w-0 pt-0.5 kore-timeline-content">
        @if($label)
            <p class="text-sm font-semibold text-kore-fg">{{ $label }}</p>
        @endif
        {{-- `<time datetime>` cuando se sabe la fecha exacta: «12 ene» es
             ambiguo para una máquina —y para un lector que quiera anunciarlo
             bien— sin el valor en formato ISO al lado. --}}
        @if($timestamp)
            <p class="text-xs text-kore-muted-fg">
                @if($datetime)
                    <time datetime="{{ $datetime }}">{{ $timestamp }}</time>
                @else
                    {{ $timestamp }}
                @endif
            </p>
        @endif
        @if($slot->isNotEmpty())
            <div class="mt-1 text-sm text-kore-muted-fg">{{ $slot }}</div>
        @endif
    </div>
</li>
