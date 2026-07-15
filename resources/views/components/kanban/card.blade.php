@props([
    'card' => [],
])

<div
    wire:key="kore-card-{{ $card['id'] ?? '' }}"
    x-sort:item="{{ $card['id'] ?? '' }}"
    {{ $attributes->except(['card'])->class(['rounded-kore-md border border-kore-border bg-kore-surface p-3 shadow-sm cursor-grab transition-shadow hover:shadow-md']) }}
>
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <p class="text-sm font-medium text-kore-fg">{{ $card['title'] ?? '' }}</p>

        @isset($card['description'])
            <p class="mt-1 text-xs text-kore-muted-fg">{{ $card['description'] }}</p>
        @endisset

        @isset($card['badge'])
            <div class="mt-2">
                <x-kore::badge :label="$card['badge']" :color="$card['badgeColor'] ?? 'muted'" size="sm" />
            </div>
        @endisset
    @endif
</div>
