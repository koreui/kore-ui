@props([
    'sticky' => null,
    'bordered' => null,
    'toggle' => true,
    'toggleFor' => 'main',
    'start' => null,
    'end' => null,
])

@php
    $config = config('kore-ui.shell.navbar', []);

    $sticky = $sticky ?? ($config['sticky'] ?? true);
    $bordered = $bordered ?? ($config['bordered'] ?? true);
@endphp

{{-- La barra superior del shell.

     Compone el <x-kore::toolbar> que ya existe (zonas start / centro / end) en lugar
     de reimplementar el flex de tres columnas. Le pasa role={{ null }}: role="toolbar"
     promete un widget que se recorre con las flechas, y esto es una cabecera. --}}
<header
    class="kore-navbar"
    data-sticky="{{ $sticky ? 'true' : 'false' }}"
    @class(['border-b border-kore-border' => $bordered])
>
    <x-kore::toolbar
        :role="false"
        class="w-full gap-3 px-4"
        {{ $attributes }}
    >
        <x-slot:start>
            @if($toggle)
                <x-kore::sidebar.toggle :for="$toggleFor" />
            @endif

            {{ $start }}
        </x-slot:start>

        {{ $slot }}

        <x-slot:end>
            {{ $end }}
        </x-slot:end>
    </x-kore::toolbar>
</header>
