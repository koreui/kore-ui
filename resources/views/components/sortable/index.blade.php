@props([
    'handler' => null,
    'mode' => null,
    'group' => null,
    'handle' => null,
    'tag' => 'div',
    'animation' => null,
])

@php
    $mode = $mode ?? config('kore-ui.ui.sortable.mode', 'server');
    $animation = $animation ?? config('kore-ui.ui.sortable.animation', 150);

    $isServer = $mode === 'server';
    $p = $isServer ? 'wire:sort' : 'x-sort';

    // Build the sort directives. In server mode a handler is required to do anything;
    // in client mode a bare `x-sort` reorders the DOM visually.
    $directives = [];
    if ($handler) {
        $directives[] = $p . '="' . $handler . '"';
    } elseif (! $isServer) {
        $directives[] = 'x-sort';
    }

    if (count($directives) > 0) {
        if ($group) {
            $directives[] = $p . ':group="' . $group . '"';
        }
        $directives[] = $p . ':config="{ animation: ' . (int) $animation . ' }"';
    }

    $directiveString = implode(' ', $directives);
@endphp

<{{ $tag }}
    {!! $directiveString !!}
    {{ $attributes->except(['handler', 'mode', 'group', 'handle', 'tag', 'animation'])->class(['space-y-2']) }}
>
    {{ $slot }}
</{{ $tag }}>
