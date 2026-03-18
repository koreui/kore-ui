<li class="flex items-center {{ $s['gap'] }}">
    @if ($item->current || $item->url === null)
        <span
            class="flex items-center {{ $s['gap'] }} {{ $s['text'] }} text-kore-foreground font-medium"
            @if ($item->current) aria-current="page" @endif
            @if ($item->tooltip) x-data x-tooltip="{{ $item->tooltip }}" @endif
        >
            @if ($item->icon)
                <x-dynamic-component :component="'lucide-' . $item->icon" class="{{ $s['icon'] }} shrink-0" />
            @endif
            {{ $item->label }}
        </span>
    @else
        <a
            href="{{ $item->url }}"
            class="flex items-center {{ $s['gap'] }} {{ $s['text'] }} text-kore-muted-foreground hover:text-kore-foreground transition-colors"
            @if ($item->tooltip) x-data x-tooltip="{{ $item->tooltip }}" @endif
        >
            @if ($item->icon)
                <x-dynamic-component :component="'lucide-' . $item->icon" class="{{ $s['icon'] }} shrink-0" />
            @endif
            {{ $item->label }}
        </a>
    @endif
</li>
