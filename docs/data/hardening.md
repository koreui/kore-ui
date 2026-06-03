# DataTable — Endurecimiento, escalabilidad y accesibilidad

> Cambios introducidos tras la auditoría del DataTable (ver `docs/datatable-auditoria.md` en la raíz del monorepo). Documenta la **API pública nueva** y los **cambios de comportamiento** que afectan al uso de la librería.

---

## 1. Columnas

### `maxWidth(int $px)` — truncado con elipsis

Limita el ancho de una columna y trunca el contenido sobrante con `…`. El valor completo queda disponible en el atributo `title` de la celda (tooltip nativo y accesible) para columnas de tipo texto.

```php
use KoreUi\DataTable\Columns\Column;

Column::make('Descripción', 'description')->maxWidth(280),
```

- Aplica `max-width` + `truncate` a `<th>` y `<td>`.
- Útil con muchas columnas o textos largos: evita que la tabla crezca a lo ancho de forma descontrolada.
- Complementa a `width()` (ancho fijo) y `minWidth()` (ancho mínimo).

---

## 2. Selección y acciones masivas

### Selección entre páginas (cross-page) con indicador

La selección **se mantiene al cambiar de página, filtrar u ordenar**. La barra de acciones muestra cuántas filas hay seleccionadas y avisa cuando la selección incluye filas de otras páginas:

> `12 seleccionados (incl. otras páginas)`

### "Seleccionar todo lo que coincide"

Cuando todas las filas de la página están seleccionadas y hay más resultados que coinciden con los filtros actuales, aparece un enlace **"Seleccionar los N"**. Al activarlo, la acción masiva opera sobre **toda la query filtrada**, no solo sobre los IDs visibles.

- En modo normal: `executeBulkAction(identifier, ids)` recibe los IDs seleccionados del cliente.
- En modo "todo lo que coincide": `executeBulkActionMatching(identifier)` resuelve los IDs en el backend desde `query()` + filtros/búsqueda activos (no confía en el cliente).

```php
// La acción masiva recibe los IDs igual que siempre; no hay que cambiar nada
// en tu definición de bulkActions():
public function activate(array $ids): void
{
    User::whereIn('id', $ids)->update(['active' => true]);
}
```

> **Nota:** "seleccionar todo lo que coincide" materializa todos los IDs que coinciden (`pluck` del primary key). Para datasets enormes considera implementar la acción con operaciones en bloque (`query()->update()`/`delete()`) usando los filtros.

### Selección por rango (shift-click)

Mantén **Shift** al marcar un checkbox para seleccionar el rango contiguo desde la última fila marcada.

---

## 3. Exportación

`exportMaxRows` ahora **se respeta de verdad**. Antes, `chunk()` sobrescribía el `limit()` y se exportaba todo el dataset; ahora el exporter corta exactamente en el tope.

```php
public function configure(): void
{
    $this->setExportEnabled()
         ->setExportMaxRows(5000); // tope efectivo
}
```

- El export ordena por el primary key como desempate, garantizando que `chunk()` no salte ni duplique filas aunque ordenes por una columna no única.
- Los valores que empiezan por `= + - @` (fórmulas) se neutralizan con una comilla simple → protección contra **CSV/formula injection** al abrir en Excel/LibreOffice.

---

## 4. Filtros y presets

### Caché de conteos de preset

Los badges de conteo (`->count(fn () => ...)`) ya **no se recalculan en cada render**. Se calculan una vez y se invalidan automáticamente tras acciones masivas e inline editing. Si tu app cambia los datos por otra vía y necesitas refrescar los conteos manualmente:

```php
$this->invalidatePresetCounts();
```

### Debounce en filtros numéricos

Los filtros `number` y `number-range` usan `wire:model.live.debounce.500ms` para no disparar una consulta por cada tecla.

---

## 5. Cabecera, pinning y responsive

- **Header sticky:** el `<thead>` permanece visible al hacer scroll vertical (`sticky top-0`, fondo opaco).
- **Column pinning preciso:** los offsets de columnas fijadas se recalculan en el cliente midiendo los anchos reales (init, resize y tras cada render de Livewire), por lo que **ya no es obligatorio** definir `width` en columnas fijadas para que se alineen.
- **Responsive por contenedor:** el cambio a vista `card`/`collapse` se decide por el ancho del **contenedor** (vía `ResizeObserver`), no del viewport. Un datatable dentro de un panel estrecho colapsa aunque la pantalla sea ancha.
- **Dark mode:** la sombra de separación de las columnas fijadas usa el token `--kore-pin-shadow` (más opaca en dark), visible en ambos temas.

---

## 6. Accesibilidad

- `<th>` con `scope="col"` y `aria-sort` (`ascending`/`descending`/`none`) reflejando el estado de orden.
- Botones de orden con `aria-label="Ordenar por …"`.
- Checkboxes de selección con `aria-label`; filas con `aria-selected`.
- Botones de cierre de pills (filtros/orden/preset) con `aria-label`.
- Flechas de paginación (activas y deshabilitadas) con `aria-label`.
- La navegación por teclado solo actúa cuando el datatable está enfocado o bajo el cursor — no secuestra atajos globales como `Ctrl/Cmd+A`.

---

## 7. Seguridad (garantías del componente)

Estas protecciones son automáticas; no requieren configuración:

- **Ordenamiento:** `sorts` se valida contra el whitelist de columnas `sortable()` y la dirección se normaliza a `asc|desc`. No se puede ordenar por columnas arbitrarias ni provocar errores con direcciones manipuladas.
- **Inline editing:** el registro se resuelve a través de `query()` (respeta scopes/global scopes), evitando editar filas fuera del dataset autorizado (IDOR). El valor se coerciona al tipo de la columna.
- **Búsqueda:** los comodines `%` y `_` se escapan con cláusula `ESCAPE` explícita (consistente en MySQL/PostgreSQL/SQLite).
- **Agregaciones:** el nombre de columna se valida y se entrecomilla antes de construir el `selectRaw`.
- **Paginación:** `perPage` se acota al whitelist de `per_page_options`.

---

## Mejoras pendientes (opcionales, baja prioridad)

Documentadas en `docs/datatable-auditoria.md`:

- Reset visual de los inputs de filtro tras "Limpiar filtros" (requiere soporte de reset en `x-kore::select`, que es un componente Alpine).
- Memoización de `query()`/`columns()` por request.
- `selected` como `Set` (micro-optimización).
- Indicador visual de la celda activa en navegación por teclado.
