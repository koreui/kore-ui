@props([
    'ariaLabel' => null,
])

@php
    // Tres botones sueltos y un grupo de tres botones no son lo mismo para un
    // lector: sin `role="group"` no hay nada que diga que van juntos ni dónde
    // acaba el conjunto. El nombre lo pone el consumidor, que es quien sabe de
    // qué es el grupo.
    $ariaLabel = $ariaLabel ?? config('kore-ui.ui.translations.button_group', 'Acciones');
@endphp

<div {{ $attributes
    ->except(['ariaLabel'])
    ->class('inline-flex [&>*]:rounded-none [&>*:first-child]:rounded-l-kore-md [&>*:last-child]:rounded-r-kore-md [&>*+*]:-ml-px') }}
    role="group"
    aria-label="{{ $ariaLabel }}">
    {{ $slot }}
</div>
