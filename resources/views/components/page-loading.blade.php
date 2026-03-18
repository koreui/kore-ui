@props([
    'type' => null,
    'blur' => null,
    'text' => null,
])

@php
    $type = $type ?? config('kore-ui.ui.page-loading.type', 'spinner');
    $blur = $blur ?? config('kore-ui.ui.page-loading.blur', true);
    $defaultText = $text ?? config('kore-ui.ui.page-loading.text');
@endphp

<div
    x-data="{
        show: false,
        text: '{{ $defaultText ?? '' }}',
        open(detail) {
            if (typeof detail === 'object' && detail !== null) {
                this.text = detail.text ?? '{{ $defaultText ?? '' }}';
                if (detail.show === false) { this.close(); return; }
            }
            this.show = true;
        },
        close() {
            this.show = false;
        }
    }"
    x-on:kore-page-loading.window="open($event.detail)"
    x-on:kore-page-loading-close.window="close()"
    x-show="show"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 z-[70] flex flex-col items-center justify-center bg-kore-surface/80 {{ $blur ? 'backdrop-blur-sm' : '' }}"
    role="status"
    aria-live="assertive"
>
    <x-kore::loading :type="$type" size="lg" />
    <template x-if="text">
        <p class="mt-3 text-sm font-medium text-kore-muted-fg" x-text="text"></p>
    </template>
</div>
