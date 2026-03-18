@props([
    'nodes' => [],
    'selectable' => false,
    'selectionMode' => 'single',
    'expandedKeys' => [],
    'selectedKeys' => [],
    'filter' => false,
    'filterPlaceholder' => 'Filter...',
])

<div {{ $attributes->class(['w-full']) }}
     x-data="KoreTree({
        nodes: @js($nodes),
        selectable: {{ $selectable ? 'true' : 'false' }},
        selectionMode: '{{ $selectionMode }}',
        expandedKeys: @js($expandedKeys),
        selectedKeys: @js($selectedKeys),
        filter: {{ $filter ? 'true' : 'false' }},
     })"
     role="tree">

    {{-- Filter --}}
    @if($filter)
        <div class="mb-3">
            <div class="relative">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-kore-muted-fg" />
                <input type="text"
                       x-model.debounce.200ms="filterText"
                       placeholder="{{ $filterPlaceholder }}"
                       class="w-full pl-9 pr-3 py-2 text-sm rounded-kore-md border border-kore-border bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg focus:outline-none focus:ring-2 focus:ring-kore-ring" />
            </div>
        </div>
    @endif

    {{-- Flat-rendered nodes --}}
    <div class="space-y-0.5" role="group">
        <template x-for="item in flatNodes" :key="item.node.key">
            <div x-show="item.visible" class="select-none"
                 :aria-level="item.level + 1">
                <div class="flex items-center gap-1 py-1 px-2 rounded-kore-sm cursor-pointer transition-colors hover:bg-kore-accent/50"
                     :class="isSelected(item.node.key) && 'bg-kore-primary/10 text-kore-primary'"
                     :style="'padding-left: ' + (item.level * 20 + 8) + 'px'"
                     x-on:click="onNodeClick(item.node)"
                     role="treeitem"
                     :aria-expanded="item.hasChildren ? isExpanded(item.node.key) : undefined"
                     :aria-selected="selectable ? isSelected(item.node.key) : undefined">

                    {{-- Expand/collapse chevron --}}
                    <template x-if="item.hasChildren">
                        <button type="button"
                                x-on:click.stop="toggleExpand(item.node.key)"
                                class="inline-flex items-center justify-center size-5 rounded-kore-sm hover:bg-kore-accent transition-colors"
                                aria-label="Toggle expand">
                            <svg class="size-3.5 transition-transform duration-150"
                                 :class="isExpanded(item.node.key) && 'rotate-90'"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </template>

                    <template x-if="!item.hasChildren">
                        <span class="inline-block size-5"></span>
                    </template>

                    {{-- Checkbox --}}
                    @if($selectable && $selectionMode === 'checkbox')
                        <input type="checkbox"
                               :checked="isSelected(item.node.key)"
                               x-on:click.stop="toggleSelect(item.node.key)"
                               class="size-4 rounded border-kore-border text-kore-primary focus:ring-kore-ring" />
                    @endif

                    {{-- Label --}}
                    <span class="text-sm truncate" x-text="item.node.label"></span>
                </div>
            </div>
        </template>
    </div>
</div>
