@props([
    'paginator' => null,
    'showingText' => null,
])

{{-- Aquí no hay selector de «por página»: esta vista pinta el recuento y los
     enlaces, nada más. Declaraba `perPage` y `perPageOptions` y no los leía
     nadie — el selector del DataTable vive en su propia barra. --}}

@php
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
