@props([
    'colspan' => 1,
    'text' => null,
    'icon' => null,
])

@php
    $text = $text ?? config('kore-ui.datatable.empty_text', 'No se encontraron resultados');
    $icon = $icon ?? config('kore-ui.datatable.empty_icon', 'inbox');
@endphp

<tr>
    <td colspan="{{ $colspan }}">
        <x-kore::empty-state
            :title="$text"
            :icon="$icon"
        />
    </td>
</tr>
