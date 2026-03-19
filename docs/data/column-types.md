# Column Types

Columnas especializadas que renderizan contenido visual rico sin necesidad de escribir HTML manual con `format()` + `html()`.

Todas las columnas extienden `Column` y heredan sorting, search, visibility y alignment.

---

## Uso basico

```php
use KoreUi\DataTable\Columns\BooleanColumn;
use KoreUi\DataTable\Columns\BadgeColumn;
use KoreUi\DataTable\Columns\DateColumn;
// ... etc

public function columns(): array
{
    return [
        BooleanColumn::make('Activo', 'is_active'),
        BadgeColumn::make('Rol', 'role')->colors(['admin' => 'primary']),
        DateColumn::make('Creado', 'created_at')->dateFormat('d M Y'),
    ];
}
```

---

## Tipos disponibles

| Tipo | Clase | Componente UI |
|------|-------|---------------|
| `text` | `Column` | Texto plano (default) |
| `boolean` | `BooleanColumn` | `<x-kore::boolean>` |
| `badge` | `BadgeColumn` | `<x-kore::badge>` |
| `date` | `DateColumn` | Carbon + `<x-kore::tooltip>` |
| `image` | `ImageColumn` | `<x-kore::avatar>` o `<img>` |
| `link` | `LinkColumn` | `<a>` con estilos kore |
| `number` | `NumberColumn` | `number_format` / `NumberFormatter` |
| `progress` | `ProgressColumn` | `<x-kore::progress>` |
| `color` | `ColorColumn` | Swatch div + clipboard |
| `component` | `ComponentColumn` | `@include` o `<x-dynamic-component>` |
| `action` | `ActionColumn` | `<x-kore::dropdown>` con RowActions |

---

## BooleanColumn

Renderiza un icono check/x con colores semanticos.

```php
BooleanColumn::make('Activo', 'is_active')
    ->trueColor('success')
    ->falseColor('destructive')
    ->trueIcon('check')
    ->falseIcon('x')
    ->size('md');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `trueIcon(string)` | `'check'` | Icono cuando el valor es truthy |
| `falseIcon(string)` | `'x'` | Icono cuando el valor es falsy |
| `trueColor(string)` | `'success'` | Color semantico para true |
| `falseColor(string)` | `'muted'` | Color semantico para false |
| `size(string)` | `'sm'` | Tamano: `sm`, `md`, `lg` |

Constructor: `align = 'center'`

---

## BadgeColumn

Renderiza un badge con color e icono mapeados por valor.

```php
BadgeColumn::make('Rol', 'role')
    ->colors([
        'admin'  => 'primary',
        'editor' => 'info',
        'user'   => 'muted',
    ])
    ->icons([
        'admin'  => 'shield',
        'editor' => 'pencil',
        'user'   => 'user',
    ])
    ->variant('soft')
    ->size('sm');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `colors(array)` | `[]` | Mapa `valor => color` semantico. Fallback: `'muted'` |
| `icons(array)` | `[]` | Mapa `valor => icono` Lucide |
| `variant(string)` | `'solid'` | Variante del badge: `solid`, `soft`, `outline` |
| `size(string)` | `'sm'` | Tamano: `sm`, `md`, `lg` |

---

## DateColumn

Formatea fechas con Carbon. Opcionalmente muestra tooltip con formato completo.

```php
DateColumn::make('Creado', 'created_at')
    ->dateFormat('d M Y')
    ->timezone('America/Mexico_City')
    ->tooltipFormat('l, d F Y H:i:s');

// Fecha relativa
DateColumn::make('Ultima actividad', 'last_login_at')
    ->diffForHumans()
    ->tooltipFormat('d/m/Y H:i');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `dateFormat(string)` | `'d/m/Y'` | Formato Carbon para el valor mostrado |
| `timezone(string)` | `null` | Zona horaria para la conversion |
| `diffForHumans(bool)` | `false` | Muestra "hace 2 dias" en vez del formato |
| `tooltipFormat(string)` | `null` | Si se define, envuelve en `<x-kore::tooltip>` con la fecha completa |

> **Nota:** Se usa `dateFormat()` en vez de `format()` para evitar conflicto con `Column::format(Closure)`. Si se define un `format()` callback, tiene prioridad sobre `dateFormat()`.

---

## ImageColumn

Muestra imagen o avatar con fallback de iniciales.

```php
ImageColumn::make('Avatar', 'avatar_url')
    ->isAvatar()           // default: true
    ->nameField('name')    // para iniciales de fallback
    ->size('sm')
    ->shape('circle');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `isAvatar(bool)` | `true` | Usa `<x-kore::avatar>` (con fallback iniciales) |
| `nameField(string)` | `null` | Campo del modelo para generar iniciales |
| `size(string)` | `'sm'` | Tamano: `xs`, `sm`, `md`, `lg` |
| `shape(string)` | `'circle'` | Forma: `circle`, `square` |

Constructor: `align = 'center'`

---

## LinkColumn

Renderiza un enlace con URL interpolada o callback.

```php
// Con patron de interpolacion
LinkColumn::make('Nombre', 'name')
    ->urlPattern('/users/{id}/edit')
    ->icon('external-link')
    ->openInNewTab();

// Con callback
LinkColumn::make('Sitio', 'website')
    ->url(fn ($row) => $row->website)
    ->color('primary');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `urlPattern(string)` | `null` | Patron con `{field}` para interpolar valores del row |
| `url(Closure)` | `null` | Callback `fn($row) => string` para URL dinamica |
| `openInNewTab(bool)` | `false` | Agrega `target="_blank"` |
| `icon(string)` | `null` | Icono Lucide junto al texto |
| `color(string)` | `'primary'` | Color semantico del enlace |

> El patron `{field}` se interpola con `data_get($row, 'field')`. Ej: `/users/{id}` → `/users/42`

---

## NumberColumn

Formatea numeros con separadores, prefijos y soporte de moneda.

```php
// Formato basico
NumberColumn::make('Edad', 'age')
    ->decimals(0);

// Moneda
NumberColumn::make('Salario', 'salary')
    ->money('USD', 'en_US');

// Formato personalizado
NumberColumn::make('Precio', 'price')
    ->decimals(2)
    ->prefix('$')
    ->suffix(' MXN')
    ->separators(',', '.');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `decimals(int)` | `0` | Decimales |
| `separators(string, string)` | `','`, `'.'` | Separador de miles y decimal |
| `prefix(string)` | `null` | Texto antes del numero |
| `suffix(string)` | `null` | Texto despues del numero |
| `money(string, string)` | — | Atajo para formato moneda con `NumberFormatter` |

Constructor: `align = 'right'`

> `money($currency, $locale)` usa `NumberFormatter::CURRENCY` de PHP intl. Requiere la extension `intl`.

---

## ProgressColumn

Barra o circulo de progreso con color automatico.

```php
ProgressColumn::make('Progreso', 'completion')
    ->max(100)
    ->color('auto')
    ->showValue()
    ->size('sm');

// Circulo
ProgressColumn::make('Score', 'score')
    ->circle()
    ->max(100)
    ->color('primary');

// Barra striped
ProgressColumn::make('Avance', 'progress')
    ->striped();
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `max(int)` | `100` | Valor maximo |
| `size(string)` | `'sm'` | Tamano: `xs`, `sm`, `md`, `lg` |
| `color(string)` | `'auto'` | Color: `auto` (verde>amarillo>rojo segun %), o color semantico |
| `showValue(bool)` | `false` | Muestra el porcentaje como texto |
| `circle(bool)` | `false` | Renderiza como circulo en vez de barra |
| `striped(bool)` | `false` | Patron striped en la barra |

---

## ColorColumn

Muestra un swatch de color con opcion de copiar al clipboard.

```php
ColorColumn::make('Color', 'hex_color')
    ->copyable()
    ->showLabel()
    ->swatchSize('md')
    ->swatchShape('circle');
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `showLabel(bool)` | `false` | Muestra el valor hex junto al swatch |
| `swatchSize(string)` | `'md'` | Tamano del swatch: `sm`, `md`, `lg` |
| `swatchShape(string)` | `'square'` | Forma: `square`, `circle` |
| `copyable(bool)` | `false` | Click en el swatch copia el hex al clipboard (Alpine) |

---

## ComponentColumn

Renderiza un componente Blade o vista personalizada por celda.

```php
// Componente Blade
ComponentColumn::make('Estado', 'status')
    ->component('x-status-indicator')
    ->props(fn ($value, $row) => ['status' => $value, 'label' => $row->name]);

// Vista include
ComponentColumn::make('Detalle', 'detail')
    ->view('partials.user-detail')
    ->props(fn ($value, $row) => ['user' => $row]);
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `component(string)` | `null` | Nombre del componente Blade (usa `<x-dynamic-component>`) |
| `view(string)` | `null` | Nombre de la vista (usa `@include`) |
| `props(Closure)` | `null` | Callback `fn($value, $row) => array` para props/variables |

> Usa `component()` o `view()`, no ambos. `component()` tiene prioridad.

---

## ActionColumn

Columna de acciones por fila con dropdown o botones inline.

```php
use KoreUi\DataTable\Actions\RowAction;

ActionColumn::make()
    ->actions([
        RowAction::make('view', 'Ver')
            ->icon('eye')
            ->urlPattern('/users/{id}'),

        RowAction::make('edit', 'Editar')
            ->icon('pencil')
            ->wireMethod('editUser'),

        RowAction::make('delete', 'Eliminar')
            ->icon('trash')
            ->color('destructive')
            ->wireMethod('deleteUser')
            ->confirm('¿Eliminar este registro?')
            ->separator(),
    ]);
```

| Metodo | Default | Descripcion |
|--------|---------|-------------|
| `actions(array)` | `[]` | Array de `RowAction` |
| `triggerIcon(string)` | `'more-horizontal'` | Icono del boton trigger del dropdown |
| `inline(bool)` | `false` | Botones en linea en vez de dropdown |
| `dropdown(bool)` | `true` | Acciones dentro de dropdown (default) |

Constructor: `label = 'Acciones'`, `field = '__actions'`, `align = 'center'`. No es sortable ni searchable.

---

## RowAction API

Clase para definir acciones individuales por fila.

```php
RowAction::make('id', 'Label')
    ->icon('pencil')
    ->color('primary')
    ->urlPattern('/users/{id}/edit')
    ->url(fn ($row) => route('users.edit', $row))
    ->wireMethod('editUser')
    ->confirm('¿Estas seguro?', 'Descripcion opcional')
    ->openInNewTab()
    ->hidden(fn ($row) => $row->role === 'admin')
    ->separator();
```

| Metodo | Descripcion |
|--------|-------------|
| `make(id, label)` | Identificador unico y texto visible |
| `icon(string)` | Icono Lucide |
| `color(string)` | Color semantico (default: `null`) |
| `urlPattern(string)` | Patron URL con `{field}` interpolado |
| `url(Closure)` | Callback `fn($row) => string` para URL dinamica |
| `wireMethod(string)` | Metodo Livewire a ejecutar (recibe el primary key como argumento) |
| `confirm(string, string)` | Dialogo de confirmacion antes de ejecutar |
| `openInNewTab(bool)` | Agrega `target="_blank"` |
| `hidden(Closure)` | Callback `fn($row) => bool` para ocultar por fila |
| `separator()` | Agrega separador visual antes de la accion en el dropdown |

> `wireMethod` y `url`/`urlPattern` son mutuamente excluyentes. Si se define `wireMethod`, se ejecuta como `$wire.call(method, id)`.

---

## Column Select

Toggle de visibilidad de columnas con persistencia en session.

### Configuracion

```php
public function configure(): void
{
    // Desactivar por tabla
    $this->setColumnSelectEnabled(false);
}
```

```php
// Config global (config/kore-ui.php)
'datatable' => [
    'column_select' => true,
]
```

El boton "Columnas" aparece en el toolbar. La seleccion se persiste en session por DataTable (keyed por FQCN).

### Metodos del trait WithColumnSelect

| Metodo | Descripcion |
|--------|-------------|
| `toggleColumnVisibility(string)` | Alterna visibilidad de una columna por field |
| `resetColumnSelect()` | Restaura todas las columnas visibles |
| `isColumnDeselected(string)` | Verifica si una columna esta oculta |
| `setColumnSelectEnabled(bool)` | Habilita/deshabilita el feature |

---

## Responsive

Tres modos de visualizacion responsive para viewports pequenos.

### Modos

| Modo | Descripcion |
|------|-------------|
| `scroll` | Scroll horizontal en la tabla (default) |
| `card` | Cada fila como card vertical con label:value |
| `collapse` | Tabla con columnas colapsables y expand por fila |

### Configuracion

```php
public function configure(): void
{
    $this->setResponsiveMode('card');
    $this->setResponsiveBreakpoint(640); // default: 768
}
```

```php
// Config global
'datatable' => [
    'responsive_mode' => 'scroll',
    'responsive_breakpoint' => 768,
]
```

### Marcar columnas como colapsables (modo collapse)

```php
Column::make('Email', 'email')->collapseOnMobile();
Column::make('Ciudad', 'city')->collapseOnTablet();
```

El modo responsive se detecta client-side via Alpine.js (`window.innerWidth`). En modo `card`, la primera columna se usa como titulo y `ActionColumn` como menu en el header del card.
