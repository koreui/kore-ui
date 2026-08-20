@php
    $props = $filter->getComponentProps();
    $key   = $filter->getKey();
@endphp
{{-- El panel de filtros vive dentro de un wire:ignore, así que Livewire no
     morfea este input: sin el watch de abajo, un reset en servidor
     (resetFilter / resetAllFilters / applyPreset / clearPreset) vaciaría
     $filters pero dejaría el texto escrito en pantalla, mostrando todos los
     resultados con el filtro todavía visible. Los demás tipos de filtro son
     componentes Kore que ya hacen esto por su cuenta (select.js, number.js,
     datepicker.js); este input plano necesita el suyo. --}}
<div
    class="relative"
    x-data="{
        init() {
            this.$wire.$watch('filters.{{ $key }}', (value) => {
                const next = value ?? '';
                if (this.$refs.input && this.$refs.input.value !== next) {
                    this.$refs.input.value = next;
                }
            });
        }
    }"
>
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
        <x-lucide-search class="size-3.5 text-kore-muted-fg" />
    </div>
    <input
        type="text"
        x-ref="input"
        @isset($fieldId) id="{{ $fieldId }}" @endisset
        placeholder="{{ $props['placeholder'] }}"
        wire:model.live.debounce.{{ $props['debounce'] }}ms="filters.{{ $key }}"
        class="bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg transition-colors duration-150 block w-full rounded-kore-md border border-kore-input focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary text-xs py-1.5 px-2.5 pl-8"
    />
</div>
