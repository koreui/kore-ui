@php
    $props = $column->getComponentProps();
    $url = $column->getUrl($row);

    $colorClass = match($props['color'] ?? 'primary') {
        'primary' => 'text-kore-primary-text hover:text-kore-primary/80',
        'secondary' => 'text-kore-fg hover:text-kore-muted-fg',
        'success' => 'text-kore-success-text hover:text-kore-success/80',
        'warning' => 'text-kore-warning-text hover:text-kore-warning/80',
        'destructive' => 'text-kore-destructive-text hover:text-kore-destructive/80',
        'info' => 'text-kore-info-text hover:text-kore-info/80',
        default => 'text-kore-primary-text hover:text-kore-primary/80',
    };
@endphp

<a
    href="{{ $url }}"
    @if($props['openInNewTab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
    class="inline-flex items-center gap-1 font-medium kore-transition underline-offset-2 hover:underline {{ $colorClass }}"
>
    @if($props['icon'] ?? null)
        <x-dynamic-component :component="'lucide-' . $props['icon']" class="size-3.5" />
    @endif
    <span>{{ $value }}</span>
    @if($props['openInNewTab'] ?? false)
        <x-lucide-external-link class="size-3" />
    @endif
</a>
