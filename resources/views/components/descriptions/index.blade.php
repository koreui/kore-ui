@props([
    'title' => null,
    'description' => null,
    'columns' => null,
    'layout' => null,
    'bordered' => null,
    'size' => null,
    'items' => null,
])

@php
    $columns = $columns ?? config('kore-ui.ui.descriptions.columns', 1);
    $layout = $layout ?? config('kore-ui.ui.descriptions.layout', 'horizontal');
    $bordered = \KoreUi\Core\Support\Look::resolver('descriptions', 'bordered', $bordered, false);
    $size = $size ?? config('kore-ui.ui.descriptions.size', 'md');

    $colsClasses = match((int) $columns) {
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-3',
        default => '',
    };

    $listClasses = $bordered
        ? trim('grid grid-cols-1 ' . $colsClasses . ' gap-px bg-kore-border rounded-kore-lg border border-kore-border overflow-hidden')
        : trim('grid grid-cols-1 ' . $colsClasses . ' gap-x-6 gap-y-3');
@endphp

<div {{ $attributes->except(['title', 'description', 'columns', 'layout', 'bordered', 'size', 'items'])->class(['text-kore-fg']) }}>
    @if($title || $description)
        <div class="mb-4">
            @if($title)
                <h3 class="text-base font-semibold text-kore-fg">{{ $title }}</h3>
            @endif

            @if($description)
                <p class="mt-1 text-sm text-kore-muted-fg">{{ $description }}</p>
            @endif
        </div>
    @endif

    <dl class="{{ $listClasses }}">
        @if(is_array($items) && count($items) > 0)
            @foreach($items as $item)
                <x-kore::descriptions.item
                    :label="$item['label'] ?? ''"
                    :value="$item['value'] ?? null"
                    :icon="$item['icon'] ?? null"
                    :span="$item['span'] ?? 1"
                >
                    @isset($item['html'])
                        {!! $item['html'] !!}
                    @endisset
                </x-kore::descriptions.item>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </dl>
</div>
