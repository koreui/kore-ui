@props([
    'src' => null,
    'name' => null,
    'icon' => 'user',
    'size' => 'md',
    'shape' => null,
    'presence' => null,
    'presencePulse' => false,
])

@php
    $shape = $shape ?? config('kore-ui.ui.avatar.shape', 'circle');

    $sizeClasses = match($size) {
        'xs' => 'size-6 text-[10px]',
        'sm' => 'size-8 text-xs',
        'lg' => 'size-12 text-lg',
        'xl' => 'size-16 text-xl',
        default => 'size-10 text-sm',
    };

    $iconSize = match($size) {
        'xs' => 'size-3',
        'sm' => 'size-4',
        'lg' => 'size-6',
        'xl' => 'size-8',
        default => 'size-5',
    };

    $shapeClasses = match($shape) {
        'square' => 'rounded-kore-md',
        default => 'rounded-full',
    };

    $presenceColor = match($presence) {
        'online' => 'bg-kore-success',
        'offline' => 'bg-kore-muted-fg',
        'busy' => 'bg-kore-destructive',
        'away' => 'bg-kore-warning',
        default => '',
    };

    $presenceSize = match($size) {
        'xs' => 'size-1.5',
        'sm' => 'size-2',
        'lg' => 'size-3.5',
        'xl' => 'size-4',
        default => 'size-3',
    };

    // Auto-iniciales
    $initials = null;
    if ($name && !$src) {
        $words = explode(' ', trim($name));
        $initials = strtoupper(mb_substr($words[0], 0, 1));
        if (count($words) > 1) {
            $initials .= strtoupper(mb_substr(end($words), 0, 1));
        }
    }

    // Hash-based background color for name
    $bgColors = [
        'bg-kore-primary/20 text-kore-primary',
        'bg-kore-success/20 text-kore-success',
        'bg-kore-warning/20 text-kore-warning',
        'bg-kore-info/20 text-kore-info',
        'bg-kore-destructive/20 text-kore-destructive',
    ];
    $bgClass = $name ? $bgColors[abs(crc32($name)) % count($bgColors)] : 'bg-kore-muted text-kore-muted-fg';
@endphp

<div {{ $attributes
    ->except(['src', 'name', 'icon', 'size', 'shape', 'presence', 'presencePulse'])
    ->class(['relative inline-flex shrink-0', $sizeClasses])
}}>
    @if($src)
        <img
            src="{{ $src }}"
            @if($name) alt="{{ $name }}" @else alt="" @endif
            class="w-full h-full object-cover {{ $shapeClasses }}"
        />
    @elseif($initials)
        <div class="w-full h-full flex items-center justify-center font-medium {{ $shapeClasses }} {{ $bgClass }}">
            {{ $initials }}
        </div>
    @else
        <div class="w-full h-full flex items-center justify-center {{ $shapeClasses }} {{ $bgClass }}">
            <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSize }}" />
        </div>
    @endif

    @if($presence)
        <span class="absolute bottom-0 right-0 block {{ $presenceSize }} {{ $presenceColor }} {{ $shape === 'square' ? 'rounded-sm' : 'rounded-full' }} ring-2 ring-kore-surface">
            @if($presencePulse && $presence === 'online')
                <span class="absolute inset-0 rounded-full {{ $presenceColor }} animate-ping opacity-75"></span>
            @endif
        </span>
    @endif
</div>
