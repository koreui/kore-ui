# Sortable

Lista reordenable por arrastre. Es un wrapper fino sobre `wire:sort` (o `x-sort`), la directiva que Livewire 4 trae de fábrica (con SortableJS embebido) — **no añade ninguna dependencia de JavaScript**. El estado y la persistencia los pone tu componente (host-driven).

## Uso básico (server-driven)

```blade
<x-kore::sortable handler="reorder">
    @foreach($tasks as $task)
        <x-kore::sortable.item :id="$task['id']">
            {{ $task['name'] }}
        </x-kore::sortable.item>
    @endforeach
</x-kore::sortable>
```

```php
// En el componente Livewire
public array $tasks = [...];

public function reorder($item, $position)
{
    // $item = id de la fila movida · $position = índice destino
    $moved = collect($this->tasks)->firstWhere('id', $item);
    $this->tasks = collect($this->tasks)
        ->reject(fn ($t) => $t['id'] == $item)
        ->splice(0, $position)
        ->push($moved)
        ->merge(collect($this->tasks)->reject(fn ($t) => $t['id'] == $item)->splice($position))
        ->values()
        ->all();
}
```

> **`:id` estable es obligatorio en modo server.** Usa el id del modelo, no un valor generado: alimenta a la vez `wire:sort:item` y el `wire:key` que el morphing de Livewire necesita para reconciliar bien las filas.

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `handler` | `string\|null` | `null` | Método Livewire que recibe `($item, $position)` en el drop |
| `mode` | `string` | `server` | `server` (`wire:sort`, round-trip) o `client` (`x-sort`, solo DOM) |
| `group` | `string\|null` | `null` | Nombre de grupo para arrastrar **entre** listas (Kanban/Transfer) |
| `handle` | `bool` | `false` | Muestra un tirador por fila (solo se arrastra desde ahí) |
| `tag` | `string` | `div` | Etiqueta del contenedor (`div`, `ul`, …) |
| `animation` | `int` | `150` | Duración de la animación de SortableJS (ms) |

## Props de item

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `id` | `string` | requerido (server) | Clave estable → `wire:sort:item` y `wire:key` |

## Modo cliente (sin round-trip)

Para reordenar solo en el navegador (sin ir al servidor en cada drop):

```blade
<x-kore::sortable mode="client">
    <x-kore::sortable.item id="1">Uno</x-kore::sortable.item>
    <x-kore::sortable.item id="2">Dos</x-kore::sortable.item>
</x-kore::sortable>
```

## Con tirador

```blade
<x-kore::sortable handler="reorder" :handle="true">
    <x-kore::sortable.item :id="$row['id']">{{ $row['label'] }}</x-kore::sortable.item>
</x-kore::sortable>
```

## Entre listas (grupos)

Dos `sortable` con el mismo `group` permiten arrastrar ítems de una a otra — es la base del Kanban y del Transfer:

```blade
<x-kore::sortable handler="moveTodo" group="tasks">...</x-kore::sortable>
<x-kore::sortable handler="moveDone" group="tasks">...</x-kore::sortable>
```
