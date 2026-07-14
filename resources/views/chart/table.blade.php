@php $table = $plot->table(); @endphp

<table class="sr-only">
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
