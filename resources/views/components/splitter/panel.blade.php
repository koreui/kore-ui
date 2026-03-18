@props([
    'size' => null,
    'minSize' => 0,
    'maxSize' => 100,
])

<div {{ $attributes->class(['overflow-auto']) }}
     data-splitter-panel
     @if($size) data-panel-size="{{ $size }}" @endif
     data-panel-min="{{ $minSize }}"
     data-panel-max="{{ $maxSize }}">
    {{ $slot }}
</div>
