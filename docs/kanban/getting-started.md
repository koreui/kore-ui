# Kanban

Tablero de columnas con tarjetas arrastrables dentro y entre columnas (pipelines de CRM, gestión de tareas, flujos de aprobación). Las tarjetas se arrastran con `x-sort` (el plugin de Alpine que Livewire 4 trae embebido, **sin dependencias nuevas**) y el grupo compartido permite mover una tarjeta de una columna a otra.

Hay dos formas de usarlo, igual que el DataTable:

1. **Clase base `KoreKanban`** — extiéndela para un board con estado y persistencia (recomendado). Ver [kore-kanban.md](kore-kanban.md).
2. **Componente anónimo `<x-kore::kanban>`** — para un board data-driven donde el estado lo maneja tu propio componente.

## Componente anónimo (data-driven)

```blade
<x-kore::kanban
    handler="moveCard"
    :columns="[
        ['id' => 'todo', 'label' => 'Por hacer'],
        ['id' => 'doing', 'label' => 'En curso', 'color' => 'warning'],
        ['id' => 'done', 'label' => 'Hecho', 'color' => 'success'],
    ]"
    :cards="[
        ['id' => 1, 'column' => 'todo', 'title' => 'Diseñar API'],
        ['id' => 2, 'column' => 'doing', 'title' => 'Migrar tablas'],
    ]"
/>
```

```php
// En tu componente Livewire
public function moveCard($cardId, $position, $toColumn)
{
    // $cardId = tarjeta movida · $position = índice destino · $toColumn = columna destino
    Task::whereKey($cardId)->update(['status' => $toColumn, 'position' => $position]);
}
```

## Props del componente anónimo

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `columns` | `array` | `[]` | `[['id' => , 'label' => , 'color' => ?], ...]` |
| `cards` | `array\|Collection` | `[]` | `[['id' => , 'column' => , 'title' => , ...], ...]` |
| `handler` | `string` | `moveCard` | Método Livewire invocado en el drop: `($item, $position, $toColumn)` |
| `group` | `string` | config `kanban` | Grupo `x-sort` compartido (permite arrastrar entre columnas) |
| `animation` | `int` | `150` | Animación de arrastre (ms) |

Los colores de columna aceptan tokens semánticos: `primary`, `success`, `warning`, `destructive`, `info`, `secondary`.

## Tarjeta

Cada tarjeta (`<x-kore::kanban.card>`) muestra `title`, `description` y un `badge` opcional (`badge`, `badgeColor`). Para contenido totalmente personalizado, usa el slot:

```blade
<x-kore::kanban.card :card="$card">
    <div class="flex items-center gap-2">
        <x-kore::avatar :name="$card['assignee']" size="sm" />
        <span class="text-sm">{{ $card['title'] }}</span>
    </div>
</x-kore::kanban.card>
```

## El handler

`moveCard($cardId, $position, $toColumn)` se dispara **en cada drop**, tanto al reordenar dentro de una columna como al mover entre columnas. `$toColumn` es siempre la columna destino (se conoce al renderizar). Persiste el cambio y vuelve a exponer las tarjetas actualizadas en el siguiente render.

> **`wire:key` por tarjeta.** El board pone `wire:key="kore-card-{id}"` en cada tarjeta para que el morphing de Livewire reconcilie bien tras un drop.
