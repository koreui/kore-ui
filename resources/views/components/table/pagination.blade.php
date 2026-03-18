@props([
    'paginator' => null,
    'perPage' => 25,
    'perPageOptions' => [],
    'showingText' => null,
])

@php
    $perPageOptions = $perPageOptions ?: config('kore-ui.datatable.per_page_options', [10, 25, 50, 100]);
    $translations = config('kore-ui.datatable.translations', []);
@endphp

@if($paginator && $paginator->hasPages())
    <div {{ $attributes->class('flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3') }}>
        {{-- Info text --}}
        <div class="text-sm text-kore-muted-fg">
            @if($showingText)
                {{ $showingText }}
            @elseif(method_exists($paginator, 'total'))
                {{ str_replace(
                    [':from', ':to', ':total'],
                    [$paginator->firstItem() ?? 0, $paginator->lastItem() ?? 0, $paginator->total()],
                    $translations['showing'] ?? 'Mostrando :from a :to de :total resultados'
                ) }}
            @endif
        </div>

        {{-- Pagination links --}}
        <div class="flex items-center gap-2">
            {{ $paginator->links() }}
        </div>
    </div>
@endif
