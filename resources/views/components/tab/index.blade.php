@props([
    'selected' => null,
    'variant' => null,
    'orientation' => 'horizontal',
    'scrollable' => false,
    // `false` apaga las transiciones de este componente y de su panel. Ver
    // KoreUi\Core\Support\Motion.
    'animated' => null,
])

@php $attributes = \KoreUi\Core\Support\Motion::marcar($attributes, $animated); @endphp

@php
    $variant = $variant ?? config('kore-ui.ui.tab.variant', 'line');
@endphp

<div
    {{ $attributes
        ->except(['selected', 'variant', 'orientation', 'scrollable'])
        ->class([
            'flex flex-col' => $orientation === 'horizontal',
            'flex flex-row' => $orientation === 'vertical',
        ])
    }}
    x-data="KoreTab({ selected: @js($selected), variant: @js($variant), orientation: @js($orientation) })"
>
    <input type="hidden" x-ref="hiddenInput" {{ $attributes->wire('model') }} />

    {{-- Tab list --}}
    <div
        x-ref="lista"
        @class([
            'relative flex',
            'overflow-x-auto' => $scrollable && $orientation === 'horizontal',
            // Line variant
            'border-b border-kore-border' => $variant === 'line' && $orientation === 'horizontal',
            'border-l border-kore-border' => $variant === 'line' && $orientation === 'vertical',
            // Pill variant
            'bg-kore-muted rounded-kore-lg p-1 gap-1' => $variant === 'pill',
            // Enclosed variant
            'bg-kore-muted rounded-t-kore-lg p-1 gap-1' => $variant === 'enclosed' && $orientation === 'horizontal',
            // Direction
            'flex-row' => $orientation === 'horizontal' && $variant === 'line',
            'flex-col' => $orientation === 'vertical',
        ])
        role="tablist"
        aria-orientation="{{ $orientation }}"
        x-on:keydown="onKeydown($event)"
    >
        {{-- El indicador: una sola pieza que se desliza hasta la pestaña elegida,
             en vez de un borde o un fondo que se apaga en una y se enciende en
             otra. La coloca `_moverIndicador()`; el aspecto y la transición son
             de `.kore-tab-indicator`, en kore-theme.css. --}}
        <span
            x-ref="indicador"
            aria-hidden="true"
            class="kore-tab-indicator"
            data-variant="{{ $variant === 'line' ? 'line' : 'pill' }}"
            data-orientation="{{ $orientation }}"
        ></span>

        <template x-for="tab in tabs" :key="tab.id">
            <button
                type="button"
                role="tab"
                x-bind:aria-selected="isSelected(tab.id)"
                x-bind:aria-controls="'panel-' + tab.id"
                x-bind:id="'tab-' + tab.id"
                x-bind:data-tab-id="tab.id"
                x-bind:disabled="tab.disabled"
                x-bind:tabindex="isSelected(tab.id) ? 0 : -1"
                x-on:click="select(tab.id)"
                x-bind:class="{
                    {{-- Line variant --}}
                    @if($variant === 'line')
                        'text-kore-primary-text': isSelected(tab.id),
                        'text-kore-muted-fg hover:text-kore-fg': !isSelected(tab.id),
                    @elseif($variant === 'pill' || $variant === 'enclosed')
                        'text-kore-fg': isSelected(tab.id),
                        'text-kore-muted-fg hover:text-kore-fg': !isSelected(tab.id),
                    @endif
                    'opacity-50 cursor-not-allowed': tab.disabled,
                }"
                @class([
                    'relative z-10 flex items-center gap-2 text-sm font-medium kore-transition whitespace-nowrap',
                    'px-3.5 py-2.5 kore-focus-inset' => $variant === 'line',
                    'px-3 py-1.5 rounded-kore-md kore-focus' => $variant === 'pill' || $variant === 'enclosed',
                ])
            >
                <span x-show="tab.iconHtml" x-html="tab.iconHtml" class="size-4 shrink-0 [&>svg]:size-4"></span>
                <span x-text="tab.label"></span>
                <template x-if="tab.badge !== null && tab.badge !== undefined">
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-semibold rounded-full bg-kore-primary/10 text-kore-primary-text" x-text="tab.badge"></span>
                </template>
                <template x-if="tab.closeable">
                    <button
                        type="button"
                        class="ml-1 p-0.5 rounded-kore-sm hover:bg-kore-muted kore-transition"
                        x-on:click.stop="closeTab(tab.id)"
                        aria-label="{{ config('kore-ui.ui.translations.close_tab', 'Cerrar pestaña') }}"
                    >
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </template>
            </button>
        </template>
    </div>

    {{-- Tab panels --}}
    <div @class([
        'flex-1 min-w-0',
        'border border-t-0 border-kore-border rounded-b-kore-lg p-4' => $variant === 'enclosed',
        'pt-4' => $variant !== 'enclosed' && $orientation === 'horizontal',
        'pl-4' => $variant !== 'enclosed' && $orientation === 'vertical',
    ])>
        {{ $slot }}
    </div>
</div>
