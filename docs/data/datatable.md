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
| `$tableName` | `string` | `''` | Identificador para aislar los parametros de URL cuando hay varias tablas en la misma pagina (ver [Persistencia en URL](#persistencia-en-url-y-multiples-tablas)) |
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

### Sort por defecto con tiebreaker

Cuando varios registros comparten el mismo valor en la columna de sort, el orden entre ellos es no determinístico a nivel SQL. Usa `addDefaultSort()` para agregar una columna secundaria que rompa los empates:

```php
public function configure(): void
{
    $this->setDefaultSort('estatus', 'desc')
         ->addDefaultSort('id', 'asc');
}
```

Esto genera `ORDER BY estatus DESC, id ASC`. Pueden encadenarse múltiples `addDefaultSort()`.

> El sort secundario solo aplica cuando no hay sort manual activo. Si el usuario hace clic en una columna para ordenar, toma control completo. Al quitar el sort manual, vuelve al default completo (incluyendo el tiebreaker).

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

## Persistencia en URL y multiples tablas

### Que se persiste en la URL

| Parametro | Cuando |
|---|---|
| `page` | Siempre |
| `per_page` | Siempre |
| `q` (busqueda) | Solo con query string activo |
| `sort` | Solo con query string activo |
| `filter` | Solo con query string activo |

`page` y `per_page` se mantienen siempre para que al recargar la pagina sigas en la misma pagina y con el mismo numero de registros. La busqueda, el orden y los filtros se persisten unicamente si activas el query string:

```php
public function configure(): void
{
    $this->setQueryStringEnabled();   // o config('kore-ui.datatable.query_string')
}
```

### Varias tablas en la misma pagina (`table-name`)

Si colocas dos o mas DataTables en una misma vista, **por defecto compartirian los mismos parametros de URL** (`page`, `per_page`, `q`, ...), por lo que paginar o filtrar una afectaria a la otra. Para aislarlas, asigna a cada una un identificador unico con `table-name`. Todos sus parametros de URL quedan prefijados (`users_page`, `orders_page`, ...):

```blade
<livewire:users-table  table-name="users" />
<livewire:orders-table table-name="orders" />
```

```
?users_page=2&users_per_page=10&orders_page=1&orders_q=ana
```

Tambien puedes fijarlo a nivel de subclase (util si esa tabla casi siempre convive con otras):

```php
class UsersTable extends KoreDataTable
{
    public string $tableName = 'users';
}
```

Con **una sola tabla** no necesitas `table-name`: los parametros quedan sin prefijo (`page`, `per_page`, ...) y las URLs siguen limpias.

> El prefijo se sanea a un slug seguro para URL: `table-name="Ventas 2024"` produce `ventas_2024_page`, etc.

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

Mientras se procesan requests de Livewire, se muestra un overlay con `<x-kore::loading>` sobre la tabla automaticamente.

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

## Filtros

El DataTable soporta filtros reutilizando los componentes form de kore-ui. Los filtros se definen en el metodo `filters()`.

### Definir filtros

```php
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\Filters\TextFilter;
use KoreUi\DataTable\Filters\BooleanFilter;

public function filters(): array
{
    return [
        TextFilter::make('Nombre', 'name')
            ->placeholder('Buscar por nombre...'),

        SelectFilter::make('Ciudad', 'city')
            ->options($cities)
            ->optionLabel('label')
            ->optionValue('value')
            ->placeholder('Todas las ciudades')
            ->searchable(),

        BooleanFilter::make('Activo', 'is_active')
            ->trueLabel('Activos')
            ->falseLabel('Inactivos'),
    ];
}
```

### Tipos de filtro

| Filtro | SQL generado | Componente UI |
|---|---|---|
| `TextFilter` | `WHERE col LIKE %val%` | `<x-kore::input>` |
| `SelectFilter` | `WHERE col = val` | `<x-kore::select>` |
| `MultiSelectFilter` | `WHERE col IN (...)` | `<x-kore::select multiple>` |
| `BooleanFilter` | `WHERE col = bool` | `<x-kore::select>` (Todos/Si/No) |
| `NumberFilter` | `WHERE col {op} val` | `<x-kore::number>` |
| `NumberRangeFilter` | `WHERE col >= min AND col <= max` | Dos `<x-kore::number>` |
| `DateFilter` | `whereDate(col, val)` | `<x-kore::datepicker>` |
| `DateRangeFilter` | `whereDate BETWEEN` | `<x-kore::datepicker mode="range">` |

### Filter API (metodos comunes)

Todos los filtros heredan de `Filter` y comparten estos metodos fluidos:

| Metodo | Descripcion |
|---|---|
| `make(label, column)` | Crea el filtro. Si se omite `column`, se genera desde label en snake_case |
| `key(string)` | Key unico para el array `$filters` (default: column name) |
| `default(mixed)` | Valor por defecto al montar el componente |
| `placeholder(string)` | Texto placeholder en el input |
| `position(int)` | Orden de aparicion (menor = primero) |
| `hidden(bool)` | Oculta el filtro de la UI |
| `hiddenIf(Closure)` | Oculta condicionalmente |
| `pill(Closure)` | Callback para texto del pill: `fn($value) => "Label: $value"` |
| `callback(Closure)` | Logica de query personalizada: `fn(Builder $query, $value) => ...` |
| `sanitize(mixed)` | Normaliza el valor del cliente antes de la consulta (ver abajo) |

### Filtros sobre relaciones

La dot-notation se resuelve como `whereHas`, igual que la busqueda global:

```php
TextFilter::make('Empresa', 'company.name'),      // whereHas('company', …)
SelectFilter::make('Pais', 'company.country.code'),  // relaciones anidadas
```

Solo se trata como relacion si el modelo la declara. `TextFilter::make('X', 'users.name')` con un join hecho en `query()` se sigue interpretando como columna cualificada por tabla.

Los filtros de rango (`NumberRangeFilter`, `DateRangeFilter`) aplican sus dos condiciones dentro del **mismo** `whereHas`: "tiene una fila entre X e Y", no "tiene alguna >= X y alguna <= Y".

### Saneado de valores (`sanitize`)

`$filters` es una propiedad publica de Livewire: quien esta en el navegador controla su contenido, tanto en forma como en tipo. Antes de tocar la consulta, cada filtro pasa su valor por `sanitize()`, y lo que devuelve `null` simplemente no se aplica.

| Filtro | Que acepta |
|---|---|
| `TextFilter` | Escalar, convertido a string. Los comodines `%` y `_` se escapan |
| `SelectFilter` | Escalar; si hay `options()` declaradas, solo valores de esa lista |
| `MultiSelectFilter` | Array de escalares, filtrado por `options()` y recortado a `max()` |
| `NumberFilter` / `NumberRangeFilter` | Solo numericos (en PostgreSQL comparar una columna numerica con texto aborta la consulta) |
| `DateFilter` / `DateRangeFilter` | Fechas parseables, normalizadas a `Y-m-d` |
| `BooleanFilter` | `filter_var`, para que la cadena `'false'` no sea `true` |

Un valor rechazado no se aplica **ni se cuenta ni aparece como pill**: la interfaz nunca anuncia un filtro que la consulta no esta aplicando.

> Un filtro propio que herede de `Filter` recibe una implementacion por defecto que solo comprueba la forma (escalares y arrays planos). Si tu filtro acepta una estructura distinta, declara su `sanitize()`.

### SelectFilter API

| Metodo | Descripcion |
|---|---|
| `options(array)` | Array de opciones |
| `optionLabel(string)` | Key para el label de cada opcion |
| `optionValue(string)` | Key para el value de cada opcion |
| `searchable(bool)` | Habilita busqueda en el dropdown |

### MultiSelectFilter API

Mismos metodos que `SelectFilter`, mas:

| Metodo | Descripcion |
|---|---|
| `max(int)` | Maximo de opciones seleccionables |

### NumberFilter API

| Metodo | Descripcion |
|---|---|
| `operator(string)` | Operador SQL: `=`, `>=`, `<=`, `>`, `<` |
| `min(int)` | Valor minimo |
| `max(int)` | Valor maximo |
| `step(int)` | Incremento |

### Key personalizado

Cuando multiples filtros apuntan a la misma columna, usa `key()` para evitar conflictos:

```php
NumberFilter::make('Edad minima', 'age')
    ->key('min_age')
    ->operator('>='),

NumberRangeFilter::make('Rango edad', 'age')
    ->key('age_range'),
```

### Query personalizada

```php
SelectFilter::make('Estado', 'status')
    ->callback(function (Builder $query, $value) {
        $query->where('status', $value)
              ->where('verified', true);
    }),
```

---

## Layouts de filtro

Cuatro layouts disponibles para mostrar los filtros. Se configuran en `configure()`.

### Popover (default)

Filtros en un dropdown flotante desde el boton "Filtros".

```php
public function configure(): void
{
    $this->setFilterLayout('popover');
}
```

### Slide Down

Panel que se despliega debajo del toolbar con transicion suave. Opcionalmente expandido por defecto.

```php
public function configure(): void
{
    $this->setFilterLayout('slide-down');
    $this->setFiltersExpanded(true); // abierto por defecto
}
```

### Inline

Filtros siempre visibles en fila debajo del toolbar. Sin boton ni toggle. Ideal para pocos filtros.

```php
public function configure(): void
{
    $this->setFilterLayout('inline');
}
```

### Drawer

Panel lateral deslizable desde la derecha. Ideal cuando hay muchos filtros.

```php
public function configure(): void
{
    $this->setFilterLayout('drawer');
}
```

### Configuracion global

```php
// config/kore-ui.php
'datatable' => [
    'filter_layout' => 'popover', // popover | slide-down | inline | drawer
],
```

---

## Filter pills

Cuando hay filtros activos, se muestran automaticamente como pills debajo del toolbar:

- Cada pill muestra el texto del filtro (personalizable con `->pill(fn)`)
- Boton X para quitar filtro individual (`resetFilter('key')`)
- Link "Limpiar filtros" para quitar todos (`resetAllFilters()`)

---

## Seleccion de filas

La seleccion de filas se habilita automaticamente cuando hay bulk actions definidas. Los checkboxes aparecen en la primera columna.

### Configurar

```php
public function configure(): void
{
    $this->setPrimaryKey('uuid');       // default: 'id'
    $this->setSelectionEnabled(false);  // deshabilita checkboxes
}
```

### Comportamiento

- **Checkbox header** — Selecciona/deselecciona todos los registros de la pagina actual
- **Estado indeterminado** — El checkbox header muestra estado indeterminado si hay seleccion parcial
- **Persistencia entre paginas** — La seleccion vive en el servidor (snapshot Livewire), por lo que se mantiene al paginar, ordenar y al cambiar `per_page`. Cada tabla tiene su propia seleccion independiente.
- **Seleccionar todo lo que coincide** — Cuando la pagina esta completa aparece "Seleccionar los N" para actuar sobre todos los registros que cumplen la busqueda/filtros, no solo los visibles.
- **Shift-click** — Click con Shift selecciona un rango de filas contiguas.
- **Clear automatico** — Al ejecutar una bulk action, la seleccion se limpia automaticamente

---

## Bulk Actions

Acciones masivas sobre las filas seleccionadas. Se muestran en un dropdown cuando hay filas seleccionadas.

### Definir acciones

```php
use KoreUi\DataTable\Actions\BulkAction;

public function bulkActions(): array
{
    return [
        BulkAction::make('activate', 'Activar seleccionados')
            ->icon('check-circle')
            ->color('success'),

        BulkAction::make('delete', 'Eliminar seleccionados')
            ->icon('trash-2')
            ->color('destructive')
            ->confirm(
                '¿Eliminar :count registro(s)?',
                'Esta accion no se puede deshacer.'
            )
            ->separator(),
    ];
}
```

### Implementar la accion

El metodo debe coincidir con el identificador del `BulkAction::make()`:

```php
public function activate(array $ids): void
{
    User::whereIn('id', $ids)->update(['is_active' => true]);
    $this->toast()->success(count($ids) . ' usuario(s) activados.')->send();
}

public function delete(array $ids): void
{
    User::whereIn('id', $ids)->delete();
    $this->toast()->success(count($ids) . ' usuario(s) eliminados.')->send();
}
```

### BulkAction API

| Metodo | Descripcion |
|---|---|
| `make(id, label)` | Crea la accion con identificador y etiqueta |
| `icon(string)` | Icono Lucide para el dropdown |
| `color(string)` | Color semantico: `primary`, `success`, `warning`, `destructive` |
| `confirm(msg, desc)` | Muestra dialogo de confirmacion. Soporta `:count` placeholder |
| `separator()` | Agrega separador visual antes de la accion |
| `hidden(bool\|Closure)` | Oculta la accion de la interfaz (**no es autorizacion**) |
| `authorize(Closure)` | Permiso comprobado en el servidor antes de ejecutar |
| `hiddenWhenEmpty()` | Oculta cuando no hay seleccion |

#### `hidden()` no es `authorize()`

Todo metodo publico de un componente Livewire es invocable desde el navegador. Esconder un boton no impide llamar a la accion desde la consola:

```php
// Se esconde Y se protege. Lo segundo es lo que cuenta.
BulkAction::make('deleteAll', 'Eliminar todo')
    ->hidden(fn () => ! auth()->user()->isAdmin())
    ->authorize(fn () => auth()->user()->isAdmin()),
```

Una accion oculta ya no se resuelve al ejecutar (`findBulkAction()` descarta las ocultas), asi que `hidden()` protege de hecho — pero solo si la condicion no depende de la fila. `authorize()` es la comprobacion explicita, se evalua en el servidor justo antes de tocar datos y responde `403` si falla. Lo mismo aplica a `RowAction`: `hidden(fn ($row) => …)` decide que se pinta; la autorizacion va en el metodo que recibe la llamada.

#### Conjuntos grandes

`getAllMatchingIds()` materializa un array con todas las claves primarias: sobre dos millones de filas son dos millones de strings en memoria antes de hacer nada. Para acciones que puedan tocar conjuntos asi hay dos herramientas que no materializan:

```php
public function archivar(array $ids): void
{
    if ($this->isActingOnAllMatching()) {
        // Una sola consulta, sin cargar nada.
        $this->matchingQuery()->update(['archived_at' => now()]);

        return;
    }

    Registro::whereIn('id', $ids)->update(['archived_at' => now()]);
}
```

```php
public function notificar(array $ids): void
{
    // chunkById: seguro aunque el callback modifique las filas que recorre.
    $this->eachMatching(fn ($registros) => Notificacion::queue($registros), chunkSize: 500);
}
```

> Una accion masiva se ejecuta en el request, de forma sincrona. Diez mil borrados son un timeout: para eso, despacha un job desde el metodo de la accion.

#### IDs y alcance

Los identificadores que llegan del cliente se recortan a los que la `query()` de la tabla autoriza antes de ejecutar la accion, con un tope de `$bulkSelectionLimit` (5.000 por defecto, redefinible en la tabla). El metodo de la accion recibe siempre **strings**.

En modo "seleccionar todo lo que coincide" los IDs no viajan al navegador: se resuelven en el servidor al confirmar.

---

## Segunda linea en la celda

El patron «nombre arriba, correo en gris debajo» que aparece en casi toda tabla de administracion, y que antes obligaba a bajar a un `ComponentColumn`:

```php
Column::make('Usuario', 'name')
    ->description(fn ($row) => $row->email),

// Tambien acepta el nombre de un campo
Column::make('Pedido', 'reference')
    ->description('customer.name'),

// O encima del valor, como etiqueta pequena
Column::make('Importe', 'total')
    ->description('currency', 'above'),
```

Se renderiza en los tres modos (tabla, `card` y `collapse`). Una descripcion que devuelva cadena vacia o `null` no pinta nada.

---

## Menu por cabecera de columna

Cada cabecera lleva un menu con **ordenar ascendente/descendente**, **fijar a la izquierda/derecha** y **ocultar columna**. Es lo que convierte `pinned()` y el selector de columnas en algo del usuario final y no solo de quien escribe la tabla.

```php
public function configure(): void
{
    $this->setColumnMenuEnabled(false);   // por defecto: activado
}
```

Tambien con `datatable.column_menu` en la configuracion global.

Los fijados que elige el usuario se guardan en sesion y **mandan sobre los que declara la tabla**: una columna con `->pinned('left')` puede soltarse desde el menu. `resetColumnPins()` devuelve todas a lo que dice la definicion.

| Metodo | Descripcion |
|---|---|
| `setSort(field, direction)` | Ordena en una direccion concreta (a diferencia de `sortBy()`, que rota) |
| `toggleColumnPin(field, side)` | Fija o suelta; elegir el mismo lado dos veces la suelta |
| `resetColumnPins()` | Devuelve el fijado declarado por la tabla |
| `effectivePin(field)` | Fijado elegido por el usuario, o `null` si no ha tocado esa columna |

---

## Vistas guardadas

Un `FilterPreset` lo declara quien escribe la tabla y es fijo. Una **vista** la crea quien la usa: guarda la combinacion de filtros, orden, busqueda, `perPage`, columnas visibles y columnas fijadas con la que esta trabajando, y vuelve a ella cuando quiere.

```php
public function configure(): void
{
    $this->setSavedViewsEnabled();
}
```

O `datatable.saved_views` en la configuracion global. Aparece un boton **Vistas** en el toolbar.

### Donde se guardan

Por defecto en la **sesion**: cero instalacion, ambito por usuario de regalo, y las vistas se pierden al cerrar sesion.

Para persistencia real se implementa el contrato y se enlaza en el contenedor. La libreria no trae modelo ni migracion a proposito: nadie deberia tener que migrar su base de datos por usar un DataTable.

```php
use KoreUi\DataTable\Views\Contracts\SavedViewStore;
use KoreUi\DataTable\Views\SavedView;

class VistasEnBaseDeDatos implements SavedViewStore
{
    public function all(string $tableKey): array
    {
        return VistaGuardada::where('user_id', auth()->id())
            ->where('table_key', $tableKey)
            ->get()
            ->mapWithKeys(fn ($fila) => [$fila->uuid => SavedView::fromArray($fila->payload)])
            ->all();
    }

    public function find(string $tableKey, string $id): ?SavedView { /* … */ }
    public function save(string $tableKey, SavedView $view): SavedView { /* … */ }
    public function delete(string $tableKey, string $id): void { /* … */ }
}
```

```php
// AppServiceProvider::register()
$this->app->bind(SavedViewStore::class, VistasEnBaseDeDatos::class);
```

`$tableKey` identifica la tabla (clase mas nombre de instancia, para que dos tablas de la misma clase en una pagina no compartan vistas). **El ambito por usuario es cosa de la implementacion**, porque solo ella sabe que es un usuario en esa aplicacion — el driver de sesion lo consigue por serlo.

### Comportamiento

- Guardar deja la vista activa.
- Editar filtros, busqueda u orden a mano **suelta** la vista: deja de describir lo que se esta viendo, igual que pasa con los presets.
- Pulsar la vista activa sale de ella.
- Activar una vista desactiva el preset, y al reves.

---

## Exportacion

```php
public function configure(): void
{
    $this->setExportEnabled()
         ->setExportFormats(['csv'])
         ->setExportFileName('usuarios.csv')
         ->setExportMaxRows(50000);
}
```

Tambien se activa globalmente en `config/kore-ui.php` (`datatable.export.enabled`, `.formats`, `.max_rows`); lo que ponga `configure()` gana.

### Formatos propios

```php
$this->registerExporter('xlsx', XlsxExporter::class)
     ->setExportFormats(['csv', 'xlsx']);
```

El exporter debe implementar `KoreUi\DataTable\Exports\Contracts\Exporter`. Pedir un formato sin registrar lanza `InvalidArgumentException`: antes se caia silenciosamente a CSV, asi que un boton "XLSX" descargaba un CSV con extension `.csv` sin decir nada.

Cuando el conjunto supera `exportMaxRows`, el archivo se corta y se avisa con un toast. Sobreescribe `notifyExportTruncated()` para cambiar el aviso.

---

## Abrir un overlay desde la tabla

Un boton que abre un modal puede costar uno o dos viajes al servidor, y la
diferencia se nota:

```php
// Dos viajes. El primero ejecuta el metodo Y repinta la tabla entera —consulta,
// columnas, filtros, paginacion— aunque nada de ella haya cambiado. El segundo
// es el del overlay manager montando el modal.
public function nuevoRegistro(): void
{
    $this->dispatch('kore:open', name: 'modals.mi-modal');
}
```

```blade
{{-- Un solo viaje: el evento sale del navegador y solo viaja la peticion del
     overlay manager. --}}
<x-kore::button x-on:click="$dispatch('kore:open', { name: 'modals.mi-modal' })">
    Nuevo registro
</x-kore::button>
```

Si el metodo tiene que existir en el servidor —por ejemplo para un `authorize()`
previo—, al menos evita el repintado:

```php
public function nuevoRegistro(): void
{
    $this->authorize('registros.create');
    $this->dispatch('kore:open', name: 'modals.mi-modal');

    $this->skipRender();   // abrir un modal no cambia nada de la tabla
}
```

> `RowAction::openOverlay()` ya usa la via del cliente: despacha `kore:open`
> desde el navegador, sin pasar por el servidor.

---

## Slots (vistas inyectables)

`setSlot()` permite inyectar una vista Blade en puntos predefinidos del layout del DataTable. Se llama desde `configure()`.

```php
public function configure(): void
{
    $this->setSlot('before-toolbar', 'livewire.users.table.header');

    // Con parámetros extra
    $this->setSlot('toolbar-right-end', 'livewire.users.table.actions', [
        'createRoute' => route('users.create'),
    ]);
}
```

### Áreas disponibles

| Área | Posición |
|------|----------|
| `before-toolbar` | Encima del toolbar completo |
| `after-toolbar` | Debajo del toolbar, antes de los filter pills |
| `toolbar-right-end` | Extremo derecho del toolbar, después del selector per-page |
| `after-table` | Después de la paginación |

### Variables disponibles en la vista inyectada

Todas las vistas inyectadas reciben:

- `$component` — La instancia del DataTable (acceso a propiedades públicas como `$search`, `$filters`, etc.)
- Cualquier variable extra pasada como tercer argumento a `setSlot()`

```blade
{{-- livewire/users/table/header.blade.php --}}
<div class="flex items-center justify-between px-4 py-3 border-b border-kore-border">
    <h2 class="text-lg font-semibold text-kore-fg">Usuarios</h2>
    <a href="{{ route('users.create') }}" class="...">
        Nuevo usuario
    </a>
</div>
```

```blade
{{-- livewire/users/table/actions.blade.php --}}
<a href="{{ $createRoute }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm ...">
    <x-lucide-plus class="size-4" />
    Nuevo
</a>
```

---

## Notas tecnicas

### Propiedades #[Locked]

Las propiedades de configuracion usan el atributo `#[Locked]` de Livewire:

| Propiedad | Qué guarda |
|-----------|-----------|
| `$defaultSortColumn` | Columna del sort primario por defecto |
| `$defaultSortDirection` | Dirección del sort primario por defecto |
| `$defaultSorts` | Array completo de sorts por defecto (incluyendo tiebreakers) |
| `$tableSlots` | Vistas Blade inyectadas via `setSlot()` |
| `$filterLayout` | Modo de layout de filtros |
| `$filtersExpanded` | Estado inicial del panel de filtros |

`#[Locked]` tiene dos efectos:

- **Persistentes** — Se incluyen en el snapshot de Livewire y sobreviven entre requests
- **Seguras** — No pueden ser modificadas desde el frontend (JavaScript)

Una propiedad `protected` no viaja en el snapshot: se resetea a su valor por defecto en cada request subsecuente. Con `#[Locked] public`, el valor se serializa y se restaura automaticamente.

### `configure()` se ejecuta en cada request

`configure()` se llama desde `booted()`, no desde `mount()`, asi que corre en **todas** las peticiones del componente.

Esto importa: las propiedades de configuracion (`density`, `responsiveMode`, `primaryKey`, `exportEnabled`, `exportFormats`, `maxHeight`, `paginationType`…) son `protected` y no se serializan. Si `configure()` corriera solo al montar, la tabla perderia su densidad, su modo responsive, su clave primaria y hasta el export en cuanto el usuario paginara, buscara o filtrara: todo volveria a los valores por defecto de la clase.

Reaplicarlo en cada request sale mas barato que serializar diecisiete propiedades en cada snapshot, y deja `configure()` como la unica fuente de verdad de la configuracion.

Dos consecuencias practicas:

- **`configure()` debe ser barato e idempotente.** Setters, nada de consultas ni efectos secundarios: se ejecuta en cada interaccion.
- **Si tu tabla define `booted()`, llama a `parent::booted()`**, o la configuracion no se aplicara.

El orden del primer request es `boot → mount → booted → render`; en los siguientes, `boot → booted → render`. Por eso `mount()` solo guarda el estado inicial que no depende de `configure()`: el `perPage` de la URL, los defaults de los filtros y el preset por defecto.

Las propiedades de la tabla de arriba siguen siendo `#[Locked] public` porque son **estado**, no configuracion: un slot inyectado o el sort por defecto tienen que sobrevivir tal cual, sin recalcularse.

### wire:ignore en layouts de filtro

Todos los layouts de filtro usan `wire:ignore` en el contenedor que envuelve los inputs de filtro. Esto previene que el morph de Livewire re-procese los componentes Alpine.js internos (como `<x-kore::select>`), lo cual causaria un loop infinito de requests.

La sincronizacion funciona asi:
- **Usuario → Livewire**: Alpine dispara eventos `input` en los hidden inputs → `wire:model.live` envia el update
- **Livewire → Alpine**: `$wire.$watch()` en los componentes Alpine detecta cambios y actualiza el estado local

Todo campo que viva dentro de un `wire:ignore` necesita ese `$watch`; si no, un reset en servidor (`resetFilter`, `resetAllFilters`, `applyPreset`, `clearPreset`) vacia `$filters` pero deja el valor escrito en pantalla. Los componentes Kore (`select`, `number`, `datepicker`) ya lo traen. El filtro de texto es un `<input>` plano y monta el suyo en `filters/types/text.blade.php`.

### Badge de filtros activos

El badge del boton de filtros **nunca** debe leerse con `$wire.getActiveFilterCount()`: en Livewire eso devuelve una `Promise`, asi que `Promise > 0` es siempre `false` (el badge no aparece nunca) y cada evaluacion reactiva dispara un round-trip al servidor.

Cada layout lo resuelve segun donde viva su boton:

| Layout | Fuente del conteo | Por que |
|--------|-------------------|---------|
| `slide-down` | Variable Blade `$filterCount` | Su boton esta **fuera** del `wire:ignore`, asi que se morfea con normalidad |
| `popover`, `drawer` | Propiedad `$wire.filterCount` | Su trigger esta **dentro** del `wire:ignore`: ese DOM no se morfea, y un valor impreso por Blade se congelaria en el del primer render |

`filterCount` es una propiedad publica `#[Locked]` que el servidor recalcula en cada `render()`. Se lee desde `$wire` de forma sincrona y reactiva, sin peticion adicional.

---

## Alpine.js Plugin

El plugin `KoreDataTable` se registra automaticamente y provee:

- `density` — Estado reactivo de densidad
- `densityClasses` — Clases CSS computadas para celdas
- `headerDensityClasses` — Clases CSS computadas para headers
- `slideDownOpen` — Estado del panel slide-down (inicializado desde `$filtersExpanded`)
- `selected` / `selectedCount` / `hasSelection` — Estado de seleccion de filas
- `toggleRow(id)` / `toggleAll()` / `clearSelection()` — Metodos de seleccion
- Atajo `/` para focus en el buscador

No necesitas importar ni registrar nada manualmente.
