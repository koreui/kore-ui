@props([
    'status' => null,
    'title' => null,
    'description' => null,
    'icon' => null,
])

@php
    $status = $status ?? config('kore-ui.ui.result.status', 'info');

    [$statusIcon, $color] = match((string) $status) {
        'success' => ['circle-check', 'success'],
        'warning' => ['triangle-alert', 'warning'],
        'error' => ['circle-x', 'destructive'],
        '404' => ['search-x', 'muted'],
        '403' => ['lock', 'warning'],
        '500' => ['server-crash', 'destructive'],
        default => ['info', 'info'],
    };

    $statusIcon = $icon ?? $statusIcon;

    $iconColorClass = match($color) {
        'warning' => 'text-kore-warning-text',
        'muted' => 'text-kore-muted-fg',
        default => 'text-kore-' . $color,
    };

    $circleBgClass = match($color) {
        'muted' => 'bg-kore-muted',
        default => 'bg-kore-' . $color . '/10',
    };
@endphp

<div {{ $attributes->except(['status', 'title', 'description', 'icon'])->class(['flex flex-col items-center justify-center text-center py-12 px-6']) }}>
    <div class="size-16 rounded-full {{ $circleBgClass }} flex items-center justify-center mb-4">
        <x-dynamic-component :component="'lucide-' . $statusIcon" class="size-8 {{ $iconColorClass }}" />
    </div>

    @if($title)
        <h3 class="text-lg font-semibold text-kore-fg">{{ $title }}</h3>
    @endif

    @if($description)
        <p class="mt-1 text-sm text-kore-muted-fg max-w-md">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif

    @if(isset($action))
        <div class="mt-6 flex items-center justify-center gap-3">{{ $action }}</div>
    @endif
</div>
