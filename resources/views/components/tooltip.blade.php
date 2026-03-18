@props([
    'text' => null,
    'position' => null,
    'delay' => null,
])

@php
    $position = $position ?? config('kore-ui.ui.tooltip.position', 'top');
    $delay = $delay ?? config('kore-ui.ui.tooltip.delay', 200);
@endphp

<div
    {{ $attributes
        ->except(['text', 'position', 'delay'])
        ->class('inline-flex')
    }}
    x-data="{
        show: false,
        timer: null,
        position: '{{ $position }}',
        _boundScroll: null,
        _boundResize: null,
        open() {
            this.timer = setTimeout(() => {
                this.positionTooltip();
                this.show = true;
                this._addListeners();
            }, {{ $delay }});
        },
        close() {
            clearTimeout(this.timer);
            this.show = false;
            this._removeListeners();
        },
        _addListeners() {
            this._boundScroll = () => { if (this.show) this.close(); };
            this._boundResize = () => { if (this.show) this.close(); };
            window.addEventListener('scroll', this._boundScroll, { capture: true, passive: true });
            window.addEventListener('resize', this._boundResize, { passive: true });
        },
        _removeListeners() {
            if (this._boundScroll) {
                window.removeEventListener('scroll', this._boundScroll, { capture: true });
                this._boundScroll = null;
            }
            if (this._boundResize) {
                window.removeEventListener('resize', this._boundResize);
                this._boundResize = null;
            }
        },
        destroy() {
            this._removeListeners();
        },
        positionTooltip() {
            const trigger = this.$refs.trigger;
            const tip = this.$refs.tooltip;
            if (!trigger || !tip) return;

            const rect = trigger.getBoundingClientRect();
            const gap = 8;

            let top, left, transform;

            if (this.position === 'top') {
                top = rect.top - gap;
                left = rect.left + rect.width / 2;
                transform = 'translate(-50%, -100%)';
            } else if (this.position === 'bottom') {
                top = rect.bottom + gap;
                left = rect.left + rect.width / 2;
                transform = 'translate(-50%, 0)';
            } else if (this.position === 'left') {
                top = rect.top + rect.height / 2;
                left = rect.left - gap;
                transform = 'translate(-100%, -50%)';
            } else {
                top = rect.top + rect.height / 2;
                left = rect.right + gap;
                transform = 'translate(0, -50%)';
            }

            tip.style.position = 'fixed';
            tip.style.top = top + 'px';
            tip.style.left = left + 'px';
            tip.style.transform = transform;
        }
    }"
    x-on:mouseenter="open()"
    x-on:mouseleave="close()"
    x-on:focus.capture="open()"
    x-on:blur.capture="close()"
>
    <div x-ref="trigger" class="inline-flex">
        {{ $slot }}
    </div>

    <template x-teleport="body">
        <div
            x-ref="tooltip"
            x-show="show"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            role="tooltip"
            class="z-[70] pointer-events-none max-w-xs"
        >
            <div class="bg-kore-fg text-white rounded-kore-md text-xs px-2.5 py-1.5 font-medium">
                {{ $text }}
            </div>
        </div>
    </template>
</div>
