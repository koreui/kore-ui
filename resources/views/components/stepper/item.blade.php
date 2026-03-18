@props([
    'id' => null,
    'label' => null,
    'description' => null,
    'icon' => null,
    'status' => 'pending',
    'disabled' => false,
])

@php
    $id = $id ?? 'step-' . uniqid();
@endphp

{{-- Icon template for auto-registration --}}
@if($icon)
    <template x-ref="step_icon_{{ $id }}" class="hidden">
        <x-dynamic-component :component="'lucide-' . $icon" class="size-4" />
    </template>
@endif

{{-- Auto-register this step --}}
<div
    x-init="
        $nextTick(() => {
            const iconRef = $refs['step_icon_{{ $id }}'];
            const iconHtml = iconRef ? iconRef.content?.firstElementChild?.outerHTML ?? '' : '';
            registerStep({
                id: @js($id),
                label: @js($label),
                description: @js($description),
                iconHtml: iconHtml,
                status: @js($status),
                disabled: @js($disabled),
            });
        })
    "
    x-show="selected === @js($id)"
    x-cloak
    {{ $attributes->except(['id', 'label', 'description', 'icon', 'status', 'disabled']) }}
>
    {{ $slot }}
</div>
