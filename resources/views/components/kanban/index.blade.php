@props([
    'columns' => [],
    'cards' => [],
    'handler' => 'moveCard',
    'group' => null,
    'animation' => null,
])

@php
    $group = $group ?? config('kore-ui.kanban.group', 'kanban');
    $animation = $animation ?? config('kore-ui.kanban.animation', 150);
    $columnWidth = config('kore-ui.kanban.column_width', '18rem');

    $cards = $cards instanceof \Illuminate\Support\Collection ? $cards : collect($cards);
    $cardsByColumn = $cards->groupBy('column');
@endphp

<div {{ $attributes->except(['columns', 'cards', 'handler', 'group', 'animation'])->class(['flex items-start gap-4 overflow-x-auto pb-3']) }}>
    @foreach($columns as $column)
        <x-kore::kanban.column
            :column="$column"
            :cards="$cardsByColumn[$column['id']] ?? collect()"
            :handler="$handler"
            :group="$group"
            :animation="$animation"
            :width="$columnWidth"
        />
    @endforeach
</div>
