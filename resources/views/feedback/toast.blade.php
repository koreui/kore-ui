<div
    class="kore-toast pointer-events-auto w-full max-w-sm overflow-hidden rounded-kore-lg
           bg-kore-surface text-kore-surface-fg shadow-lg ring-1 ring-kore-border
           transition-all touch-none"
    :class="{
        'opacity-0 translate-x-full': !toast._visible && toast.position.includes('right'),
        'opacity-0 -translate-x-full': !toast._visible && toast.position.includes('left'),
        'opacity-0 -translate-y-4': !toast._visible && toast.position.includes('top') && toast.position.includes('center'),
        'opacity-0 translate-y-4': !toast._visible && toast.position.includes('bottom') && toast.position.includes('center'),
    }"
    :style="swipeStyle(toast.id)"
    :role="toast.type === 'error' ? 'alert' : 'status'"
    :data-resolving="toast.resolving ?? false"
    x-init="$nextTick(() => { toast._visible = true })"
    @mouseenter="setHovered(toast, true); pauseTimer(toast.id)"
    @mouseleave="setHovered(toast, false); resumeTimer(toast.id)"
    @pointerdown.self="startSwipe($event, toast)"
    @pointermove.self="moveSwipe($event, toast)"
    @pointerup.self="endSwipe($event, toast)"
>
    {{-- Header (always visible) --}}
    <div class="flex items-center gap-3 p-4">
        {{-- Icon --}}
        <div class="kore-toast-icon shrink-0 transition-all duration-200"
             :class="{
                'opacity-0 scale-50': toast.resolving,
                'text-kore-success': toast.type === 'success',
                'text-kore-destructive': toast.type === 'error',
                'text-kore-warning': toast.type === 'warning',
                'text-kore-info': toast.type === 'info',
                'text-kore-primary': toast.type === 'question',
                'text-kore-muted-fg': toast.type === 'loading',
             }">
            <x-lucide-circle-check  x-show="toast.type === 'success'"  class="h-5 w-5" x-cloak />
            <x-lucide-circle-x      x-show="toast.type === 'error'"    class="h-5 w-5" x-cloak />
            <x-lucide-triangle-alert x-show="toast.type === 'warning'" class="h-5 w-5" x-cloak />
            <x-lucide-info           x-show="toast.type === 'info'"    class="h-5 w-5" x-cloak />
            <x-lucide-circle-help    x-show="toast.type === 'question'" class="h-5 w-5" x-cloak />
            <x-lucide-loader-circle  x-show="toast.type === 'loading'" class="h-5 w-5 animate-spin" x-cloak />
        </div>

        {{-- Title + badge --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">
                <span x-text="toast.title"></span>
                <span
                    x-show="toast.count > 1"
                    x-text="'(' + toast.count + ')'"
                    class="ml-1.5 inline-flex items-center justify-center min-w-5 h-5 px-1
                           rounded-full bg-kore-fg/10 text-xs font-medium"
                ></span>
            </p>
        </div>

        {{-- Dismiss button --}}
        <template x-if="toast.dismissible !== false">
            <button
                @click="dismiss(toast.id)"
                class="shrink-0 rounded-kore-sm p-1 text-kore-muted-fg hover:text-kore-fg
                       hover:bg-kore-fg/5 transition-colors"
                aria-label="Cerrar notificación"
            >
                <x-lucide-x class="h-4 w-4" />
            </button>
        </template>
    </div>

    {{-- Expandable body. isExpanded() se evalúa en vivo para que resolve() —que activa
         autoExpand al llegar una descripción— re-expanda el toast ya montado. --}}
    <div x-show="isExpanded(toast)" x-collapse class="overflow-hidden">
        <div class="px-4 pb-4 pl-12">
            {{-- Description --}}
            <template x-if="toast.description">
                <p class="text-sm text-kore-muted-fg" x-text="toast.description"></p>
            </template>

            {{-- Actions --}}
            <template x-if="toast.actions?.length > 0">
                <div class="mt-3 flex flex-wrap gap-2">
                    <template x-for="(action, i) in toast.actions" :key="i">
                        <button
                            @click="executeAction(toast, action)"
                            class="inline-flex items-center rounded-kore-md px-2.5 py-1.5 text-xs font-medium
                                   bg-kore-fg/5 text-kore-fg hover:bg-kore-fg/10 transition-colors"
                            x-text="action.label"
                        ></button>
                    </template>
                </div>
            </template>

            {{-- Confirm/Cancel options --}}
            <template x-if="toast.options?.confirm">
                <div class="mt-3 flex gap-2">
                    <template x-if="toast.options.cancel">
                        <button
                            @click="executeOption(toast, 'cancel')"
                            class="inline-flex items-center rounded-kore-md px-2.5 py-1.5 text-xs font-medium
                                   bg-kore-secondary text-kore-secondary-fg ring-1 ring-inset ring-kore-border
                                   hover:bg-kore-accent transition-colors"
                            x-text="toast.options.cancel.text"
                        ></button>
                    </template>
                    <button
                        @click="executeOption(toast, 'confirm')"
                        class="inline-flex items-center rounded-kore-md px-2.5 py-1.5 text-xs font-medium
                               bg-kore-primary text-kore-primary-fg hover:bg-kore-primary/90 transition-colors"
                        x-text="toast.options.confirm.text"
                    ></button>
                </div>
            </template>
        </div>
    </div>

    {{-- Progress bar (CSS-driven timer) --}}
    <template x-if="toast.timeout > 0 && toast.dismissible !== false">
        <div class="h-0.5 w-full bg-kore-border/50 overflow-hidden">
            <div
                class="kore-toast-progress h-full origin-left"
                :class="{
                    'bg-kore-success': toast.type === 'success',
                    'bg-kore-destructive': toast.type === 'error',
                    'bg-kore-warning': toast.type === 'warning',
                    'bg-kore-info': toast.type === 'info',
                    'bg-kore-primary': toast.type === 'question',
                    'bg-kore-muted-fg': toast.type === 'loading',
                }"
                :style="`--kore-toast-duration: ${toast.timeout}s`"
                :data-toast-progress="toast.id"
                x-on:animationend.self="dismiss(toast.id)"
            ></div>
        </div>
    </template>
</div>
