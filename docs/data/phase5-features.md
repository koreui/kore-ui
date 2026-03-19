# DataTable — Fase 5: Features Avanzadas

## Events System

El DataTable despacha eventos de Laravel cuando ocurren acciones clave. Útil para auditoría, logging o side effects.

### Eventos disponibles

```php
use KoreUi\DataTable\Events\RowUpdated;
use KoreUi\DataTable\Events\BulkActionExecuted;
use KoreUi\DataTable\Events\FilterApplied;
```

| Evento | Propiedades | Se despacha cuando |
|--------|-------------|-------------------|
| `RowUpdated` | `$tableClass`, `$rowId`, `$field`, `$value`, `$oldValue` | Se edita una celda (inline editing) |
| `BulkActionExecuted` | `$tableClass`, `$action`, `$ids`, `$count` | Se ejecuta una bulk action |
| `FilterApplied` | `$tableClass`, `$filters`, `$search` | Se cambia un filtro |

### Ejemplo: escuchar eventos

```php
// En un EventServiceProvider o Listener
Event::listen(RowUpdated::class, function (RowUpdated $event) {
    Log::info("Row {$event->rowId} updated: {$event->field} from {$event->oldValue} to {$event->value}");
});
```

---

## Column Copyable

Permite copiar el valor de una celda al portapapeles con un click.

```php
Column::make('Email', 'email')->copyable()
```

- Muestra un ícono de copiar en hover
- Muestra un check verde como feedback tras copiar
- Usa `navigator.clipboard` con fallback para contextos no-seguros
- **No se aplica en columnas editables** (el click ya tiene función)

---

## Column Clickable

Convierte el valor de una celda en un enlace clickable.

```php
// Con URL estática
Column::make('Ciudad', 'city')->clickable('/cities')

// Con callback dinámico
Column::make('Ciudad', 'city')->clickable(fn ($row) => "/users/{$row->id}")

// Abrir en nueva pestaña
Column::make('URL', 'url')->clickable('/link', newTab: true)
```

---

## Column Pinning

Fija columnas a la izquierda o derecha para que permanezcan visibles al hacer scroll horizontal.

```php
Column::make('ID', 'id')->pinned('left')->width(80)
Column::make('Acciones', 'id')->pinned('right')->width(100)
```

**Importante:** Las columnas pinned requieren `->width()` definido para calcular los offsets correctamente.

- Muestra una sombra sutil en el borde de la última columna pinned-left y primera pinned-right
- Se aplica en `<thead>`, `<tbody>` y `<tfoot>`
- Se ignora en modos responsive card/collapse (solo aplica en scroll)

---

## Deferred Loading

Muestra un skeleton mientras los datos se cargan, ideal para tablas pesadas.

```php
public function configure(): void
{
    $this->setDeferredLoading();
}
```

### Configuración global

```php
// config/kore-ui.php
'datatable' => [
    'deferred_loading' => false,
],
```

- Usa `wire:init="loadData"` para cargar datos tras el render inicial
- Muestra N filas de skeleton (min de `perPage` y 10)
- Usa el componente `<x-kore::skeleton>` del sistema de UI
- Protege `getRowIds()` cuando `$rows` es null

---

## API Reference

### Column methods (Phase 5)

| Método | Descripción |
|--------|-------------|
| `copyable(bool $copyable = true)` | Hace la celda copiable al portapapeles |
| `clickable(Closure\|string\|null $url, bool $newTab = false)` | Convierte la celda en un enlace |
| `pinned(?string $side = 'left')` | Fija la columna a 'left' o 'right' |

### KoreDataTable methods (Phase 5)

| Método | Descripción |
|--------|-------------|
| `setDeferredLoading(bool $enabled = true)` | Activa/desactiva deferred loading |

### Events

| Evento | Cuándo |
|--------|--------|
| `RowUpdated` | Después de una edición inline exitosa |
| `BulkActionExecuted` | Después de ejecutar una bulk action |
| `FilterApplied` | Cuando el usuario cambia filtros |
