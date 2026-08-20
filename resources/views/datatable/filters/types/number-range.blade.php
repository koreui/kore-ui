@php $props = $filter->getComponentProps(); @endphp
{{-- Cada campo en su propio contenedor flex-1: sin él los dos se quedaban en su
     ancho mínimo (41 px en el drawer) y el placeholder salía cortado.
     `min-w-0` es necesario para que un hijo de flex pueda encogerse por debajo
     de su contenido. Y sin controles +/-: en un filtro no aportan y se comían
     el poco ancho que hay. --}}
<div class="flex items-center gap-2">
    <div class="flex-1 min-w-0">
        <x-kore::number
            size="sm"
            :id="$fieldId ?? null"
            :controls="false"
            placeholder="Min"
            :min="$props['min']"
            :max="$props['max']"
            :step="$props['step']"
            wire:model.live.debounce.500ms="filters.{{ $filter->getKey() }}.min"
        />
    </div>

    <span class="text-kore-muted-fg text-sm shrink-0">—</span>

    <div class="flex-1 min-w-0">
        <x-kore::number
            size="sm"
            :id="isset($fieldId) ? $fieldId . '-max' : null"
            :controls="false"
            placeholder="Max"
            :min="$props['min']"
            :max="$props['max']"
            :step="$props['step']"
            wire:model.live.debounce.500ms="filters.{{ $filter->getKey() }}.max"
        />
    </div>
</div>
