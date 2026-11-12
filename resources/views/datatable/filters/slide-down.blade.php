{{-- Slide-down trigger button (rendered in toolbar row) --}}
@php
    $filterDefs = $filterDefs ?? [];
    $filterCount = $filterCount ?? 0;
    $translations = $translations ?? [];
@endphp

@if(count($filterDefs) > 0)
    <button
        type="button"
        x-on:click="slideDownOpen = !slideDownOpen"
        class="kore-tool"
    >
        <x-lucide-filter class="size-4" />
        <span>{{ $translations['filters'] ?? 'Filtros' }}</span>
        @if($filterCount > 0)
            <span
                class="inline-flex items-center justify-center size-5 rounded-full bg-kore-primary text-kore-primary-fg text-xs font-medium"
            >{{ $filterCount }}</span>
        @endif
        <x-lucide-chevron-down
            class="size-4 kore-transition-base"
            x-bind:class="slideDownOpen ? 'rotate-180' : ''"
        />
    </button>
@endif
