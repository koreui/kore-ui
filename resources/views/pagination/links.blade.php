{{-- Los enlaces de un paginador de Laravel, con el aspecto del paginador del
     DataTable.

     Es la vista que usa `<x-kore::table.pagination>`. Antes llamaba a
     `$paginator->links()` a secas, que pinta la vista por defecto del framework:
     grises fijos de Tailwind, otro radio, otro foco. Una tabla normal y un
     datatable en la misma pantalla tenían dos paginaciones distintas.

     También se puede usar suelta: `{{ $usuarios->links('kore::pagination.links') }}`.

     Sirve para los dos mundos. Dentro de un componente Livewire con
     `WithPagination` los controles son botones con `wire:click`; fuera, enlaces
     con `href`. Cuando no hay a dónde ir se pinta un `<span aria-disabled>` y no
     un botón deshabilitado, por el mismo motivo que en el datatable: un botón
     deshabilitado desaparece del recorrido del tabulador. --}}
@php
    $enLivewire = isset($__livewire) && method_exists($__livewire, 'gotoPage');
    $pagina = $paginator->getPageName();

    $btnBase = 'inline-flex items-center justify-center min-w-8 h-8 px-2 text-[0.8125rem] tabular-nums rounded-kore-md kore-transition kore-focus';
    $btnActive = 'border border-kore-input bg-kore-field text-kore-fg font-semibold shadow-kore-xs';
    $btnNormal = 'text-kore-fg hover:bg-kore-muted';
    $btnDisabled = 'text-kore-muted-fg/40 cursor-not-allowed';

    $tAnterior = config('kore-ui.datatable.translations.previous', 'Anterior');
    $tSiguiente = config('kore-ui.datatable.translations.next', 'Siguiente');
    $tPagina = config('kore-ui.datatable.translations.go_to_page', 'Ir a la página :page');

    $flechaAnterior = 'M15.75 19.5 8.25 12l7.5-7.5';
    $flechaSiguiente = 'm8.25 4.5 7.5 7.5-7.5 7.5';
@endphp

@if ($paginator->hasPages())
    <nav class="flex items-center gap-1" aria-label="{{ config('kore-ui.datatable.translations.pagination', 'Paginación') }}">
        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true" aria-label="{{ $tAnterior }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $flechaAnterior }}"/></svg>
            </span>
        @elseif ($enLivewire)
            <button type="button" wire:click="previousPage('{{ $pagina }}')" class="{{ $btnBase }} {{ $btnNormal }}" aria-label="{{ $tAnterior }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $flechaAnterior }}"/></svg>
            </button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btnBase }} {{ $btnNormal }}" aria-label="{{ $tAnterior }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $flechaAnterior }}"/></svg>
            </a>
        @endif

        {{-- Números. Un paginador simple o por cursor no trae `$elements`: se
             queda en las dos flechas. --}}
        @foreach ($elements ?? [] as $element)
            @if (is_string($element))
                <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-hidden="true">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $numero => $url)
                    @if ($numero == $paginator->currentPage())
                        <span class="{{ $btnBase }} {{ $btnActive }}" aria-current="page">{{ $numero }}</span>
                    @elseif ($enLivewire)
                        <button type="button" wire:click="gotoPage({{ $numero }}, '{{ $pagina }}')" class="{{ $btnBase }} {{ $btnNormal }}" aria-label="{{ str_replace(':page', $numero, $tPagina) }}">{{ $numero }}</button>
                    @else
                        <a href="{{ $url }}" class="{{ $btnBase }} {{ $btnNormal }}" aria-label="{{ str_replace(':page', $numero, $tPagina) }}">{{ $numero }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if (! $paginator->hasMorePages())
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true" aria-label="{{ $tSiguiente }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $flechaSiguiente }}"/></svg>
            </span>
        @elseif ($enLivewire)
            <button type="button" wire:click="nextPage('{{ $pagina }}')" class="{{ $btnBase }} {{ $btnNormal }}" aria-label="{{ $tSiguiente }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $flechaSiguiente }}"/></svg>
            </button>
        @else
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btnBase }} {{ $btnNormal }}" aria-label="{{ $tSiguiente }}">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $flechaSiguiente }}"/></svg>
            </a>
        @endif
    </nav>
@endif
