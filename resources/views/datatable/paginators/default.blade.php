{{-- El paginador de siempre: flechas, la ventana de páginas con elipsis y la
     actual en una píldora. --}}
@php
    $btnBase = 'inline-flex items-center justify-center min-w-8 h-8 px-2 text-[0.8125rem] tabular-nums rounded-kore-md kore-transition kore-focus';
    // La página actual es un control, no un botón de acción: borde y sombra de
    // campo, no el sólido del primario, que competía con los botones de verdad.
    $btnActive = 'border border-kore-input bg-kore-field text-kore-fg font-semibold shadow-kore-xs';
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
