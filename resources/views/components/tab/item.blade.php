@props([
    'id' => null,
    'label' => null,
    'icon' => null,
    'badge' => null,
    'disabled' => false,
    'lazy' => false,
    'closeable' => false,
])

@php
    // No `uniqid()`. Aquí el síntoma es más callado que en el acordeón: el id del
    // panel lo pinta Alpine con `x-bind:id`, así que en el DOM queda congelado
    // en el de la primera carga; pero el `aria-controls` del botón lo emite el
    // servidor y estrena valor en cada render. Resultado: la pestaña sigue
    // funcionando a la vista y la relación botón→panel se rompe a partir del
    // segundo render, con una referencia rota más cada vez. Ver IdContext.
    $id = $id ?? \KoreUi\Core\Support\IdContext::secuencia('tab');
@endphp

{{-- Icon template for auto-registration --}}
@if($icon)
    <template x-ref="icon_{{ $id }}" class="hidden">
        <x-dynamic-component :component="'lucide-' . $icon" class="size-4" />
    </template>
@endif

{{-- Auto-register this tab --}}
<div
    x-init="
        $nextTick(() => {
            const iconRef = $refs['icon_{{ $id }}'];
            const iconHtml = iconRef ? iconRef.content?.firstElementChild?.outerHTML ?? '' : '';
            registerTab({
                id: @js($id),
                label: @js($label),
                iconHtml: iconHtml,
                badge: @js($badge),
                disabled: @js($disabled),
                closeable: @js($closeable),
            });
        })
    "
    x-show="isSelected(@js($id))"
    x-cloak
    role="tabpanel"
    x-bind:id="'panel-' + @js($id)"
    x-bind:aria-labelledby="'tab-' + @js($id)"
    {{ $attributes->except(['id', 'label', 'icon', 'badge', 'disabled', 'lazy', 'closeable']) }}
>
    @if($lazy)
        <template x-if="isSelected(@js($id))">
            <div>{{ $slot }}</div>
        </template>
    @else
        {{ $slot }}
    @endif
</div>
