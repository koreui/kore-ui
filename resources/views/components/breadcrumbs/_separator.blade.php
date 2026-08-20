<li aria-hidden="true" {!! $extra ?? '' !!} class="flex items-center text-kore-muted-fg/60 select-none {{ $separatorClass ?? '' }}">
    @if ($isSeparatorIcon)
        <x-dynamic-component :component="'lucide-' . $separatorIcon" class="{{ $s['sep'] }} shrink-0" />
    @else
        <span class="{{ $s['text'] }}">{{ $separator }}</span>
    @endif
</li>
