@props([
    'orientation' => null,
    'gutterSize' => null,
    'stateKey' => null,
])

@php
    $orientation = $orientation ?? config('kore-ui.ui.splitter.orientation', 'horizontal');
    $gutterSize = (int) ($gutterSize ?? config('kore-ui.ui.splitter.gutter_size', 8));

    $flexDirection = $orientation === 'vertical' ? 'flex-col' : 'flex-row';
@endphp

<div {{ $attributes->class(['flex w-full overflow-hidden', $flexDirection]) }}
     x-data="KoreSplitter({
        orientation: '{{ $orientation }}',
        gutterSize: {{ $gutterSize }},
        stateKey: @js($stateKey),
        {{-- La barra se crea desde JavaScript, así que su nombre accesible tiene
             que viajar hasta ahí: no hay etiqueta en Blade donde ponerlo. --}}
        resizeLabel: @js(config('kore-ui.ui.translations.resize', 'Redimensionar paneles')),
     })"
     role="group">
    {{ $slot }}
</div>
