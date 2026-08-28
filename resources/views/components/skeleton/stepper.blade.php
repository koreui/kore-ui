{{-- La silueta de un <x-kore::stepper>: los círculos, sus conectores y el
     panel del paso activo. --}}
@props([
    'steps' => 3,
    'variant' => null,
    'panel' => true,
    'lines' => 3,
])

@php
    $variant = $variant ?? config('kore-ui.ui.stepper.variant', 'horizontal');
    $steps = max(1, (int) $steps);
    $lines = max(1, (int) $lines);
    $vertical = $variant === 'vertical';
@endphp

<div {{ $attributes->class(['w-full']) }} role="status" aria-busy="true">
    <span class="sr-only">{{ config('kore-ui.ui.translations.loading', 'Cargando') }}</span>

    @if($vertical)
        <div class="mb-4 space-y-1">
            @for($i = 0; $i < $steps; $i++)
                <div class="flex items-stretch">
                    <div class="flex flex-col items-center shrink-0">
                        <x-kore::skeleton shape="circle" size="2rem" />
                        @if($i < $steps - 1)
                            <x-kore::skeleton width="2px" height="2rem" class="my-1" />
                        @endif
                    </div>
                    <div class="ml-3 pt-1.5 flex-1 min-w-0">
                        <x-kore::skeleton shape="text" height="0.875rem" width="35%" />
                    </div>
                </div>
            @endfor
        </div>
    @else
        {{-- La misma estructura que el stepper de verdad: cada paso es `flex-1`
             en columna, con la etiqueta DEBAJO del círculo y los conectores
             repartiéndose lo que sobra. En fila y con anchos fijos, cuatro pasos
             ya no caben en un móvil. --}}
        <div class="mb-4 flex">
            @for($i = 0; $i < $steps; $i++)
                <div class="flex-1 flex flex-col items-center min-w-0">
                    <div class="flex items-center w-full">
                        <div class="flex-1 h-px {{ $i > 0 ? 'bg-kore-border' : '' }}"></div>
                        <x-kore::skeleton shape="circle" size="2rem" class="shrink-0 mx-2" />
                        <div class="flex-1 h-px {{ $i < $steps - 1 ? 'bg-kore-border' : '' }}"></div>
                    </div>
                    <x-kore::skeleton shape="text" height="0.75rem" width="70%" class="mt-2" />
                </div>
            @endfor
        </div>
    @endif

    @if($panel)
        <div class="rounded-kore-lg border border-kore-border bg-kore-surface p-4 space-y-3">
            @for($i = 0; $i < $lines; $i++)
                <x-kore::skeleton shape="text" height="0.875rem" :width="$i === $lines - 1 && $lines > 1 ? '60%' : '100%'" />
            @endfor
        </div>
    @endif
</div>
