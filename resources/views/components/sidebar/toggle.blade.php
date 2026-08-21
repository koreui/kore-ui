@props([
    'for' => 'main',
    'label' => null,
    'icon' => 'panel-left',
])

@php
    $label = $label ?? config('kore-ui.ui.translations.sidebar_toggle', 'Alternar navegación');
@endphp

{{-- El botón hamburguesa. Vive en el store y no en el x-data del sidebar, así que
     puede colocarse donde sea —en la navbar, suelto en la página— sin estar anidado
     dentro del sidebar.

     handleToggle() hace lo correcto según el viewport: colapsa en escritorio y abre
     el drawer en móvil. Es lo que espera el 99% de los botones de este tipo. --}}
<button
    type="button"
    x-data
    x-on:click="$store.koreSidebar.handleToggle(@js($for))"
    x-bind:aria-expanded="$store.koreSidebar.isMobile(@js($for))
        ? String($store.koreSidebar.isOpen(@js($for)))
        : String(! $store.koreSidebar.isCollapsed(@js($for)))"
    aria-controls="{{ $for }}"
    aria-label="{{ $label }}"
    {{ $attributes->except('class')->class([
        'inline-flex size-9 items-center justify-center rounded-kore-md text-kore-muted-fg transition-colors',
        'hover:bg-kore-muted hover:text-kore-fg',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring/50',
        $attributes->get('class'),
    ]) }}
>
    <x-dynamic-component :component="'lucide-' . $icon" class="size-5" />
</button>
