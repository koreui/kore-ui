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
    $id = $id ?? 'tab-' . uniqid();
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
