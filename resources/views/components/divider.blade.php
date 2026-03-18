@props([
    'label' => null,
    'icon' => null,
    'align' => 'center',
    'orientation' => 'horizontal',
    'type' => null,
])

@php
    $type = $type ?? config('kore-ui.ui.divider.type', 'solid');

    $borderStyle = match($type) {
        'dashed' => 'border-dashed',
        'dotted' => 'border-dotted',
        default => 'border-solid',
    };

    $hasContent = $label || $icon || $slot->isNotEmpty();
@endphp

@if($orientation === 'vertical')
    <div
        {{ $attributes
            ->except(['label', 'icon', 'align', 'orientation', 'type'])
            ->class([
                'border-l border-kore-border self-stretch',
                $borderStyle,
            ])
        }}
        role="separator"
        aria-orientation="vertical"
    ></div>
@elseif($hasContent)
    <div
        {{ $attributes
            ->except(['label', 'icon', 'align', 'orientation', 'type'])
            ->class(['flex items-center gap-3 w-full'])
        }}
        role="separator"
        aria-orientation="horizontal"
    >
        {{-- Left line --}}
        <div @class([
            'border-t border-kore-border',
            $borderStyle,
            'w-[5%] shrink-0' => $align === 'right',
            'flex-1' => $align !== 'right',
        ])></div>

        {{-- Content --}}
        <span class="text-sm text-kore-muted-fg whitespace-nowrap shrink-0 flex items-center gap-1.5">
            @if($icon)
                <x-dynamic-component :component="'lucide-' . $icon" class="size-4" />
            @endif
            @if($label)
                {{ $label }}
            @endif
            @if($slot->isNotEmpty())
                {{ $slot }}
            @endif
        </span>

        {{-- Right line --}}
        <div @class([
            'border-t border-kore-border',
            $borderStyle,
            'w-[5%] shrink-0' => $align === 'left',
            'flex-1' => $align !== 'left',
        ])></div>
    </div>
@else
    <div
        {{ $attributes
            ->except(['label', 'icon', 'align', 'orientation', 'type'])
            ->class([
                'border-t border-kore-border w-full',
                $borderStyle,
            ])
        }}
        role="separator"
        aria-orientation="horizontal"
    ></div>
@endif
