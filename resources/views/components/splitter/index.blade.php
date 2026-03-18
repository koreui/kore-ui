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
     })"
     role="group">
    {{ $slot }}
</div>
