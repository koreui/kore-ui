<li aria-hidden="true" class="flex items-center text-kore-muted-foreground/60 select-none {{ $separatorClass ?? '' }}">
    @if ($isSeparatorIcon)
        <x-dynamic-component :component="'lucide-' . $separatorIcon" class="{{ $s['sep'] }} shrink-0" />
    @else
        <span class="{{ $s['text'] }}">{{ $separator }}</span>
    @endif
</li>
