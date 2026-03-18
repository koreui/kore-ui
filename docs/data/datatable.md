# DataTable

Clase abstracta Livewire para tablas interactivas con sorting multi-columna, busqueda, paginacion y eager loading automatico.

---

## Uso basico

### 1. Crear la clase

```php
<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class UsersTable extends KoreDataTable
{
    public function query(): Builder
    {
        return User::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),

            Column::make('Rol', 'role')
                ->sortable(),

            Column::make('Creado', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('d/m/Y')),
        ];
    }
}
```

### 2. Usar en Blade

```blade
<livewire:users-table />
```

---

## KoreDataTable API

### Metodos abstractos (obligatorios)

| Metodo | Retorno | Descripcion |
|---|---|---|
| `query()` | `Builder` | Query base de Eloquent |
| `columns()` | `Column[]` | Definicion de columnas |

### Metodos opcionales

| Metodo | Descripcion |
|---|---|
| `configure()` | Se ejecuta en `mount()`. Usa para setear defaults como `setDefaultSort()` o `setPaginationType()` |

### Propiedades

| Propiedad | Tipo | Default | Descripcion |
|---|---|---|---|
| `$perPage` | `int` | `config(25)` | Registros por pagina |
| `$search` | `string` | `''` | Termino de busqueda |
| `$sorts` | `array` | `[]` | Columnas ordenadas `['field' => 'asc\|desc']` |
| `$density` | `string` | `config(normal)` | Densidad visual |
| `$emptyText` | `string\|null` | `config` | Texto de estado vacio |
| `$emptyIcon` | `string\|null` | `config` | Icono de estado vacio |

---

## Column API

Cada columna se crea con el metodo estatico `Column::make(label, field)`.

```php
Column::make('Nombre', 'name')
```

Si se omite `field`, se genera automaticamente desde el label en snake_case:

```php
Column::make('Nombre Completo') // field = 'nombre_completo'
```

### Metodos fluidos

| Metodo | Descripcion |
|---|---|
| `sortable(bool)` | Habilita sorting en la columna |
| `sortableAs(string)` | Sorting con campo diferente al field |
| `searchable(bool)` | Habilita busqueda en la columna |
| `searchableAs(string)` | Busqueda con campo diferente al field |
| `searchCallback(Closure)` | Logica de busqueda personalizada |
| `hidden(bool)` | Oculta la columna |
| `hiddenIf(Closure)` | Oculta condicionalmente |
| `width(int)` | Ancho en px |
| `minWidth(int)` | Ancho minimo en px |
| `align(string)` | Alineacion: `left`, `center`, `right` |
| `wrap(bool)` | Permite wrap del texto (default `true`) |
| `html(bool)` | Renderiza como HTML sin escapar |
| `default(mixed)` | Valor por defecto si el campo es null |
| `format(Closure)` | Callback `fn($value, $row) => ...` para formatear |

---

## Sorting

El sorting soporta multiples columnas simultaneas con ciclo `asc → desc → null`:

```php
public function columns(): array
{
    return [
        Column::make('Nombre', 'name')->sortable(),
        Column::make('Email', 'email')->sortable(),
        Column::make('Creado', 'created_at')->sortable(),
    ];
}
```

### Sort por defecto

```php
public function configure(): void
{
    $this->setDefaultSort('created_at', 'desc');
}
```

### Sort con campo personalizado

```php
Column::make('Nombre completo', 'name')
    ->sortableAs('users.name') // util en joins
```

---

## Busqueda

La busqueda aplica OR entre todas las columnas marcadas como `searchable`. Se resetea la paginacion automaticamente al escribir.

```php
Column::make('Nombre', 'name')->searchable(),
Column::make('Email', 'email')->searchable(),
```

### Busqueda en relaciones (dot notation)

```php
Column::make('Empresa', 'company.name')->searchable(),
```

Genera `whereHas('company', fn($q) => $q->where('name', 'like', '%term%'))`.

### Busqueda con campo diferente

```php
Column::make('Nombre', 'full_name')
    ->searchableAs('name') // busca en la columna 'name' de la BD
```

### Busqueda personalizada

```php
Column::make('Nombre', 'name')
    ->searchCallback(function (Builder $query, string $term) {
        $query->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%");
    }),
```

---

## Paginacion

Tres tipos de paginacion disponibles:

| Tipo | Metodo Eloquent | Descripcion |
|---|---|---|
| `standard` | `paginate()` | Con total y numeros de pagina |
| `simple` | `simplePaginate()` | Solo anterior/siguiente |
| `cursor` | `cursorPaginate()` | Eficiente para datasets grandes |

### Configurar tipo

```php
public function configure(): void
{
    $this->setPaginationType('simple');
}
```

### Opciones de per page

Se configuran globalmente en `config/kore-ui.php`:

```php
'per_page_options' => [10, 25, 50, 100],
```

El usuario puede cambiar el valor desde el select en el toolbar.

### Paginacion custom

La vista de paginacion del DataTable incluye:
- Botones anterior/siguiente con iconos SVG
- Numeros de pagina con ellipsis para rangos largos
- Texto "Mostrando X a Y de Z resultados"
- Estilos con tokens kore (primary para pagina activa)

---

## Eager Loading automatico

Si una columna usa dot-notation en su field, el DataTable detecta la relacion y agrega `with()` automaticamente:

```php
Column::make('Empresa', 'company.name'),      // with('company')
Column::make('Ciudad', 'company.city.name'),   // with('company.city')
```

No necesitas agregar `with()` manualmente en `query()`.

---

## Visibilidad de columnas

```php
// Siempre oculta
Column::make('ID', 'id')->hidden(),

// Oculta condicionalmente
Column::make('Admin Notes', 'notes')
    ->hiddenIf(fn () => ! auth()->user()->isAdmin()),
```

---

## HTML en celdas

```php
Column::make('Estado', 'status')
    ->html()
    ->format(fn ($value) => "<span class=\"text-green-500\">{$value}</span>"),
```

---

## Toolbar

El DataTable incluye automaticamente un toolbar con:

- **Buscador** — Input con icono, clearable, debounce configurable (default 300ms)
- **Selector de per page** — Select con opciones configurables

### Atajo de teclado

Presionar `/` enfoca automaticamente el buscador (solo cuando no hay un input enfocado). Esto se maneja via el plugin Alpine `KoreDataTable`.

---

## Loading state

Mientras se procesan requests de Livewire, se muestra un overlay con `<x-kore:loading>` sobre la tabla automaticamente.

---

## Ejemplo completo

```php
<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;

class ProductsTable extends KoreDataTable
{
    public function query(): Builder
    {
        return Product::query();
    }

    public function configure(): void
    {
        $this->setDefaultSort('created_at', 'desc');
        $this->setPaginationType('standard');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->width(80)
                ->align('center'),

            Column::make('Producto', 'name')
                ->sortable()
                ->searchable()
                ->minWidth(200),

            Column::make('Categoria', 'category.name')
                ->sortable()
                ->searchable(),

            Column::make('Precio', 'price')
                ->sortable()
                ->align('right')
                ->format(fn ($value) => '$' . number_format($value, 2)),

            Column::make('Stock', 'stock')
                ->sortable()
                ->align('center')
                ->format(fn ($value) => $value > 0 ? $value : '—'),

            Column::make('Estado', 'is_active')
                ->html()
                ->format(fn ($value) => $value
                    ? '<span class="text-green-600">Activo</span>'
                    : '<span class="text-red-500">Inactivo</span>'
                ),

            Column::make('Creado', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('d/m/Y')),

            Column::make('Notas admin', 'admin_notes')
                ->hiddenIf(fn () => ! auth()->user()?->isAdmin()),
        ];
    }
}
```

```blade
{{-- resources/views/products/index.blade.php --}}
<livewire:products-table />
```

---

## Alpine.js Plugin

El plugin `KoreDataTable` se registra automaticamente y provee:

- `density` — Estado reactivo de densidad
- `densityClasses` — Clases CSS computadas para celdas
- `headerDensityClasses` — Clases CSS computadas para headers
- Atajo `/` para focus en el buscador

No necesitas importar ni registrar nada manualmente.
