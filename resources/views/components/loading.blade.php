@props([
    'type' => null,
    'size' => 'md',
    'text' => null,
    'overlay' => false,
    'blur' => false,
])

@php
    $type = $type ?? config('kore-ui.ui.loading.type', 'spinner');

    $spinnerSize = match($size) {
        'sm' => 'size-4',
        'lg' => 'size-8',
        default => 'size-6',
    };

    $dotSize = match($size) {
        'sm' => 'size-1.5',
        'lg' => 'size-3',
        default => 'size-2',
    };

    $textSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };
@endphp

@if($overlay)
<div {{ $attributes
    ->except(['type', 'size', 'text', 'overlay', 'blur'])
    ->class([
        'absolute inset-0 flex flex-col items-center justify-center z-10 bg-kore-surface/80',
        'backdrop-blur-sm' => $blur,
    ])
}}>
@else
<div {{ $attributes
    ->except(['type', 'size', 'text', 'overlay', 'blur'])
    ->class('inline-flex flex-col items-center justify-center gap-2')
}}>
@endif

    @if($type === 'dots')
        <div class="flex items-center gap-1">
            <span class="{{ $dotSize }} rounded-full bg-current animate-bounce" style="animation-delay: 0ms"></span>
            <span class="{{ $dotSize }} rounded-full bg-current animate-bounce" style="animation-delay: 150ms"></span>
            <span class="{{ $dotSize }} rounded-full bg-current animate-bounce" style="animation-delay: 300ms"></span>
        </div>
    @elseif($type === 'pulse')
        <span class="{{ $spinnerSize }} rounded-full bg-current animate-pulse opacity-50"></span>
    @else
        {{-- spinner (default) --}}
        <svg class="animate-spin {{ $spinnerSize }} text-kore-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif

    @if($text)
        <span class="{{ $textSize }} text-kore-muted-fg">{{ $text }}</span>
    @endif

</div>
