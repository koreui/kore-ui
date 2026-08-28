@php
    $paginator = $paginator ?? null;
    $showingText = $showingText ?? null;

    if (! $paginator) return;

    // La variante decide cómo se pinta, no qué se calcula: el estado —cursor o
    // no, en qué página se está, a dónde se puede ir— es el mismo para las
    // cuatro y se resuelve aquí una sola vez.
    $variante = $variante ?? config('kore-ui.datatable.paginator', 'default');
    $variante = in_array($variante, ['default', 'simple', 'compact', 'minimal'], true) ? $variante : 'default';

    // Un CursorPaginator no tiene número de página: reenvía las llamadas que no
    // conoce a su colección, así que preguntarle currentPage() reventaba con un
    // «Method Collection::currentPage does not exist» y tumbaba la página entera.
    // pagination_type => 'cursor' es una opción documentada; aquí se atiende.
    $isCursor = $paginator instanceof \Illuminate\Pagination\CursorPaginator;

    $onFirstPage  = $paginator->onFirstPage();
    $hasMorePages = $paginator->hasMorePages();
    $currentPage  = $isCursor ? null : $paginator->currentPage();
    $lastPage     = (! $isCursor && method_exists($paginator, 'lastPage')) ? $paginator->lastPage() : null;

    // A dónde lleva cada flecha, o null si no lleva a ninguna parte. Las cuatro
    // variantes reciben esto ya resuelto y no vuelven a preguntar por el cursor.
    if ($isCursor) {
        $accionAnterior = $paginator->previousCursor()
            ? 'setCursor(' . json_encode($paginator->previousCursor()->encode()) . ')'
            : null;
        $accionSiguiente = $paginator->nextCursor()
            ? 'setCursor(' . json_encode($paginator->nextCursor()->encode()) . ')'
            : null;
    } else {
        $accionAnterior = $onFirstPage ? null : 'previousPage';
        $accionSiguiente = $hasMorePages ? 'nextPage' : null;
    }

    // Ventana de páginas con elipsis. Se calcula con aritmética y no recorriendo
    // 1..$lastPage: con un millón de filas a 25 por página eso eran 40.000
    // vueltas por render para pintar siempre los mismos seis botones.
    //
    // `compact` y `simple` no la usan, así que tampoco se calcula para ellas.
    $pages = [];
    if (in_array($variante, ['default', 'minimal'], true) && $lastPage !== null && $lastPage > 1) {
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
        <nav class="flex items-center gap-1" aria-label="{{ config('kore-ui.datatable.translations.pagination', 'Paginación') }}">
            @include('kore::datatable.paginators.' . $variante, [
                'paginator' => $paginator,
                'isCursor' => $isCursor,
                'currentPage' => $currentPage,
                'lastPage' => $lastPage,
                'pages' => $pages,
                'accionAnterior' => $accionAnterior,
                'accionSiguiente' => $accionSiguiente,
            ])
        </nav>
    @endif
</div>
