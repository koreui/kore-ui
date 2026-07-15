# KoreKanban (clase base)

Componente Livewire base para un tablero Kanban con estado y persistencia. Se extiende como `KoreDataTable`: implementas tres métodos y el board se encarga del arrastre, el layout y la reconciliación.

## Uso

```php
use KoreUi\Kanban\KoreKanban;

class TasksBoard extends KoreKanban
{
    public function columns(): array
    {
        return [
            ['id' => 'todo', 'label' => 'Por hacer'],
            ['id' => 'doing', 'label' => 'En curso', 'color' => 'warning'],
            ['id' => 'done', 'label' => 'Hecho', 'color' => 'success'],
        ];
    }

    public function cards(): array
    {
        return Task::orderBy('position')->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'column' => $t->status,
                'title' => $t->title,
                'description' => $t->description,
            ])
            ->all();
    }

    protected function persistMove(string|int $cardId, string|int $toColumn, int $position): void
    {
        Task::whereKey($cardId)->update([
            'status' => $toColumn,
            'position' => $position,
        ]);
    }
}
```

```blade
<livewire:tasks-board />
```

## Métodos a implementar

| Método | Descripción |
|--------|-------------|
| `columns(): array` | Columnas del board: `[['id' => , 'label' => , 'color' => ?], ...]` |
| `cards(): array` | Tarjetas: `[['id' => , 'column' => , 'title' => , ...], ...]` |
| `persistMove($cardId, $toColumn, $position): void` | Persiste el movimiento de una tarjeta (columna + posición destino) |

## Cómo funciona

- `render()` pinta `kore::kanban.index`, que reutiliza el componente anónimo `<x-kore::kanban>`.
- En cada drop, el board llama a `moveCard($cardId, $position, $toColumn)`, que delega en tu `persistMove()`.
- Tras persistir, Livewire re-renderiza y `cards()` vuelve a leer el estado actualizado.

## Personalizar la tarjeta

Sobrescribe la vista o pasa datos extra en `cards()` y renderiza un card view propio publicando la vista `kore::components.kanban.card`, o usa el slot del componente anónimo montándolo tú mismo en lugar de la clase base.

## Notas

- El arrastre entre columnas usa un grupo `x-sort` compartido (configurable en `config('kore-ui.kanban.group')`).
- El ancho de columna se controla con `config('kore-ui.kanban.column_width')` (default `18rem`).
- Cuida las colisiones de posición bajo concurrencia: si `position` tiene un índice único, aplica la reordenación dentro de una transacción en `persistMove()`.
