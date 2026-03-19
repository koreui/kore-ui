@php
    $props = $column->getComponentProps();
    $url = $column->getUrl($row);

    $colorClass = match($props['color'] ?? 'primary') {
        'primary' => 'text-kore-primary hover:text-kore-primary/80',
        'secondary' => 'text-kore-fg hover:text-kore-muted-fg',
        'success' => 'text-kore-success hover:text-kore-success/80',
        'warning' => 'text-kore-warning hover:text-kore-warning/80',
        'destructive' => 'text-kore-destructive hover:text-kore-destructive/80',
        'info' => 'text-kore-info hover:text-kore-info/80',
        default => 'text-kore-primary hover:text-kore-primary/80',
    };
@endphp

<a
    href="{{ $url }}"
    @if($props['openInNewTab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
    class="inline-flex items-center gap-1 font-medium transition-colors underline-offset-2 hover:underline {{ $colorClass }}"
>
    @if($props['icon'] ?? null)
        <x-dynamic-component :component="'lucide-' . $props['icon']" class="size-3.5" />
    @endif
    <span>{{ $value }}</span>
    @if($props['openInNewTab'] ?? false)
        <x-lucide-external-link class="size-3" />
    @endif
</a>
