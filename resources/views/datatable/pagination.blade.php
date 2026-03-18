@php
    $paginator = $paginator ?? null;
    $showingText = $showingText ?? null;

    if (! $paginator) return;

    $onFirstPage = $paginator->onFirstPage();
    $hasMorePages = $paginator->hasMorePages();
    $currentPage = $paginator->currentPage();
    $lastPage = method_exists($paginator, 'lastPage') ? $paginator->lastPage() : null;

    // Build page numbers with ellipsis
    $pages = [];
    if ($lastPage !== null && $lastPage > 1) {
        $window = 2; // pages around current

        for ($i = 1; $i <= $lastPage; $i++) {
            if ($i === 1 || $i === $lastPage || abs($i - $currentPage) <= $window) {
                $pages[] = $i;
            } elseif (end($pages) !== '...') {
                $pages[] = '...';
            }
        }
    }

    $btnBase = 'inline-flex items-center justify-center size-8 text-sm rounded-kore-md transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring';
    $btnActive = 'bg-kore-primary text-kore-primary-fg font-medium';
    $btnNormal = 'text-kore-fg hover:bg-kore-muted';
    $btnDisabled = 'text-kore-muted-fg/40 cursor-not-allowed';
@endphp

<div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-kore-border">
    {{-- Showing text --}}
    @if($showingText)
        <div class="text-sm text-kore-muted-fg">
            {{ $showingText }}
        </div>
    @else
        <div></div>
    @endif

    {{-- Page controls --}}
    <nav class="flex items-center gap-1" aria-label="Paginación">
        {{-- Previous --}}
        @if($onFirstPage)
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true">
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
            <span class="{{ $btnBase }} {{ $btnDisabled }}" aria-disabled="true">
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
            </span>
        @endif
    </nav>
</div>
