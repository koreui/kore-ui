# Repeater

Grupos de campos repetibles: el usuario añade, elimina y reordena filas de un mini-formulario (ítems de factura, variantes de producto, miembros de equipo). Generaliza el `key-value` a un número arbitrario de campos definidos por un schema. Se sincroniza con Livewire como un **array de objetos**.

## Uso básico

```blade
<x-kore::repeater
    wire:model="lineItems"
    label="Conceptos"
    :fields="[
        ['key' => 'product', 'label' => 'Producto', 'type' => 'text'],
        ['key' => 'qty', 'label' => 'Cantidad', 'type' => 'number'],
        ['key' => 'price', 'label' => 'Precio', 'type' => 'number'],
    ]"
/>
```

```php
public array $lineItems = [
    ['product' => 'Licencia', 'qty' => '2', 'price' => '120'],
];
```

## Schema de campos (`fields`)

Cada entrada de `fields` define una columna de la fila:

| Clave | Tipo | Descripción |
|-------|------|-------------|
| `key` | `string` | Nombre de la propiedad en cada objeto de fila (requerido) |
| `label` | `string?` | Etiqueta encima del input |
| `type` | `string` | `text`, `number` o `select` (default `text`) |
| `placeholder` | `string?` | Placeholder del input |
| `options` | `array?` | Para `type => 'select'`: `['value' => 'Label']` |

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `fields` | `array` | `[]` | Schema de campos por fila |
| `label`, `hint`, `name`, `error`, `size`, `required`, `disabled`, `readonly`, `showError` | — | — | Convención de formulario. `readonly` deja las filas a la vista sin editar, reordenar ni borrar, y sigue enviando el valor |
| `min` | `int` | `0` | Filas mínimas (no se puede bajar de aquí) |
| `max` | `int\|null` | `null` | Filas máximas |
| `addLabel` | `string` | `Añadir` | Texto del botón de añadir |
| `reorderable` | `bool` | `false` | Reordenar arrastrando (`x-sort`) |
| `default` | `array` | `[]` | Filas iniciales si el modelo llega vacío |

## Con select y límite

```blade
<x-kore::repeater name="members" label="Equipo" :max="4" :fields="[
    ['key' => 'name', 'label' => 'Nombre', 'type' => 'text'],
    ['key' => 'role', 'label' => 'Rol', 'type' => 'select', 'options' => ['admin' => 'Admin', 'editor' => 'Editor']],
]" />
```

## Alcance y schemas complejos

El motor Alpine (`KoreRepeater`) cubre filas de inputs simples (`text`, `number`, `select`). Para schemas complejos —upload por fila, select async con datos de servidor, validación por fila— usa el patrón **host-driven con Livewire**, que gestiona cada fila como un componente de servidor:

```blade
<div wire:sort="reorderRows">
    @foreach($rows as $i => $row)
        <div wire:key="row-{{ $i }}" wire:sort:item="{{ $i }}" class="...">
            <x-kore::input wire:model="rows.{{ $i }}.name" label="Nombre" />
            <x-kore::select wire:model="rows.{{ $i }}.country" :options="$countries" searchable />
            <x-kore::button wire:click="removeRow({{ $i }})" icon="x" variant="ghost" />
        </div>
    @endforeach
</div>
<x-kore::button wire:click="addRow" label="Añadir" icon="plus" variant="outline" />
```

## Notas de implementación

- Plugin Alpine `KoreRepeater`, misma base de array que `key-value`/`tag-input`: estado en Alpine, sincronizado con `$wire.$set(model, rows)`.
- El contenedor va con `wire:ignore`; cada fila se rastrea con `:key="index"` dentro del `x-for`.
- Las clases de rejilla (`sm:grid-cols-N`) se resuelven con `match()` a strings literales para que Tailwind v4 las capture del source.
