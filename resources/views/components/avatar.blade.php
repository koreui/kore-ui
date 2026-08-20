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

    // El punto de presencia era color y nada más: medido, ni texto ni
    // `aria-label` en ninguno de los cuatro estados, así que «en línea» y
    // «ocupado» se veían idénticos para quien no distingue el verde del rojo.
    $presenceLabel = $presence ? config(
        'kore-ui.ui.translations.presence_'.$presence,
        ucfirst((string) $presence)
    ) : null;

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

    // Color de fondo a partir del nombre.
    //
    // Es el mismo caso que las variantes `soft`, pero al VEINTE por ciento: el
    // color base como texto sobre su propio tinte no llega a AA. Medido antes,
    // las cinco combinaciones fallaban —de 2,67 a 3,52—, así que las iniciales
    // usan el token `-text`. Los tonos están calibrados para pasar también a
    // este porcentaje, no solo al diez.
    $bgColors = [
        'bg-kore-primary/20 text-kore-primary-text',
        'bg-kore-success/20 text-kore-success-text',
        'bg-kore-warning/20 text-kore-warning-text',
        'bg-kore-info/20 text-kore-info-text',
        'bg-kore-destructive/20 text-kore-destructive-text',
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
            loading="lazy"
            decoding="async"
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
            <span class="sr-only">{{ $presenceLabel }}</span>
            @if($presencePulse && $presence === 'online')
                {{-- El pulso es decoración pura: el punto de color sigue ahí sin
                     él, así que con `prefers-reduced-motion` se apaga entero. --}}
                <span class="absolute inset-0 rounded-full {{ $presenceColor }} animate-ping kore-anim-suave opacity-75"></span>
            @endif
        </span>
    @endif
</div>
