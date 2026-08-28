<div
    x-data="KoreSpotlight(@js($items), @js($config))"
    @keydown.window.cmd.{{ $config['shortcut'] }}.prevent="toggle()"
    @keydown.window.ctrl.{{ $config['shortcut'] }}.prevent="toggle()"
    @kore:spotlight-open.window="open($event.detail ?? {})"
    @kore:spotlight-close.window="close()"
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0"
        {{-- El mismo token que el overlay manager: el velo se cambia en un solo
             sitio y los dos sistemas se ven igual. Antes era un negro fijo
             escrito aquí a mano. --}}
        style="background-color: color-mix(in oklab, var(--kore-backdrop) 50%, transparent); z-index: 69"
        aria-hidden="true"
        x-cloak
    ></div>

    {{-- Panel --}}
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
        role="dialog"
        aria-modal="true"
        aria-label="{{ config('kore-ui.ui.translations.spotlight', 'Búsqueda rápida') }}"
        @keydown.escape.prevent="close()"
        @keydown.tab="trapFocus($event)"
        class="fixed left-1/2 top-[15%] -translate-x-1/2 w-full max-w-2xl px-4 {{ $config['zIndex'] }}"
        x-cloak
    >
        <div class="bg-kore-surface border border-kore-border rounded-kore-lg shadow-kore-xl overflow-hidden">

            {{-- Search input area + pills --}}
            <div class="flex items-center gap-2 px-4 py-3 border-b border-kore-border">

                {{-- Search icon --}}
                <x-lucide-search class="size-4 text-kore-muted-fg shrink-0" />

                {{-- Pills (completed steps) --}}
                <div class="flex items-center gap-1 flex-wrap" x-show="steps.length > 0">
                    <template x-for="(step, i) in steps" :key="i">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-kore-primary/10 text-kore-primary-text border border-kore-primary/20">
                            <span x-text="step.label"></span>
                            <button
                                type="button"
                                @click="removeStep(i)"
                                class="ml-0.5 hover:text-kore-primary/70 focus:outline-none"
                                :aria-label="@js(config('kore-ui.ui.translations.spotlight_remove_step', 'Quitar :paso')).replace(':paso', step.label)"
                            >&#x2715;</button>
                        </span>
                    </template>
                </div>

                {{-- Input --}}
                <input
                    type="search"
                    role="combobox"
                    aria-expanded="true"
                    aria-haspopup="listbox"
                    aria-autocomplete="list"
                    :aria-activedescendant="activeItemId"
                    aria-controls="kore-spotlight-results"
                    {{-- El placeholder no es nombre accesible: cambia según el
                         paso en que esté la búsqueda y desaparece en cuanto se
                         escribe algo. El único control del panel se quedaba sin
                         nombre. --}}
                    aria-label="{{ config('kore-ui.ui.translations.spotlight_search', 'Buscar') }}"
                    x-ref="input"
                    x-model="query"
                    @keydown.arrow-down.prevent="moveDown()"
                    @keydown.arrow-up.prevent="moveUp()"
                    @keydown.enter.prevent="selectCurrent()"
                    @keydown.backspace="onBackspace($event)"
                    :placeholder="currentPlaceholder"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    class="flex-1 bg-transparent text-kore-fg placeholder:text-kore-muted-fg text-sm focus:outline-none min-w-0"
                >

                {{-- Loading spinner --}}
                <div x-show="isSearching" class="shrink-0">
                    <x-lucide-loader-2 class="size-4 text-kore-muted-fg animate-spin" />
                </div>

                {{-- Shortcut badge --}}
                <kbd
                    x-show="!isSearching"
                    class="shrink-0 hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium text-kore-muted-fg border border-kore-border"
                >
                    <span class="text-xs">⌘</span>{{ strtoupper($config['shortcut']) }}
                </kbd>
            </div>

            {{-- Results list --}}
            <div class="max-h-[400px] overflow-y-auto overscroll-contain" x-ref="scrollContainer">

                {{-- No results --}}
                <div
                    x-show="!isSearching && query.length > 0 && groupedResults.length === 0"
                    class="py-12 text-center"
                >
                    <x-lucide-search class="mx-auto mb-3 size-8 text-kore-muted-fg opacity-40" />
                    <p class="text-sm text-kore-muted-fg">Sin resultados para "<span x-text="query" class="font-medium"></span>"</p>
                </div>

                {{-- Empty state (no query, no recent, no items) --}}
                <div
                    x-show="!isSearching && query.length === 0 && groupedResults.length === 0 && recentItems.length === 0"
                    class="py-12 text-center"
                >
                    <x-lucide-zap class="mx-auto mb-3 size-8 text-kore-muted-fg opacity-40" />
                    <p class="text-sm text-kore-muted-fg">Comienza a escribir para buscar acciones</p>
                </div>

                <ul
                    id="kore-spotlight-results"
                    role="listbox"
                    aria-label="{{ config('kore-ui.ui.translations.spotlight_results', 'Resultados') }}"
                    class="py-2"
                >
                    {{-- Recent history (shown when input is empty) --}}
                    <template x-if="config.showRecent && query.length === 0 && recentItems.length > 0 && steps.length === 0">
                        <li role="presentation">
                            <div role="group" aria-labelledby="kore-group-recientes">
                                <p id="kore-group-recientes" role="none" class="px-4 pt-2 pb-1 text-xs font-semibold text-kore-muted-fg uppercase tracking-wider">
                                    Recientes
                                </p>
                                <ul>
                                    <template x-for="item in recentItems" :key="'recent-' + item.id">
                                        <li
                                            :id="'kore-item-' + item.id + '-recent'"
                                            role="option"
                                            :aria-selected="activeIndex === item._index"
                                            @click="selectItem(item)"
                                            @mouseenter="activeIndex = item._index ?? -1"
                                            :class="activeIndex === item._index ? 'bg-kore-primary/10 text-kore-primary-text' : 'text-kore-fg hover:bg-kore-surface-hover'"
                                            class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors duration-75 group"
                                        >
                                            <span class="size-5 flex items-center justify-center text-kore-muted-fg group-hover:text-inherit shrink-0">
                                                <template x-if="item.iconHtml">
                                                    <span x-html="item.iconHtml"></span>
                                                </template>
                                                <template x-if="!item.iconHtml">
                                                    <x-lucide-clock class="size-4 opacity-50" />
                                                </template>
                                            </span>
                                            <span class="flex-1 text-sm truncate" x-text="item.name"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </li>
                    </template>

                    {{-- Grouped results --}}
                    <template x-for="group in groupedResults" :key="group.name">
                        <li role="presentation">
                            <div role="group" :aria-labelledby="'kore-group-' + group.name">
                                <p
                                    :id="'kore-group-' + group.name"
                                    role="none"
                                    class="px-4 pt-2 pb-1 text-xs font-semibold text-kore-muted-fg uppercase tracking-wider"
                                    x-text="group.name"
                                ></p>
                                <ul>
                                    <template x-for="item in group.items" :key="item.id">
                                        <li
                                            :id="'kore-item-' + item.id"
                                            role="option"
                                            :aria-selected="activeIndex === item._index"
                                            @click="selectItem(item)"
                                            @mouseenter="activeIndex = item._index"
                                            :class="activeIndex === item._index ? 'bg-kore-primary/10 text-kore-primary-text' : 'text-kore-fg hover:bg-kore-surface-hover'"
                                            class="flex items-center gap-3 px-4 py-2.5 cursor-pointer transition-colors duration-75 group"
                                        >
                                            {{-- Icon --}}
                                            <span class="size-5 flex items-center justify-center text-kore-muted-fg group-hover:text-inherit shrink-0">
                                                <template x-if="item.iconHtml">
                                                    <span x-html="item.iconHtml"></span>
                                                </template>
                                                <template x-if="!item.iconHtml">
                                                    <span class="size-4 rounded-full bg-kore-border/50"></span>
                                                </template>
                                            </span>

                                            {{-- Text --}}
                                            <span class="flex-1 min-w-0">
                                                <span class="block text-sm truncate" x-text="item.name"></span>
                                                <span
                                                    x-show="item.description"
                                                    class="block text-xs text-kore-muted-fg truncate"
                                                    x-text="item.description"
                                                ></span>
                                            </span>

                                            {{-- Right side: shortcut or dependency indicator --}}
                                            <span class="shrink-0 flex items-center gap-1.5">
                                                <template x-if="item.dependencies && item.dependencies.length > 0">
                                                    <x-lucide-chevrons-right class="size-3.5 text-kore-muted-fg" />
                                                </template>
                                                <template x-if="item.shortcut && !(item.dependencies && item.dependencies.length > 0)">
                                                    <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium text-kore-muted-fg border border-kore-border" x-text="item.shortcut"></kbd>
                                                </template>
                                            </span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-4 py-2 border-t border-kore-border text-xs text-kore-muted-fg">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded border border-kore-border text-xs">↑↓</kbd>
                        Navegar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded border border-kore-border text-xs">↵</kbd>
                        Seleccionar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1 py-0.5 rounded border border-kore-border text-xs">Esc</kbd>
                        Cerrar
                    </span>
                </div>
                <template x-if="config.showRecent && recentItems.length > 0 && query.length === 0">
                    <button
                        type="button"
                        @click="clearHistory()"
                        class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        Limpiar historial
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>
