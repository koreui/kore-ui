@php
    $paginator = $paginator ?? null;
    $showingText = $showingText ?? null;

    if (! $paginator) return;

    // Un CursorPaginator no tiene número de página: reenvía las llamadas que no
    // conoce a su colección, así que preguntarle currentPage() reventaba con un
    // «Method Collection::currentPage does not exist» y tumbaba la página entera.
    // pagination_type => 'cursor' es una opción documentada; aquí se atiende.
    $isCursor = $paginator instanceof \Illuminate\Pagination\CursorPaginator;

    $onFirstPage  = $paginator->onFirstPage();
    $hasMorePages = $paginator->hasMorePages();
    $currentPage  = $isCursor ? null : $paginator->currentPage();
    $lastPage     = (! $isCursor && method_exists($paginator, 'lastPage')) ? $paginator->lastPage() : null;

    // Ventana de páginas con elipsis. Se calcula con aritmética y no recorriendo
    // 1..$lastPage: con un millón de filas a 25 por página eso eran 40.000
    // vueltas por render para pintar siempre los mismos seis botones.
    $pages = [];
    if ($lastPage !== null && $lastPage > 1) {
        $window = 2; // páginas a cada lado de la actual

        $from = max(2, $currentPage - $window);
        $to   = min($lastPage - 1, $currentPage + $window);

        $pages[] = 1;

        if ($from > 2) {
            $pages[] = '...';
        }

        for ($i = $from; $i <= $to; $i++) {
            $pages[] = $i;
        }

        if ($to < $lastPage - 1) {
            $pages[] = '...';
        }

        $pages[] = $lastPage;
    }

    $btnBase = 'inline-flex items-center justify-center size-8 text-sm rounded-kore-md transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring';
    $btnActive = 'bg-kore-primary text-kore-primary-fg font-medium';
    $btnNormal = 'text-kore-fg hover:bg-kore-muted';
    $btnDisabled = 'text-kore-muted-fg/40 cursor-not-allowed';
@endphp

<div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-kore-border">
    {{-- Showing text.
         aria-live: al filtrar o buscar, un lector de pantalla no recibía
         ninguna señal de que la tabla había cambiado. Este texto se reescribe
         en cada render, así que es el sitio natural para anunciarlo. --}}
    @if($showingText)
        <div class="text-sm text-kore-muted-fg" aria-live="polite" aria-atomic="true">
            {{ $showingText }}
        </div>
    @else
        <div></div>
    @endif

    {{-- Page controls. Solo si hay a dónde ir: con una única página los
         botones sobran, pero el recuento de arriba sigue haciendo falta. --}}
    @if(method_exists($paginator, 'hasPages') ? $paginator->hasPages() : true)
    <nav class="flex items-center gap-1" aria-label="Paginación">
        {{-- Previous --}}
        @if($isCursor)
            @if($paginator->previousCursor())
                <button
                    type="button"
                    wire:click="setCursor(@js($paginator->previousCursor()->encode()))"
                    class="{{ $btnBase }} {{ $btnNormal }}"
                    aria-label="Anterior"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </button>
            @else
                <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true" aria-label="Anterior">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                </span>
            @endif

            @if($paginator->nextCursor())
                <button
                    type="button"
                    wire:click="setCursor(@js($paginator->nextCursor()->encode()))"
                    class="{{ $btnBase }} {{ $btnNormal }}"
                    aria-label="Siguiente"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </button>
            @else
                <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true" aria-label="Siguiente">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </span>
            @endif
        @elseif($onFirstPage)
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true" aria-label="Anterior">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </span>
        @else
            <button
                type="button"
                wire:click="previousPage"
                class="{{ $btnBase }} {{ $btnNormal }}"
                aria-label="Anterior"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </button>
        @endif

        {{-- Page numbers --}}
        @if(! $isCursor)
        @foreach($pages as $page)
            @if($page === '...')
                <span class="{{ $btnBase }} {{ $btnDisabled }}">…</span>
            @elseif($page === $currentPage)
                <span class="{{ $btnBase }} {{ $btnActive }}" aria-current="page">{{ $page }}</span>
            @else
                <button
                    type="button"
                    wire:click="gotoPage({{ $page }})"
                    class="{{ $btnBase }} {{ $btnNormal }}"
                >
                    {{ $page }}
                </button>
            @endif
        @endforeach

        {{-- Next --}}
        @if($hasMorePages)
            <button
                type="button"
                wire:click="nextPage"
                class="{{ $btnBase }} {{ $btnNormal }}"
                aria-label="Siguiente"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </button>
        @else
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true" aria-label="Siguiente">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </span>
        @endif
        @endif
    </nav>
    @endif
</div>
