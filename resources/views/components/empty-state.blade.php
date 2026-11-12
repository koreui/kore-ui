@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'image' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center text-center py-12 px-6']) }}>
    @if($image)
        <img src="{{ $image }}" alt="" class="size-24 mb-4" />
    @elseif($icon)
        <div class="size-11 rounded-kore-lg border border-kore-border bg-kore-surface shadow-kore-xs flex items-center justify-center mb-4">
            <x-dynamic-component :component="'lucide-' . $icon" class="size-5 text-kore-muted-fg" />
        </div>
    @endif

    @if($title)
        <h3 class="text-base font-semibold text-kore-fg">{{ $title }}</h3>
    @endif

    @if($description)
        <p class="mt-1 text-sm text-kore-muted-fg max-w-sm text-pretty">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif

    @if(isset($action))
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
