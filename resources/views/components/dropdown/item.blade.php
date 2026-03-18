@props([
    'icon' => null,
    'label' => null,
    'description' => null,
    'href' => null,
    'disabled' => false,
])

@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes
        ->except(['icon', 'label', 'description', 'href', 'disabled'])
        ->class([
            'flex items-center gap-2 w-full px-3 py-2 text-sm text-left transition-colors',
            'text-kore-fg hover:bg-kore-muted focus:bg-kore-muted focus:outline-none' => !$disabled,
            'text-kore-muted-fg cursor-not-allowed opacity-50' => $disabled,
        ])
    }}
    role="menuitem"
    @if($tag === 'a')
        href="{{ $href }}"
    @else
        type="button"
    @endif
    @if($disabled)
        @if($tag === 'a') aria-disabled="true" tabindex="-1" @else disabled @endif
    @endif
    @if(!$disabled && !$href)
        x-on:click="close()"
    @endif
>
    @if($icon)
        <x-dynamic-component :component="'lucide-' . $icon" class="size-4 shrink-0 text-kore-muted-fg" />
    @endif

    <div class="flex-1 min-w-0">
        @if($label)
            <div>{{ $label }}</div>
        @endif

        {{ $slot }}

        @if($description)
            <div class="text-xs text-kore-muted-fg">{{ $description }}</div>
        @endif
    </div>
</{{ $tag }}>
