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

## 4.b Rendimiento por render

- **Definición memoizada.** `columns()`, `filters()`, `filterPresets()` y `bulkActions()` se construyen una vez por petición. `columns()` se invocaba trece veces solo desde el módulo; con `filters()` el coste no era de objetos sino de consultas, porque el patrón normal es `SelectFilter::options(Ciudad::pluck(...))`. Los cachés son `protected`, así que no se serializan: duran lo que dura el request.
- **`hiddenIf()` se evalúa una vez por columna**, no una por cada sitio que consulta `isHidden()`.
- **Ventana de paginación por aritmética.** Antes se recorría `1..$lastPage`: un millón de filas a 25 por página eran 40.000 vueltas por render para pintar seis botones.
- **Una sola variante en los modos `card` y `collapse`.** El servidor no sabe el ancho del contenedor, así que la primera carga emite tabla y tarjetas y el cliente esconde la que sobra; en cuanto Alpine informa del ancho, cada render manda solo la que toca. El HTML duplicado se paga una vez, no en cada paginación.

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
- La navegación por teclado solo actúa cuando el datatable está enfocado o bajo el cursor — no secuestra atajos globales como `Ctrl/Cmd+A`. Un `<select>` enfocado cuenta como campo de formulario, y `Escape` solo suelta el foco de un input propio.
- Los identificadores de fila entran en las expresiones de Alpine y Livewire vía `@js()`, no interpolados: una clave primaria de tipo texto no puede romper (ni extender) la expresión.
- El panel de filtros en modo `drawer` es un diálogo real: `role="dialog"`, `aria-modal`, `aria-labelledby` y foco atrapado dentro con `x-kore-trap`, devolviéndolo al botón que lo abrió al cerrar.
- Cada etiqueta de filtro apunta a su campo con `for`/`id`, únicos por tabla y por filtro.
- El recuento de resultados se anuncia con `aria-live="polite"`: filtrar o buscar ya no es un cambio silencioso.
- La celda activa en navegación por teclado se resalta, y el recorrido horizontal se acota a la tabla visible (en modo `collapse` conviven dos).

---

## 7. Seguridad (garantías del componente)

Estas protecciones son automáticas; no requieren configuración:

- **Ordenamiento:** `sorts` se valida contra el whitelist de columnas `sortable()` y la dirección se normaliza a `asc|desc`. No se puede ordenar por columnas arbitrarias ni provocar errores con direcciones manipuladas.
- **Filtros:** `filters` es igual de pública que `sorts` y recibe el mismo trato. Cada filtro implementa `sanitize()` y solo llega a la consulta lo que sobrevive: se descartan los valores con la forma equivocada (un array donde se espera un escalar era un `PDOException` y un 500), los no numéricos en filtros numéricos, las fechas no parseables, y —cuando el filtro declara `options()`— los valores fuera de esa lista. Los comodines `%` y `_` se escapan igual que en la búsqueda. Un valor rechazado no se aplica **ni se cuenta ni se pinta como pill**, para que la interfaz nunca anuncie un filtro que la consulta no está aplicando.
- **Acciones masivas:** el identificador se resuelve contra las acciones **visibles** y se comprueba su `authorize()` justo antes de ejecutar. Los IDs que llegan del cliente se recortan a los que la `query()` de la tabla autoriza (mismo criterio que la edición inline), con un tope de `bulkSelectionLimit`. En modo "todo lo que coincide" los IDs no viajan al navegador: se resuelven de nuevo en el servidor al confirmar.
- **Inline editing:** el registro se resuelve a través de `query()` (respeta scopes/global scopes), evitando editar filas fuera del dataset autorizado (IDOR). El valor se coerciona al tipo de la columna.
- **Búsqueda:** los comodines `%` y `_` se escapan con cláusula `ESCAPE` explícita (consistente en MySQL/PostgreSQL/SQLite).
- **Agregaciones:** el nombre de columna se valida y se entrecomilla antes de construir el `selectRaw`.
- **Paginación:** `perPage` se acota al whitelist de `per_page_options`.

---

## Mejoras pendientes (opcionales, baja prioridad)

- Export asíncrono en cola para datasets grandes. Hoy `exportMaxRows` corta el archivo y avisa con un toast, pero no hay despacho a job.
- Acciones masivas en cola. `matchingQuery()` y `eachMatching()` permiten escribirlas sin materializar el conjunto, pero se ejecutan en el request.
- `selected` como `Set` (micro-optimización acotada por `perPage`).
