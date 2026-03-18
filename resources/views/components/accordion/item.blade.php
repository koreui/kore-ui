@props([
    'id' => null,
    'title' => null,
    'icon' => null,
    'disabled' => false,
    'open' => false,
])

@php
    $id = $id ?? 'accordion-' . uniqid();
@endphp

<div
    {{ $attributes
        ->except(['id', 'title', 'icon', 'disabled', 'open'])
        ->class([
            'border border-kore-border rounded-kore-lg overflow-hidden' => false, // variant separated handled by parent
        ])
    }}
    data-accordion-item="{{ $id }}"
    @if($open) data-open="true" @endif
    x-bind:class="variant === 'separated' && 'border border-kore-border rounded-kore-lg overflow-hidden'"
>
    {{-- Header --}}
    <button
        type="button"
        class="flex items-center justify-between gap-3 w-full text-left px-4 py-3 text-sm font-medium text-kore-fg transition-colors hover:bg-kore-muted/50 disabled:opacity-50 disabled:cursor-not-allowed"
        x-on:click="!{{ $disabled ? 'false' : 'true' }} || toggle('{{ $id }}')"
        x-bind:aria-expanded="isOpen('{{ $id }}')"
        aria-controls="panel-{{ $id }}"
        id="header-{{ $id }}"
        @if($disabled) disabled @endif
    >
        <span class="flex items-center gap-2 min-w-0 flex-1">
            @if($icon)
                <x-dynamic-component :component="'lucide-' . $icon" class="size-4 shrink-0 text-kore-muted-fg" />
            @endif
            <span class="truncate">{{ $title }}</span>
        </span>
        <x-lucide-chevron-down
            class="size-4 shrink-0 text-kore-muted-fg transition-transform duration-200"
            x-bind:class="isOpen('{{ $id }}') && 'rotate-180'"
        />
    </button>

    {{-- Panel --}}
    <div
        class="grid transition-[grid-template-rows] duration-300"
        x-bind:class="isOpen('{{ $id }}') ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        role="region"
        aria-labelledby="header-{{ $id }}"
        id="panel-{{ $id }}"
    >
        <div class="overflow-hidden">
            <div class="px-4 pb-3 text-sm text-kore-muted-fg">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
