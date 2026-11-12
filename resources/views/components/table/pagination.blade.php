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
    {{-- El mismo pie que el DataTable: fondo tenue, recuento a la izquierda y
         los controles a la derecha. --}}
    <div {{ $attributes->class('flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-2.5 border-t border-kore-border bg-kore-muted/40') }}>
        {{-- Info text --}}
        <div class="text-[0.8125rem] text-kore-muted-fg tabular-nums">
            @if($showingText)
                {{ $showingText }}
            @elseif(method_exists($paginator, 'total'))
                {{-- `strtr`, no `str_replace`: este último sustituye en orden, y
                     `:to` se comía el principio de `:total` — el recuento salía
                     «de 11tal resultados». `strtr` prueba primero la clave más
                     larga. --}}
                {{ strtr($translations['showing'] ?? 'Mostrando :from a :to de :total resultados', [
                    ':from' => $paginator->firstItem() ?? 0,
                    ':to' => $paginator->lastItem() ?? 0,
                    ':total' => $paginator->total(),
                ]) }}
            @endif
        </div>

        {{-- Los enlaces, con la vista de la librería y no la del framework. --}}
        {{ $paginator->links('kore::pagination.links') }}
    </div>
@endif
