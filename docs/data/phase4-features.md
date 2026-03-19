# DataTable — Fase 4: Diferenciadores

Features avanzadas de productividad para admin panels.

## Aggregation (Footer)

Agrega cálculos sobre la columna completa (respetando filtros/búsqueda, sin paginar).

```php
Column::make('Edad', 'age')->sum('Total'),
NumberColumn::make('Salario', 'salary')->avg(2, 'Promedio'),
Column::make('ID', 'id')->count('Registros'),
Column::make('Precio', 'price')->footerMin('Mínimo'),
Column::make('Precio', 'price')->footerMax('Máximo'),

// Footer callback personalizado
Column::make('Total', 'amount')
    ->footer(fn ($query) => '$' . number_format($query->sum('amount'), 2))
    ->footerLabel('Suma'),
```

**Formateo automático:** `NumberColumn` formatea el valor de aggregation usando la misma configuración que las celdas (currency, locale, separadores). Una `Column` normal muestra el valor sin formato. Se puede override con `formatAggregationValue($value)` en columnas custom.

## Sort Pills

Muestra los criterios de ordenamiento activos como pills removibles.

Se renderizan automáticamente después de filter pills. Color `info` para diferenciar de filter pills (`primary`).

Métodos disponibles en el componente:
- `removeSortBy(string $column)` — Quita un sort específico
- `clearSorts()` — Limpia todos los sorts
- `getActiveSorts()` — Retorna array con field, label, direction

## Query String Persistence

Persiste el estado del DataTable en la URL (filtros, búsqueda, sorts, perPage).

```php
public function configure(): void
{
    $this->setQueryStringEnabled();
}
```

Mapping:
- `search` → `?q=...`
- `sorts` → `?sort[name]=asc`
- `filters` → `?filter[city]=Madrid`
- `perPage` → `?per_page=50`

Config global: `'query_string' => false` en `config/kore-ui.php`.

## Filter Presets

Tabs horizontales que aplican conjuntos predefinidos de filtros/sorts/search.

```php
public function filterPresets(): array
{
    return [
        FilterPreset::make('all', 'Todos')
            ->icon('users')
            ->count(fn () => User::count()),

        FilterPreset::make('active', 'Activos')
            ->icon('check-circle')
            ->filters(['is_active' => '1'])
            ->count(fn () => User::where('is_active', true)->count()),

        FilterPreset::make('admins', 'Administradores')
            ->filters(['role' => 'admin'])
            ->sorts(['name' => 'asc']),
    ];
}
```

API de `FilterPreset`:
- `make(identifier, label)` — Constructor
- `icon(string)` — Ícono Lucide
- `filters(array)` — Filtros a aplicar
- `sorts(array)` — Sorts a aplicar
- `search(string)` — Búsqueda a aplicar
- `perPage(int)` — Items por página
- `count(Closure)` — Badge con conteo
- `default()` — Preset activo al montar
- `hidden()` — No mostrar en tabs

Comportamiento:
- Al aplicar un preset siempre se resetea el estado previo (filtros, sorts, search)
- El preset se desactiva automáticamente al cambiar filtros/búsqueda manualmente
- `clearPreset()` limpia todo el estado

## Export

Exportar datos filtrados/ordenados como CSV.

```php
public function configure(): void
{
    $this->setExportEnabled();
    $this->setExportFormats(['csv']);
    $this->setExportFileName('usuarios.csv');
    $this->setExportMaxRows(10000);
    $this->setExportOnlyVisible(true); // Respeta column select
}
```

- UTF-8 BOM incluido para compatibilidad con Excel
- `streamDownload` + `chunk(1000)` para eficiencia en memoria
- Excluye `ActionColumn` automáticamente
- Booleans exportados como "Sí"/"No"

## Inline Editing

Editar celdas directamente en la tabla. Cada celda editable usa su propio `x-data` local con Alpine.js para máxima confiabilidad.

### Tipos de editable

#### Input (default)
```php
Column::make('Nombre', 'name')
    ->editable()
    ->editableRules(['required', 'min:2']),
```
Click → aparece input de texto → Enter o blur guarda → Escape cancela.

#### Input numérico (automático en NumberColumn)
```php
NumberColumn::make('Salario', 'salary')
    ->money('USD', 'en_US')
    ->editable(),
```
`NumberColumn` automáticamente usa `type="number"` con `step="any"`. El browser muestra controles numéricos y teclado numérico en mobile.

También se puede forzar manualmente:
```php
Column::make('Edad', 'age')->editable()->editableInputType('number'),
Column::make('URL', 'website')->editable()->editableInputType('url'),
```

#### Select (con opciones)
```php
Column::make('Rol', 'role')
    ->editable()
    ->editableOptions([
        'admin'  => 'Administrador',
        'editor' => 'Editor',
        'user'   => 'Usuario',
    ]),
```
`editableOptions()` automáticamente setea el componente a `select`. Click → aparece dropdown con la opción actual preseleccionada → al cambiar guarda inmediatamente.

#### Textarea
```php
Column::make('Notas', 'notes')
    ->editable()
    ->editableComponent('textarea'),
```
Click → aparece textarea multilínea → Enter o blur guarda → Escape cancela.

#### Toggle (automático en BooleanColumn)
```php
BooleanColumn::make('Activo', 'is_active')
    ->editable()
    ->trueColor('success')
    ->falseColor('destructive'),
```
Click directo → toggle visual inmediato (optimistic UI) + actualización en servidor. Sin input, sin modo edición. Usa `x-show` para alternar iconos sin destruir el DOM.

### Callback personalizado
```php
Column::make('Email', 'email')
    ->editable()
    ->editableCallback(function ($rowId, $field, $value) {
        // Lógica personalizada en vez de update directo
    }),
```

### Validación
Las reglas definidas con `editableRules()` se validan en el servidor antes de guardar. Si falla, se despacha un evento `kore:datatable-edit-error` con el mensaje de error.

## Keyboard Navigation

Navegación completa por teclado:

| Tecla | Acción |
|-------|--------|
| `/` | Focus en búsqueda |
| `↑` / `↓` | Navegar filas |
| `←` / `→` | Navegar celdas |
| `Enter` | Activar celda (editar si es editable) |
| `Space` | Toggle selección de fila |
| `Escape` | Cancelar edición / salir de navegación |
| `Ctrl+A` / `Cmd+A` | Seleccionar/deseleccionar todas |

La fila activa se resalta con ring azul. La navegación es 100% client-side (sin roundtrips a Livewire). Se desactiva automáticamente cuando un input tiene focus.

## Arquitectura de edición inline

Cada celda editable usa `x-data` local en vez de estado global:
- **Rendimiento:** Solo la celda clickeada reacciona, no se re-evalúan N expresiones
- **Confiabilidad:** Boolean local `editing` es 100% reactivo en Alpine
- **Independencia:** No hay conflictos entre celdas, cada una es autosuficiente
- **Paginación típica (25-50 filas):** Sin impacto de rendimiento notable

El servidor maneja validación y persistencia via `$wire.updateCell(rowId, field, value)` en el trait `WithInlineEditing`.

## Configuración

Nuevas opciones en `config/kore-ui.php`:

```php
'datatable' => [
    'query_string' => false,
    'export' => [
        'enabled'  => false,
        'formats'  => ['csv'],
        'max_rows' => 10000,
    ],
    'translations' => [
        'sorted_by'   => 'Ordenado por',
        'clear_sorts' => 'Limpiar ordenamiento',
        'export'      => 'Exportar',
    ],
],
```
