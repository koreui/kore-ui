@props([
    'title' => null,
    'description' => null,
    'type' => 'info',
    'icon' => null,
    'variant' => null,
    'closeable' => false,
    'timeout' => null,
    'showIcon' => true,
    'live' => null,
])

@php
    $variant = $variant ?? config('kore-ui.ui.alert.variant', 'soft');

    $resolvedIcon = $icon ?? match($type) {
        'success' => 'check-circle',
        'warning' => 'alert-triangle',
        'destructive' => 'x-circle',
        default => 'info',
    };

    // Las variantes que pintan el color COMO TEXTO —soft, outline y ghost— usan
    // el token `-text`, no el color base. El base está pensado para ser un
    // FONDO: sobre su propio tinte al diez por ciento se queda muy por debajo de
    // AA. Medido en un navegador, antes: success 3,01 · info 3,24 ·
    // destructive 3,91 · primary 4,08. Ver la nota de `--kore-warning-text` en
    // kore-theme.css, que ya lo resolvía para un color de cinco.
    $colorClasses = match($variant) {
        'solid' => match($type) {
            'success' => 'bg-kore-success text-kore-success-fg border border-kore-success',
            'warning' => 'bg-kore-warning text-kore-warning-fg border border-kore-warning',
            'destructive' => 'bg-kore-destructive text-kore-destructive-fg border border-kore-destructive',
            default => 'bg-kore-info text-kore-info-fg border border-kore-info',
        },
        'outline' => match($type) {
            'success' => 'border border-kore-success text-kore-success-text bg-transparent',
            'warning' => 'border border-kore-warning text-kore-warning-text bg-transparent',
            'destructive' => 'border border-kore-destructive text-kore-destructive-text bg-transparent',
            default => 'border border-kore-info text-kore-info-text bg-transparent',
        },
        default => match($type) {
            'success' => 'bg-kore-success/10 text-kore-success-text border border-kore-success/20',
            'warning' => 'bg-kore-warning/10 text-kore-warning-text border border-kore-warning/20',
            'destructive' => 'bg-kore-destructive/10 text-kore-destructive-text border border-kore-destructive/20',
            default => 'bg-kore-info/10 text-kore-info-text border border-kore-info/20',
        },
    };

    $needsAlpine = $closeable || $timeout;

    // `role="alert"` es una región ASSERTIVE: interrumpe al lector para leerla.
    // Eso está bien para un aviso que aparece de pronto, y muy mal para uno que
    // ya estaba en la página al cargar. Medido: doce alertas estáticas en una
    // página, las doce con el rol, todas anunciándose de golpe al abrirla.
    //
    // Se pone solo cuando la alerta es dinámica de verdad —la que trae un
    // `timeout` está pensada para aparecer y marcharse—, y el consumidor puede
    // forzarlo con `live` en cualquier sentido.
    $live = $live ?? ($timeout ? 'assertive' : 'off');
    $role = match($live) {
        'assertive' => 'alert',
        'polite' => 'status',
        default => null,
    };
@endphp

<div
    {{ $attributes
        ->except(['title', 'description', 'type', 'icon', 'variant', 'closeable', 'timeout', 'showIcon', 'live'])
        ->class([
            'relative rounded-kore-md px-4 py-3',
            $colorClasses,
        ])
    }}
    @if($role) role="{{ $role }}" @endif
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

            {{-- Sin `opacity-90`: la descripción ya se distingue del título por
                 el peso de la letra, y bajar la opacidad de un texto que en
                 varias combinaciones estaba justo en el límite lo tiraba por
                 debajo de AA. Medido: la descripción fallaba en once de doce
                 combinaciones y el título en ocho. --}}
            @if($description)
                <p class="text-sm {{ $title ? 'mt-1' : '' }}">{{ $description }}</p>
            @endif

            @if($slot->isNotEmpty())
                <div class="text-sm {{ $title || $description ? 'mt-1' : '' }}">{{ $slot }}</div>
            @endif

            @if(isset($action))
                <div class="mt-3">{{ $action }}</div>
            @endif
        </div>

        @if($closeable)
            {{-- `size-6` con el icono centrado, no `p-0.5` a secas: medido, la
                 caja salía de 20 px de ancho y WCAG 2.2 pide 24×24. Y sin
                 `opacity-70`, que hundía el contraste de un icono que ya iba
                 justo en varias combinaciones. --}}
            <button
                type="button"
                class="shrink-0 inline-flex size-6 items-center justify-center rounded-kore-md hover:bg-black/10 dark:hover:bg-white/10 transition-colors"
                x-on:click="show = false"
                aria-label="{{ config('kore-ui.ui.translations.close', 'Cerrar') }}"
            >
                <x-lucide-x class="size-4" />
            </button>
        @endif
    </div>
</div>
