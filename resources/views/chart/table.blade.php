@php $table = $plot->table(); @endphp

{{-- ⚠️ El `sr-only` va en un <div>, NO en la <table>.

     `sr-only` esconde la caja con `width: 1px` + `overflow: hidden` + `clip-path`. Sobre una
     tabla, el `width: 1px` **se ignora**: el algoritmo de layout de tablas acota el ancho por
     abajo al min-content del contenido. Medido: 321 px de ancho en un móvil de 375. El
     `clip-path` sí aplicaba —así que la tabla no se veía y nadie se enteró— pero la caja seguía
     ocupando, y con ella llegaba el scroll horizontal en toda la página.

     Un <div> es una caja de bloque normal y sí obedece. --}}
<div class="sr-only">
<table>
    <caption>{{ $label ?? 'Datos del gráfico' }}</caption>
    <thead>
        <tr>
            <th scope="col">{{ __('Categoría') }}</th>
            @foreach($table['headers'] as $header)
                <th scope="col">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($table['rows'] as $row)
            <tr>
                <th scope="row">{{ $row['label'] }}</th>
                @foreach($row['values'] as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
</div>
