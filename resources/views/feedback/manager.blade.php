<div
    x-data="KoreFeedback(@js($flash), @js($config))"
    class="pointer-events-none"
>
    {{-- One fixed container per active position --}}
    <template x-for="pos in activePositions" :key="pos">
        <div
            :class="{
                'top-4 right-4 items-end': pos === 'top-right',
                'top-4 left-4 items-start': pos === 'top-left',
                'top-4 inset-x-0 items-center': pos === 'top-center',
                'bottom-4 right-4 items-end': pos === 'bottom-right',
                'bottom-4 left-4 items-start': pos === 'bottom-left',
                'bottom-4 inset-x-0 items-center': pos === 'bottom-center',
                'flex-col-reverse': pos.startsWith('bottom'),
                'flex-col': !pos.startsWith('bottom'),
            }"
            style="z-index: 60"
            class="fixed flex gap-3 pointer-events-none"
            role="region"
            aria-label="Notificaciones"
            aria-live="polite"
            aria-relevant="additions removals"
        >
            <template x-for="toast in visibleToasts(pos)" :key="toast.id">
                @include('kore::feedback.toast')
            </template>

            {{-- Hidden count indicator --}}
            <template x-if="hiddenCount(pos) > 0">
                <div class="text-center text-xs text-kore-muted-fg pointer-events-auto py-1">
                    <span x-text="'+' + hiddenCount(pos) + ' más'"></span>
                </div>
            </template>
        </div>
    </template>
</div>
