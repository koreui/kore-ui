{{-- El paginador de siempre: flechas, la ventana de páginas con elipsis y la
     actual en una píldora. --}}
@php
    $btnBase = 'inline-flex items-center justify-center size-8 text-sm rounded-kore-md transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring';
    $btnActive = 'bg-kore-primary text-kore-primary-fg font-medium';
    $btnNormal = 'text-kore-fg hover:bg-kore-muted';
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
