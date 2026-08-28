{{-- Un control de navegación del paginador: la flecha anterior/siguiente, o su
     versión con texto.

     Existe para no repetir cuatro veces el mismo SVG y la misma pareja
     botón/span: cuando no hay a dónde ir NO se pinta un `<button disabled>`
     sino un `<span aria-disabled>`, porque un botón deshabilitado sale del
     recorrido del tabulador y el usuario que navega con teclado ve desaparecer
     el control en vez de encontrarlo apagado. --}}
@props([
    'direccion' => 'next',   // prev | next
    'accion' => null,        // la llamada wire:click, o null si no hay a dónde ir
    'etiqueta' => null,
    'clases' => '',
    'clasesApagado' => '',
    'texto' => null,         // con texto en vez de flecha (variante `simple`)
])

@php
    $etiqueta = $etiqueta ?? config(
        'kore-ui.datatable.translations.' . ($direccion === 'prev' ? 'previous' : 'next'),
        $direccion === 'prev' ? 'Anterior' : 'Siguiente'
    );

    $trazo = $direccion === 'prev' ? 'M15.75 19.5 8.25 12l7.5-7.5' : 'm8.25 4.5 7.5 7.5-7.5 7.5';
@endphp

@if($accion)
    <button type="button" wire:click="{{ $accion }}" class="{{ $clases }}" @if(! $texto) aria-label="{{ $etiqueta }}" @endif>
        @if($texto)
            {{ $texto }}
        @else
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $trazo }}"/></svg>
        @endif
    </button>
@else
    <span class="{{ $clases }} {{ $clasesApagado }}" aria-disabled="true" @if(! $texto) aria-label="{{ $etiqueta }}" @endif>
        @if($texto)
            {{ $texto }}
        @else
            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $trazo }}"/></svg>
        @endif
    </span>
@endif
