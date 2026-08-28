{{-- Los mismos números, sin cajas: la página actual se marca con una barra
     debajo en vez de con una píldora de color. Para interfaces donde el
     paginador no debe pesar más que la tabla. --}}
@php
    $btnBase = 'relative inline-flex items-center justify-center min-w-7 px-1 py-1 text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring rounded-kore-sm';
    // La barra va DENTRO de la caja del botón (`bottom-0`, no `-bottom-0.5`):
    // fuera, el pie la recorta y el número activo se queda sin su marca.
    $btnActive = 'font-semibold text-kore-primary after:absolute after:inset-x-1 after:bottom-0 after:h-0.5 after:rounded-full after:bg-kore-primary';
    $btnNormal = 'text-kore-muted-fg hover:text-kore-fg';
    $btnDisabled = 'text-kore-muted-fg/40 cursor-not-allowed';
@endphp

@include('kore::datatable.paginators._nav', [
    'direccion' => 'prev',
    'accion' => $accionAnterior,
    'clases' => $btnBase . ' ' . ($accionAnterior ? $btnNormal : $btnDisabled),
])

@unless($isCursor)
    @foreach($pages as $page)
        @if($page === '...')
            <span class="{{ $btnBase }} {{ $btnDisabled }}">…</span>
        @elseif($page === $currentPage)
            <span class="{{ $btnBase }} {{ $btnActive }}" aria-current="page">{{ $page }}</span>
        @else
            <button type="button" wire:click="gotoPage({{ $page }})" class="{{ $btnBase }} {{ $btnNormal }}">{{ $page }}</button>
        @endif
    @endforeach
@endunless

@include('kore::datatable.paginators._nav', [
    'direccion' => 'next',
    'accion' => $accionSiguiente,
    'clases' => $btnBase . ' ' . ($accionSiguiente ? $btnNormal : $btnDisabled),
])
