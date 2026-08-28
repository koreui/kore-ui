{{-- La silueta de un <x-kore::chart>: el marco, el eje y unas barras de altura
     desigual. Barras iguales no se parecen a ningún gráfico, y el hueco que deja
     el eje es justo el que ocupará después. --}}
@props([
    'bars' => 7,
    'height' => '16rem',
    'axis' => true,
    'legend' => false,
    'bordered' => true,
])

@php
    $bars = max(1, (int) $bars);

    // Alturas fijas, no aleatorias: una silueta que cambia en cada repintado
    // parpadea, y con Livewire se repinta más de lo que uno cree.
    $alturas = [55, 80, 40, 95, 65, 30, 75, 50, 88, 45, 70, 35];
@endphp

<div
    {{ $attributes->class([
        'rounded-kore-lg bg-kore-surface p-4',
        'border border-kore-border' => $bordered,
    ]) }}
    role="status"
    aria-busy="true"
>
    <span class="sr-only">{{ config('kore-ui.ui.translations.loading', 'Cargando') }}</span>

    @if($legend)
        <div class="mb-3 flex items-center gap-4">
            @for($i = 0; $i < 3; $i++)
                <div class="flex items-center gap-1.5">
                    <x-kore::skeleton shape="circle" size="0.625rem" />
                    <x-kore::skeleton shape="text" height="0.75rem" width="3rem" />
                </div>
            @endfor
        </div>
    @endif

    <div class="flex gap-3" style="height: {{ $height }}">
        @if($axis)
            <div class="flex flex-col justify-between py-1 shrink-0">
                @for($i = 0; $i < 5; $i++)
                    <x-kore::skeleton shape="text" height="0.625rem" width="1.75rem" />
                @endfor
            </div>
        @endif

        <div class="flex-1 min-w-0 flex items-end gap-2">
            @for($i = 0; $i < $bars; $i++)
                <x-kore::skeleton
                    class="flex-1"
                    rounded="rounded-t-kore-sm"
                    :height="$alturas[$i % count($alturas)] . '%'"
                />
            @endfor
        </div>
    </div>

    @if($axis)
        {{-- El hueco del eje: 1.75rem de las etiquetas + el gap de 0.75rem. --}}
        <div class="mt-2 flex gap-2 pl-10">
            @for($i = 0; $i < $bars; $i++)
                <x-kore::skeleton shape="text" height="0.625rem" class="flex-1" />
            @endfor
        </div>
    @endif
</div>
