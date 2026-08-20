{{-- Segunda línea de la celda. Se incluye a los dos lados del contenido y cada
     lado comprueba su posición, para no duplicar el bloque en las cinco ramas
     de renderizado que tiene una celda (editable, copyable, clickable, tipo
     propio y texto plano). --}}
@php
    $koreDescription = $column->hasDescription() && $column->getDescriptionPosition() === ($slot ?? 'below')
        ? $column->getDescription($row)
        : null;
@endphp

@if($koreDescription !== null)
    <div class="text-xs text-kore-muted-fg {{ ($slot ?? 'below') === 'below' ? 'mt-0.5' : 'mb-0.5' }}">
        {{ $koreDescription }}
    </div>
@endif
