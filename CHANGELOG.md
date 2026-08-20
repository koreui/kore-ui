# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y el proyecto usa [Semantic Versioning](https://semver.org/lang/es/).

---

## [2.0.0] — 2026-08-20

**La librería entera, auditada en un navegador.** Seis lotes de componentes probados uno a uno en Chrome de escritorio y en WebKit sobre iPhone, midiendo el comportamiento real en vez de asertar clases: **67 defectos corregidos** y una suite E2E que pasa de 0 a 456 pruebas.

| Lote | Defectos | Informe |
|---|---|---|
| DataTable | — | `docs/datatable-auditoria.md` |
| Formulario | 18 | `docs/formulario-auditoria.md` |
| Overlay y feedback | 11 | `docs/overlay-auditoria.md` |
| Navegación y layout | 9 | `docs/navegacion-auditoria.md` |
| Datos y visualización | 8 | `docs/datos-auditoria.md` |
| Interacción | 12 | `docs/interaccion-auditoria.md` |
| Presentación | 9 | `docs/presentacion-auditoria.md` |

Es una versión mayor porque hay cambios visibles que no se pueden desactivar. Están todos abajo, y ninguno es gratuito: cada uno arregla algo que estaba medido y roto.

Tres hilos recorren toda la auditoría, y explican la mayoría de los defectos:

1. **Lo que el JavaScript escribe y el servidor no emite, el morph se lo lleva.** Estado de Alpine, estilos en línea, atributos puestos a mano. Costó un árbol muerto, un splitter deshecho, un carrusel en blanco y varios componentes que dejaban de enterarse de sus propios datos.
2. **Un id que cambia en cada render rompe el morph**, y de ahí salieron siete defectos del lote de formulario a la vez.
3. **El color de la paleta es un color de FONDO.** Usado como texto sobre su propio tinte no llega a AA, y así estaba en media librería.

### ⚠️ Cambios que rompen compatibilidad

Lo que hay que mirar antes de actualizar:

- **Las variantes `soft`, `outline`, `ghost` y `link` cambian de tono**, en toda la librería. Usan los tokens `-text` nuevos: más oscuros en tema claro, más claros en oscuro. Es el cambio visible más extendido — afecta a `badge`, `chip`, `alert`, `button`, `avatar`, `sidebar`, `stats`, `tree`, `tab`, `tag-input`, `spotlight` y cuatro vistas del DataTable. Se revierte apuntando los tokens `-text` al color base.
- **Los textos por defecto pasan a español.** `Search...` → `Buscar...`, `No options found` → `Sin resultados`, `Choose file` → `Elegir archivo`, `Previous slide` → `Diapositiva anterior`, `Copy to clipboard` → `Copiar`, `Speed dial menu` → `Acciones rápidas`. Están en `kore-ui.form.translations` y `kore-ui.ui.translations`, así que se revierten por configuración sin publicar vistas.
- **Un modal ya no centra el texto de su contenido.** Quien se apoyara en ese `text-center` heredado verá su contenido a la izquierda. No hay interruptor: se arregla poniendo `text-center` en el propio contenido.
- **El velo de los overlays usa `--kore-backdrop`.** Quien lo personalizara sobrescribiendo `--kore-fg` tiene que mover el cambio al token nuevo. El del drawer del sidebar también, y era un negro fijo en el CSS.
- **`<x-kore::tab>` y `<x-kore::stepper>` sin `selected` ya seleccionan el primer item.** Antes no seleccionaban ninguno y el componente salía sin contenido; si alguien contaba con que el primer panel no se viera, ahora se ve.
- **Un `Escape` con un panel abierto dentro de un modal ya no cierra las dos capas.** Hacen falta dos pulsaciones.
- **La raíz de `<x-kore::tree>` lleva `wire:ignore`.** Quien pintara contenido propio dentro del árbol contando con que el servidor lo actualizara tiene que sacarlo fuera.
- **`<x-kore::alert>` ya no lleva `role="alert"` por defecto.** Interrumpía al lector aunque la alerta llevara ahí desde el principio. Para pedirlo: `live="assertive"`.
- **`<x-kore::loading>` es `role="status"`** y añade un texto oculto cuando no tiene uno visible. Con `announce="false"` se calla.
- **`<x-kore::boolean>` dice «Sí» y «No»** en vez de «true» y «false».
- **`<x-kore::button-group>` es un `role="group"` con nombre.**
- **Los indicadores del carrusel ya no son `role="tab"`**, sino botones con `aria-current` — no había ningún `tabpanel` al otro lado. Quien los localizara por rol en sus tests tiene que cambiar el selector.
- **`<x-kore::carousel autoplay>` pinta un botón de pausa** que antes no existía, y **sus diapositivas fuera de vista llevan `inert`**: quien contara con enfocar algo dentro de una oculta tiene que moverse a ella primero.
- **El panel del `<x-kore::tooltip>` ya no lleva `role="tooltip"`**: va `aria-hidden`, y el texto accesible lo da un `<span class="sr-only">` del componente. Quien lo localizara por `[role="tooltip"]` tiene que buscar por `[data-kore-teleport]`.
- **`<x-kore::stats>` ya no anima con `prefers-reduced-motion` activo**, ni tampoco el spinner, los puntos, el pulso, el brillo del esqueleto o el pulso de presencia del avatar.

### Sigue pendiente

Medido y sin resolver a propósito, porque son decisiones de diseño:

- **El contraste de las variantes `solid`.** Los cuatro colores fallan AA como fondo de texto blanco: `success` 3,17 · `info` 3,42 · `destructive` 4,39 · `primary` 4,41. Arreglarlo es mover la paleta base, y esos mismos tokens pintan gráficos, iconos y estados. Ver `docs/presentacion-auditoria.md` §A.1.
- **Tres `role` sin nombre**: el `role="dialog"` del overlay manager, dos `listbox` anidados del select y el `menu` del theme-switch.
- **Teclado para el tablero y para `<x-kore::sortable>` en modo servidor**, los dos a cero controles enfocables.
- **Textos en inglés** que quedan fuera de los tres lotes ya barridos: `theme-switch`, `color-picker`, `time-picker`, el filtro del árbol y las migas.

---

### DataTable

**Auditoría del DataTable completa, tres funciones nuevas y una suite E2E.** Cuarenta y ocho correcciones sobre el módulo. Dos de ellas cierran una brecha incómoda: estaban firmadas como completadas en el roadmap de la 1.2.0, publicadas en este CHANGELOG y descritas como garantías en `docs/data/hardening.md`, pero el código nunca las tuvo. `git log -S` sobre `resources/js/datatable.js` lo confirma para el guard de teclado: no existió en ninguna versión.

#### Added

- **`Column::description()`** — segunda línea de la celda, en tono secundario. El patrón «nombre arriba, correo en gris debajo» que aparece en casi toda tabla de administración y que hasta ahora obligaba a bajar a un `ComponentColumn`. Acepta closure o nombre de campo, y se puede colocar encima del valor. Se renderiza en los tres modos (tabla, `card` y `collapse`).
- **Menú por cabecera de columna** — ordenar ascendente/descendente, fijar a izquierda/derecha y ocultar, desde la propia columna. Es lo que convierte `pinned()` y el selector de columnas en algo del usuario final y no solo de quien escribe la tabla. Los fijados que elige el usuario se guardan en sesión y mandan sobre los que declara la tabla, así que una columna con `->pinned('left')` puede soltarse. Trae `setSort()` (fija la dirección, a diferencia de `sortBy()`, que rota), `toggleColumnPin()` y `resetColumnPins()`. Se apaga con `datatable.column_menu` o `setColumnMenuEnabled(false)`.
- **Vistas guardadas por usuario** — un `FilterPreset` lo declara quien escribe la tabla y es fijo; una vista la crea quien la usa, guardando filtros, orden, búsqueda, `perPage`, columnas visibles y columnas fijadas. Se activan con `setSavedViewsEnabled()` o `datatable.saved_views`.

  La persistencia es un contrato, `SavedViewStore`, con un driver de sesión por defecto: funciona sin instalar nada, y quien quiera vistas permanentes implementa la interfaz contra su propia tabla y la enlaza en el contenedor. La librería **no trae modelo ni migración** a propósito — nadie debería tener que migrar su base de datos por usar un DataTable. El ámbito por usuario queda del lado de la implementación, porque solo ella sabe qué es un usuario en esa aplicación.
- **Directiva `x-kore-trap`** — retención de foco sin dependencias, para cualquier componente que necesite comportarse como diálogo. Nace del drawer de filtros, pero es de uso general.

#### Security

- **Los valores de filtro dejan de llegar crudos a la consulta.** `$filters` es tan pública y manipulable como `$sorts`, pero solo la segunda se validaba. Un `?filter[estado][]=x` metía un array donde `where()` espera un escalar, Eloquent lo pasaba como binding y salía un `PDOException` — es decir, un enlace que rompe la página de quien lo abra. Ahora cada filtro implementa `sanitize()` y solo llega a la consulta lo que sobrevive: se descartan las formas equivocadas, los valores no numéricos en filtros numéricos (en PostgreSQL eso aborta la consulta, no devuelve cero filas), las fechas no parseables y —cuando el filtro declara `options()`— los valores fuera de esa lista. Un valor rechazado tampoco se cuenta ni se pinta como pill, para que la interfaz no anuncie un filtro que no se está aplicando.
- **Los filtros de texto escapan los comodines `LIKE`.** La búsqueda global lo hacía desde la 1.2.0 y los filtros no: un `%` escrito en cualquier filtro seguía actuando como comodín, y `%%%%` forzaba un escaneo completo de la tabla. Ambos comparten ahora el mismo helper (`Support\LikePattern`), con cláusula `ESCAPE` explícita para que el comportamiento sea igual en MySQL, PostgreSQL y SQLite.
- **`hidden()` ya no se confunde con autorización en acciones y presets.** `resolveBulkActions()` descartaba las acciones ocultas para pintar el menú, pero `findBulkAction()` —por donde pasa la ejecución— no filtraba nada: una acción escondida por permisos seguía siendo ejecutable con `$wire.runBulk('deleteAll')`. Igual con `findPreset()`. Además hay un `BulkAction::authorize(Closure)` explícito, que se evalúa en el servidor justo antes de tocar datos y responde `403`.
- **Los IDs de una acción masiva se recortan al alcance de la tabla.** `executeBulkAction()` aceptaba la lista tal cual venía del navegador, así que se podía operar sobre registros que la `query()` del componente nunca habría mostrado (el mismo IDOR que ya se cerró en la edición inline). Ahora se contrastan contra la consulta filtrada, con un tope de `$bulkSelectionLimit` (5.000, redefinible). En modo "seleccionar todo lo que coincide" los IDs dejan de viajar al navegador dentro del payload de confirmación: se resuelven de nuevo en el servidor al confirmar, que además evita mandar millones de identificadores de ida y vuelta.
- **La clave primaria entra en las expresiones vía `@js()`.** Quince puntos entre `datatable`, `card`, `collapse` y `action` la interpolaban dentro de comillas: Blade escapa a entidades HTML, pero el parser del navegador las decodifica antes de que Alpine o Livewire evalúen la expresión, así que una PK de tipo texto con una comilla rompía la cadena y lo que viniera detrás se evaluaba como código. No era explotable con IDs enteros o UUID, pero `setPrimaryKey()` acepta slugs y códigos.
- **`#[Locked]` en el estado de control** (`pendingBulkIdentifier`, `pendingBulkMatching`, `deferredLoading`, `dataLoaded`). Fijar `pendingBulkIdentifier` desde el navegador era la forma de saltarse el flujo de confirmación entero.
- **`selectRange()` se acota a la página visible.** Es la única entrada que recibe una lista de IDs del cliente; un rango, por definición, sale de las filas que se están viendo.
- **El panel de filtros en modo `drawer` es un diálogo de verdad.** Un panel teleportado a `body` con backdrop, pero sin `role="dialog"`, sin `aria-modal`, sin etiqueta y sin retención de foco: con lector de pantalla, el contenido de detrás seguía siendo navegable mientras el panel estaba abierto. Se añade además una directiva propia `x-kore-trap` — `@alpinejs/focus` arrastra `focus-trap` y `tabbable`, que no caben en el presupuesto de bundle, así que el trap son sesenta líneas sin dependencias, con su propio archivo de tests.

#### Changed

- **El export hace eager loading igual que la pantalla.** `exportAs()` construía su query sin `applyEagerLoading()`, que sí está en `buildRowsQuery()`: exportar 10.000 filas con una columna en dot-notation eran 10.000 consultas extra dentro del `streamDownload`.
- **La paginación deja de recorrer todas las páginas.** El cálculo de la ventana iteraba `1..$lastPage` para producir siempre los mismos seis botones: un millón de filas a 25 por página eran 40.000 vueltas de bucle por render. Ahora es aritmética directa, con el mismo resultado (verificado en los tests).
- **`NumberColumn` ya no asume `ext-intl`.** `money()` y `locale()` instanciaban `NumberFormatter` sin comprobar nada, y la extensión no viene activada en las imágenes PHP oficiales: eso era un `Class "NumberFormatter" not found` en mitad de un render. Ahora degrada a `number_format()` (con el código de moneda como prefijo) y el formato de celdas y agregaciones pasa por un único método.
- **`composer.json` declara lo que el paquete usa.** Añadidos `illuminate/database`, `illuminate/pagination`, `illuminate/validation` e `illuminate/contracts` — se usaban por transitividad, sin declararse. `ext-intl` entra como `suggest`, no como requisito duro, porque solo hace falta para `money()`/`locale()` y ahora hay fallback.
- **Los hooks de montaje dejan de duplicarse.** Livewire invoca por su cuenta los métodos `mount{Trait}`, y `mount()` llamaba además a mano a cuatro de los cinco, así que se ejecutaban dos veces por montaje y en un orden que no controlábamos respecto a `configure()`. No era cosmético: los defaults de los filtros y el preset por defecto escriben las mismas propiedades, y la config de columnas y responsive podía deshacer lo que la tabla acababa de pedir. Ahora son métodos ordinarios (`applyColumnSelectConfig`, `applyResponsiveConfig`, `applyQueryStringConfig`, `applyFilterDefaults`, `applyDefaultPreset`), llamados una vez desde `mount()` en un orden explícito: config global → `configure()` → estado inicial derivado.
- **El método de una acción masiva recibe siempre IDs en `string`.** Antes llegaban tal cual venían del cliente y ahora se normalizan, igual que ya hacían `getAllMatchingIds()` y `getRowIds()`. Si comparabas con `in_array($id, $ids, true)` sobre enteros, ajústalo.
- **`setQueryStringEnabled()` dentro de `configure()` no puede activar la sincronización en la primera carga.** No es un cambio de código sino algo que estaba sin documentar: Livewire evalúa `queryString()` **antes** de `mount()`, así que en ese momento `configure()` todavía no ha corrido. Para activarlo por tabla, declara `public bool $queryStringEnabled = true` en la subclase o sobreescribe el nuevo `usesQueryString()`.
- **La definición de la tabla se construye una vez por petición.** `columns()` se invocaba trece veces solo desde el módulo, más las de Blade, y cada llamada reconstruía todos los objetos `Column` con sus closures. Con `filters()` el coste no era de objetos sino de base de datos: se llamaba cuatro veces, y el patrón habitual es `SelectFilter::options(Ciudad::pluck(...))`. Los cachés son `protected`, así que no se serializan y duran lo que dura el request. `hiddenIf()` también se evalúa una sola vez por columna.
- **Los modos `card` y `collapse` dejan de mandar el doble de HTML en cada render.** El servidor no sabe el ancho del contenedor, así que la primera carga emite las dos variantes y el cliente esconde la que sobra; en cuanto Alpine informa del ancho (`setViewport`), cada render manda solo la que toca. El HTML duplicado se paga una vez, no en cada paginación.
- **Los exporters se registran en un mapa.** `resolveExporter()` tenía un `default => new CsvExporter()`: en cuanto alguien añadiera `'xlsx'` a `setExportFormats()`, el botón habría descargado un CSV con extensión `.csv` sin decir nada. Ahora un formato sin registrar lanza `InvalidArgumentException`, y hay `registerExporter()` para añadir los propios.
- **El export avisa cuando se corta.** Al superar `exportMaxRows`, el usuario recibía un archivo truncado sin ninguna señal.
- **CSV conforme a RFC 4180.** `fputcsv()` usaba el escape por defecto de PHP —la barra invertida—, que no es estándar y corrompe cualquier campo terminado en `\`.
- **`matchingQuery()`, `eachMatching()` e `isActingOnAllMatching()`** para escribir acciones masivas que no materialicen el conjunto. `getAllMatchingIds()` sigue ahí, pero sobre dos millones de filas son dos millones de strings en memoria antes de hacer nada.

#### Fixed

- **El listener de teclado capturaba toda la página.** `Ctrl/Cmd+A` dejaba de seleccionar texto en cualquier parte de la aplicación, y con dos datatables en la misma vista una flecha movía la fila activa en las dos a la vez. Ahora el handler sigue en `document` —el foco puede estar en el `<body>` y las flechas deben funcionar igual— pero solo responde si el cursor está sobre la tabla o el foco vive dentro de ella. De paso, un `<select>` enfocado ya cuenta como campo de formulario (antes las flechas cambiaban la opción **y** movían la fila), y `Escape` solo suelta el foco de un input propio, nunca de uno ajeno. Cubierto por `tests/js/datatable-keyboard.test.js`.
- **El badge de filtros activos no aparecía nunca, y de paso dejaba un velo de carga parpadeando sobre la tabla.** Los layouts `popover` (el valor por defecto de `filter_layout`) y `drawer` pedían el conteo con `$wire.getActiveFilterCount()` dentro de un `x-if`. Eso tiene dos consecuencias: en Livewire devuelve una `Promise`, y `Promise > 0` es `false`, así que el badge no se pintaba jamás; y como es una **llamada al servidor**, Livewire marcaba el componente como «cargando» en cuanto Alpine evaluaba la expresión.

  El segundo efecto es el que se veía: la tabla llegaba pintada del servidor y, unos milisegundos después, aparecía el overlay translúcido dejando ver las filas por detrás. Y no una vez — la respuesta hacía que Alpine reevaluara la expresión, que volvía a llamar, en un bucle de re-render. Medido en un caso real: **166 encendidos y apagados del velo en tres segundos**, con los datos visibles todo el rato.

  El conteo se publica ahora en la propiedad `#[Locked] public int $filterCount`, que se lee desde `$wire` de forma síncrona y reactiva —sin viaje al servidor— incluso dentro del `wire:ignore` en el que viven esos triggers. `slide-down` se queda con su variable Blade: su botón está fuera del `wire:ignore`. Hay regresión en `demo/e2e/specs/14-overlay-fantasma.spec.js`, con el patrón antiguo montado al lado como control. El conteo se publica ahora en la propiedad `#[Locked] public int $filterCount`, que se lee desde `$wire` de forma síncrona y reactiva incluso dentro del `wire:ignore` en el que viven esos triggers. `slide-down` se queda con su variable Blade: su botón está fuera del `wire:ignore`.
- **La configuración de export era letra muerta.** `datatable.export.enabled`, `.formats` y `.max_rows` existían en el archivo publicable, aparecían en la documentación y no las leía nadie: el export solo se podía activar tabla por tabla con `setExportEnabled()`. Se aplican en `mount()` antes de `configure()`, así que un ajuste de la tabla siempre gana sobre el global.
- **`search_debounce` no tenía efecto.** El valor viajaba desde `getSearchDebounce()` hasta el toolbar y el input llevaba `debounce.300ms` escrito a mano.
- **«Limpiar filtros» no vaciaba el campo de texto.** `TextFilter` es el único tipo que renderiza un `<input>` plano en vez de un componente Kore, así que era el único sin el `$wire.$watch` que sincroniza el `wire:ignore` de vuelta desde el servidor: `resetAllFilters()`, `resetFilter()`, `applyPreset()` y `clearPreset()` limpiaban `$filters` y dejaban el texto en pantalla, mostrando todos los resultados con el filtro todavía visible.
- **Las celdas `copyable` copiaban entidades HTML.** La expresión pasaba el valor por `e()` dentro de un `{{ }}`, que escapa por segunda vez; el navegador decodifica el atributo una sola vez, así que al portapapeles llegaba `Sanz &amp;amp; Cía`. Ahora usa `@js()`, que es el escape correcto para contexto JavaScript — y también en la clave de feedback, porque una comilla en la clave primaria rompía la expresión entera.
- **Ordenar mantenía la página.** `sortBy()`, `removeSortBy()` y `clearSorts()` dejaban al usuario en la página 7 de un orden nuevo. Vuelven a la 1. Se usa `resetPage()` y no `resetDataScope()` a propósito: ordenar no cambia el conjunto de filas, así que una selección «todo lo que coincide» sigue siendo válida.
- **Filas sin `wire:key` en los modos responsive.** En modo `card` no lo llevaba ninguna tarjeta y en `collapse` solo se emitía si la tabla tenía bulk actions. Livewire reutiliza los nodos por posición, así que el estado Alpine de una celda en edición o de una fila desplegada se quedaba pegado al hueco y no al registro al paginar o filtrar.
- **Una fecha inválida tumbaba el render completo.** `Carbon::parse()` sin protección en `DateColumn`: bastaba una fila con `'0000-00-00'`, una cadena vacía en una columna `string` o cualquier dato heredado sucio para que la tabla entera respondiera 500. Ahora la celda muestra el valor tal cual —feo, pero visible— y la página funciona.
- **Los filtros sobre relaciones generaban SQL inválido.** `TextFilter::make('Autor', 'user.name')` producía `where('user.name', 'like', …)`. Se resuelve como `whereHas`, igual que la búsqueda global, y solo cuando el modelo declara esa relación: una columna cualificada por tabla con un join propio se sigue tratando como antes. Los filtros de rango aplican sus dos condiciones dentro del mismo `whereHas`, porque dos separados no describen un rango.
- **La navegación por teclado no funcionaba sin acciones masivas.** `$rowIds` solo se calculaba si `isSelectionEnabled()`, que exige `hasBulkActions()`, así que en una tabla de solo lectura las flechas, `Enter` y `Espacio` no hacían nada. Son funciones independientes y ya no están atadas.
- **Quitar un filtro no reseteaba nada.** `resetFilter()` solo hacía `unset()`: ni volvía a la página 1, ni soltaba "seleccionar todo lo que coincide", ni desactivaba el preset. Y `resetAllFilters()` dejaba el preset marcado como activo después de borrar sus filtros. Los tres caminos —editar, quitar uno, quitarlos todos— comparten ahora la misma semántica.
- **La opción global `query_string` no se aplicaba nunca.** `$this->queryStringEnabled ?? config(...)` sobre una propiedad `bool` no nulable: el `??` era código inalcanzable.
- **`configure()` se perdía después del primer request.** Las propiedades de configuración (`density`, `responsiveMode`, `primaryKey`, `exportEnabled`, `maxHeight`, `paginationType`…) son `protected` y Livewire no las serializa, así que con `configure()` corriendo solo en `mount()` la tabla volvía a los valores por defecto de la clase en cuanto el usuario paginaba, buscaba o filtraba. En la práctica: el modo `card` dejaba de aplicarse al pasar de página, y `exportAs()` empezaba a responder 403 porque `isExportEnabled()` era `false`. Ahora `configure()` se llama desde `booted()`, que corre en todas las peticiones. **Si tu tabla define `booted()`, tiene que llamar a `parent::booted()`.**
- **La opción global `filter_layout` no se aplicaba nunca.** Livewire vuelca las propiedades públicas en el scope de la vista y ganan sobre los datos que pasa `render()`. `$filterLayout` es pública y vale `null` hasta que alguien llame a `setFilterLayout()`, así que el valor resuelto —con su fallback a config— no llegaba al Blade y siempre se pintaba el `popover`. Los dos valores se igualan ahora en `render()`.
- **`moveRight()` contaba las cabeceras de las dos tablas** en modo `collapse`, donde conviven la tabla y la variante colapsada.
- **Volver a una vista guardada la desactivaba en vez de restaurarla.** Editar filtros a mano no soltaba la vista activa, así que el siguiente clic sobre ella se interpretaba como «salir de la vista» — lo contrario de lo que espera quien la creó. Ahora `resetDataScope()` la suelta, igual que ya hacía con los presets.
- **Dos tablas de la misma clase compartían las columnas ocultas.** La clave de sesión no incluía el nombre de instancia, así que esconder «Email» en una la escondía en la otra. La clave de las tablas sin nombre no cambia, de modo que las sesiones abiertas conservan su estado.
- **`toggleColumnVisibility()` aceptaba campos inexistentes** y los acumulaba en la sesión indefinidamente.
- **Un `default()` de texto se convertía en `0` en las columnas numéricas.** `data_get()` resuelve objetos con `isset()`, así que para un atributo a `null` ya devolvía el default; lo que llegaba a `NumberColumn` era el marcador (`'—'`, `'N/D'`), que se casteaba a `float` y salía como un `0` con pinta de dato real — peor que la celda vacía que se quería evitar. De paso, `Column::getValue()` iguala el caso de las filas que son arrays, donde `data_get()` sí entrega el `null` tal cual: `default()` deja de depender de si la fila es un modelo o un array.
- **El buscador del DataTable no se renderizaba.** Regresión propia de esta misma tanda: al hacer configurable el debounce se interpoló `{{ }}` en el **nombre** del atributo (`wire:model.live.debounce.{{ $x }}ms`), y eso rompe el parser de componentes de Blade — la etiqueta `<x-kore::input>` dejaba de compilarse y acababa literal en el HTML. El test que lo cubría era un falso positivo: buscaba ese texto en el HTML, y estaba precisamente porque nadie lo había procesado. Ahora el atributo se construye en PHP y se pasa por el bag, que sí admite nombres dinámicos.
- **El bundle JavaScript quedaba clavado en el navegador hasta un año.** `@koreScripts` servía `dist/kore-ui.js` con `Cache-Control: immutable, max-age=31536000` desde una URL sin versionar: al publicar una versión nueva de la librería, los navegadores seguían ejecutando la anterior sin forma de invalidarla. La URL lleva ahora la huella del archivo (`?id=`), y una petición sin huella se sirve revalidando.
- **El shift-click nunca seleccionó un rango.** La feature se publicó en la 1.2.0 y no llegó a funcionar: el evento `kore:datatable-rows-updated` se emite en cada render y su manejador borraba el ancla, así que el primer clic provocaba un render, el render borraba el ancla y el shift siguiente ya no tenía desde dónde medir. Ahora solo se suelta si la página cambió de verdad.
- **`pagination_type => 'cursor'` tumbaba la página.** Es una opción documentada, pero la vista de paginación pedía `currentPage()` al paginador y un `CursorPaginator` no lo tiene: reenvía las llamadas que no conoce a su colección, así que salía un «Method Collection::currentPage does not exist». La vista distingue ahora el caso y hay un `setCursor()` para avanzar, porque `nextPage()`/`previousPage()` suman enteros y un cursor no lo es.
- **`Column::width()` no se respetaba.** Sin `table-layout: fixed` el navegador reparte los anchos según el contenido, así que una columna con mucho texto aplastaba a las demás y su contenido se apilaba palabra a palabra. Nuevo `setTableLayout('fixed')` (y `datatable.table_layout` en config), que además da a la tabla el ancho mínimo necesario para que los `width()` sean exactos.
- **El selector de «por página» mostraba un valor distinto del que se usaba.** Una tabla que fije `perPage = 5` en `configure()` quedaba fuera de `per_page_options` y el desplegable enseñaba el primero de la lista. El selector incluye ahora el valor en uso; la validación sigue haciéndose contra la lista de config.
- **`@js()` dentro del atributo de un componente Blade** no se compila en el scope del padre: la directiva llega literal al hijo y se evalúa allí, donde `$rowId` no existe. Afectaba a las celdas copiables y al desplegable de fila del modo `collapse`. Se usa `Js::from()` en esos puntos, que sí se resuelve donde toca.
- **Las cabeceras ordenables no se veían como las demás.** Los navegadores aplican `text-transform: none` a los elementos de formulario, así que el `<span>` dentro del botón de ordenar no heredaba el `uppercase` del `<th>`: en la misma cabecera convivían «Nombre» y «EMAIL».
- **El filtro de rango numérico salía cortado.** Los dos campos no repartían el ancho del contenedor y en el `drawer` se quedaban en 41 px, con el placeholder «Max» recortado a «Ma:». Se les da `flex-1` y se retiran los controles `+/−`, que en un filtro no aportan.

#### Removed

- **`BulkAction::hiddenWhenEmpty()`.** Nunca se consultó desde ninguna vista, y no podía tener efecto: el bloque de acciones masivas solo se pinta cuando hay selección, así que "ocultar cuando no hay selección" era siempre cierto.
- **`getVisibleColumnsForCollapse()`**, sin uso. La vista `collapse` recalculaba lo mismo por su cuenta —y peor: reconstruía la lista de campos colapsados dentro del `in_array`, una vez por columna—; eso también está arreglado.
- **`toggleBooleanEdit()`** del plugin Alpine: el Blade llama a `$wire.updateCell` directamente.
- **`translations.no_results`** del archivo de configuración, que nadie leía. El texto del estado vacío es `empty_text`.

#### Accessibility

- `aria-label` en los botones de acción por fila del modo `inline`, que solo tenían `title` sobre un icono decorativo — `title` no es un nombre accesible fiable y no llega en navegación táctil.
- `aria-label` en el selector de «por página», cuyo texto vivía en un `<span>` adyacente sin asociar.
- Los pares etiqueta/valor de los modos `card` y `collapse` van dentro de un `<dl>`. Estaban sueltos en un `<div>`: HTML inválido, y sin la relación que expone el elemento a las tecnologías asistivas.
- El drawer de filtros se anuncia como diálogo modal y atrapa el foco (ver Security).
- Cada etiqueta de filtro apunta a su campo con `for`/`id`, únicos por tabla y por filtro. Eran `<label>` huérfanos en los cuatro layouts.
- El recuento de resultados se anuncia con `aria-live="polite"`: filtrar o buscar dejaba de ser un cambio silencioso para un lector de pantalla.
- La celda activa en navegación por teclado se resalta. Antes solo se marcaba la fila, así que el recorrido horizontal era invisible.
- `aria-label` en el botón de copiar de `ColorColumn`, en el disparador del menú de acciones por fila y en el botón de copiar de celda: eran controles con solo un icono dentro. La suite E2E cuenta ahora los controles sin nombre accesible y falla si aparece alguno.

#### Docs

- Reescrita la nota **«Badge de filtros activos»** de `docs/data/datatable.md`, que documentaba el arreglo del layout `slide-down` como si cubriera los tres.
- Ampliada la nota de `wire:ignore`: todo campo dentro de uno necesita su `$wire.$watch`, y el filtro de texto monta el suyo.
- `docs/data/hardening.md` documenta las garantías nuevas (filtros, acciones masivas, `@js()` en la clave primaria) y su lista de pendientes refleja por fin lo que queda.
- `docs/data/datatable.md` añade **Filtros sobre relaciones**, **Saneado de valores** y **`hidden()` no es `authorize()`**.
- Reescrita la nota **«Propiedades `#[Locked]`»** de `docs/data/datatable.md`: su regla —"todo lo que configure `configure()` debe ser `#[Locked] public`"— dejó de ser cierta al mover `configure()` a `booted()`. La sustituye **«`configure()` se ejecuta en cada request»**, con el orden de hooks medido.
- Nuevas secciones **Exportación** (con formatos propios) y **Conjuntos grandes** (cómo escribir una acción masiva que no materialice el conjunto).
- `docs/data/hardening.md` añade un apartado de rendimiento por render.
- Nuevas secciones **Segunda linea en la celda**, **Menu por cabecera de columna** y **Vistas guardadas**, esta última con el ejemplo de un `SavedViewStore` contra base de datos.
- **Suite E2E** en `demo/e2e/`: 123 pruebas con Playwright sobre navegador real (Chrome de escritorio y WebKit en iPhone), con captura de cada estado y medición de tiempos por volumen. Ocho de los fallos corregidos en esta entrada los encontró ella, no la suite de unidad — son los que solo se ven cuando el HTML se compila, el JavaScript se ejecuta y el navegador pinta.

### Formulario

**Auditoría del lote de formulario en navegador, y la suite E2E extendida a él.** Veintiún componentes probados en un navegador de verdad —Chrome de escritorio y WebKit en iPhone— con 145 pruebas nuevas. Los fallos de abajo son los que solo aparecen cuando el HTML se compila, el JavaScript se ejecuta y el navegador pinta: ninguno lo veía la suite de unidad, y varios llevaban versiones ahí.

Dos de ellos tienen la misma raíz y merece la pena decirlo antes que nada: **los `id` de los campos sin `name` se generaban con `uniqid()`, así que cambiaban en cada render**. El morph de Livewire empareja los nodos por `id`; si cambia, no reconoce el nodo, lo sustituye por otro y Alpine arranca el componente desde cero. Eso es lo que cerraba el desplegable, el calendario y el selector de color cada vez que **cualquier otro** componente de la página hablaba con el servidor. La librería ya había resuelto exactamente este problema para los gráficos —`ChartContext` y su comentario lo explican— y no se había aplicado a los campos.

#### Added

- **`IdContext`** — ids de campo deterministas para los componentes sin `name`, con un contador por petición acotado al componente Livewire que lo pide. Sustituye a `uniqid()` en los veintiún componentes de formulario. Ver el porqué arriba.
- **`kore-ui.form.translations` y `kore-ui.ui.translations`** — los nombres accesibles de los controles que solo llevan un icono, y los textos visibles que estaban escritos a pelo dentro de las vistas. Son el único nombre que recibe quien navega con lector de pantalla, así que tienen que poder traducirse sin publicar las vistas.
- **`labelable` en `<x-kore::field>`** — para decir que lo que envuelve no es un control de formulario. `<label for>` solo vale contra un control: apuntarlo a un `role="radiogroup"` o a un calendario empotrado deja la etiqueta huérfana y el grupo sin nombre.
- **Censo de consola** (`demo/e2e/specs/32-censo-consola.spec.js`) — recorre **todas** las rutas GET del demo y falla si alguna registra un error de consola o no devuelve 200. Existe porque encontró algo que ningún test de componente podía encontrar: al layout del demo le faltaba `@koreScripts`, así que 92 de las 100 rutas se servían **sin el bundle de la librería**. Ningún plugin Alpine quedaba registrado y todo `x-data="KoreAlgo(...)"` reventaba al iniciar. Es el bloque más lento de la suite (~3 min) y vale lo que cuesta.
- **Suite E2E del lote de formulario** en `demo/e2e/specs/20`–`31`: render y variantes de props de los veintiún componentes, ida y vuelta al servidor, teclado, dos instancias del mismo componente en una página, estado a medio hacer durante un re-render ajeno, escala, invariantes de accesibilidad y una tanda en WebKit móvil.

#### Fixed

- **El desplegable, el calendario y el selector de color se cerraban solos.** Bastaba con que cualquier otro componente de la página hablara con el servidor: el `wire:model.live` de un campo de al lado, un botón, un `poll`. El desplegable se cerraba, la búsqueda a medio escribir se borraba y el calendario volvía al mes de hoy. La causa es la de la cabecera —el `id` no determinista—, no el morph en sí. Con `IdContext` el nodo se actualiza en vez de sustituirse y el estado sobrevive. Regresión en `demo/e2e/specs/28-form-contagio.spec.js`, con el patrón roto montado al lado como control: un componente Alpine cuyo `id` se sortea en cada render, que tiene que seguir perdiendo su estado.

- **El mismo `uniqid()`, en `accordion`, `tab`, `stepper` y `sortable`.** Se dejaron fuera al cerrar el lote de formulario con el argumento de que su síntoma era otro —`aria-controls` en vez de `label[for]`—, y al medirlo resultó ser una diferencia de síntoma y no de causa:

  - **`accordion`**: el panel abierto **se cierra solo** en el primer re-render ajeno, y los `aria-controls` se rompen de forma **acumulativa** — uno más en cada render. Medido: `1 → 0` paneles abiertos, `0 → 1 → 2` referencias rotas.
  - **`tab`**: más callado. El `id` del panel lo pinta Alpine con `x-bind:id`, así que en el DOM queda congelado en el de la primera carga; pero el `aria-controls` del botón lo emite el servidor y estrena valor en cada render. La pestaña sigue funcionando a la vista y la relación botón→panel se rompe a partir del segundo render.
  - **`sortable`**: el id va en el `wire:key`, y uno distinto en cada render obliga a Livewire a **reemplazar todos los items** en cada ida y vuelta en vez de actualizarlos.
  - **`stepper`**: el id viaja en un `x-ref` literal y en la identidad del paso.

  `FieldContext` pasa a llamarse **`IdContext`** —el nombre se quedaba corto en cuanto dejó de ser solo de campos— con un `secuencia('prefijo')` para los que no son campos. El cepo de `IdContextTest` ya no excluye a nadie.

- **Los breadcrumbs colapsables no desplegaban nada.** El bloque metía los `<li>` del separador y de los items ocultos **dentro de otro `<li>`**, y eso es HTML inválido: el parser del navegador cierra el `<li>` exterior al encontrarse el interior, así que el botón y los `<template>` acababan como hermanos **fuera** del elemento que llevaba el `x-data`. `expanded is not defined` en cada expresión, y el desplegable muerto. No daba error de compilación —el HTML que emite Blade es exactamente el que se escribió— y solo se ve mirando el DOM ya parseado. El `x-data` sube al `<ol>` y los items ocultos usan `x-show` en su propio `<li>`, sin envolturas.

- **La leyenda de un gráfico horizontal era decorativa y ruidosa.** `$interactive` excluía al horizontal junto al donut, el gauge y el funnel, pero con una diferencia: a él sí se le pinta la leyenda, y la leyenda son **botones que ocultan series**. Sin el componente Alpine montado, cada uno soltaba un `ReferenceError: isHidden is not defined` al evaluar su `aria-pressed` y no hacía nada al pulsarlo. Ahora se monta cuando hay leyenda; el donut se queda fuera a propósito, porque su interacción es CSS puro.

- **`<x-kore::sortable mode="client">` soltaba un `ReferenceError` por cada item.** `x-sort:item` es una **expresión** de JavaScript para Alpine, y el id se interpolaba sin comillas: un id de texto se lee como una resta de variables. En modo servidor el atributo es `wire:sort:item`, que sí es un valor plano y no se toca.

- **La etiqueta de un campo dejaba de apuntar a su campo a partir del SEGUNDO render.** Corolario del anterior, y peor porque no se ve nunca en una captura: los componentes que van con `wire:ignore` conservan el `id` de la primera carga, mientras la etiqueta —que vive fuera— estrena uno nuevo en cada render. La primera carga estaba bien; a partir de la segunda, `for` apuntaba a un id que ya no existía.

- **Las opciones de un `<x-kore::select>` no se podían cambiar desde el servidor.** Es el patrón del select dependiente —provincia según país— y no funcionaba en el modo por defecto. Las opciones viajaban dentro del `x-data`, que Alpine evalúa una sola vez: al cambiar `:options` el atributo se actualizaba en el DOM y nadie lo volvía a leer. Y como el panel está teleportado a `body`, fuera del alcance del morph, seguía enseñando la lista de la primera carga **para siempre**: ni un segundo re-render la refrescaba. Medido: con el servidor sirviendo cuatro opciones, el panel seguía mostrando dos tras cinco renders. Ahora viajan en un nodo JSON aparte que Livewire sí actualiza, y el plugin lo vigila. El modo nativo nunca tuvo el problema y se queda como control del arreglo.

- **`@js()` dentro del atributo de un componente Blade, otra vez.** La 1.7.x lo corrigió en las celdas copiables y en el desplegable del modo `collapse`, y se dejó sin corregir el desplegable de acciones por fila —el mismo patrón, dos líneas más abajo del que sí se arregló—. La directiva no se compila en el scope del padre: llegaba literal al hijo y acababa en el DOM tal cual, así que **todas las filas mandaban la cadena `@js(data_get($row, $primaryKey ?? 'id'))` en vez de su clave**. Afectaba a toda acción con `->wireMethod()` sin `->confirm()` en modo desplegable. Se usa `Js::from()`. La rama `inline` mantiene `@js()` a propósito —ahí el atributo va sobre un `<button>` normal y la directiva sí se compila— con un comentario para que nadie las unifique copiando la equivocada.

- **Un campo de moneda no admitía céntimos.** `mode="currency"` con el `step` por defecto quedaba en `precision => 0`: la deducción «paso entero → sin decimales» está pensada para un contador de unidades, pero en un importe `step` es cuánto mueven las flechas —un euro por clic es lo normal— y no tiene nada que ver con los decimales. Además `_onKeydown` bloqueaba la tecla del separador decimal, así que tampoco había forma de escribirlos a mano. La documentación promete `precision` 2 y ofrece `:precision="0"` para el caso contrario, que es justo al revés de lo que pasaba.

- **En modo moneda, `<label for>` apuntaba al input oculto.** El id vivía en el campo que lleva el `wire:model`, no en el que el usuario ve y escribe: pulsar la etiqueta no enfocaba nada y el campo visible se anunciaba sin nombre. De paso, ese campo visible **no recibía ninguno de los atributos** escritos en la etiqueta: un `placeholder` funcionaba en modo decimal y se perdía en moneda, sin decir por qué.

- **Restar sobre un campo numérico vacío no hacía nada.** Sin `min` declarado, el punto de partida de `decrement()` era `-Infinity`; el navegador rechaza ese valor en un `<input type="number">` y deja el campo vacío. Sumar sí daba `0`. Ahora los dos arrancan del mismo sitio.

- **`Enter` no abría un `<x-kore::select>`.** El manejador de teclado hacía `preventDefault()` y no hacía nada más cuando el desplegable estaba cerrado — y el disparador es un `<button>`, así que sin ese `preventDefault` el navegador habría disparado el `click` por su cuenta. Desde el teclado solo se podía abrir con las flechas.

- **`<x-kore::input-otp>` no se enteraba de lo que hiciera el servidor.** Era el único componente de formulario sin `$wire.$watch`: la sincronización iba solo de cliente a servidor. El caso normal de un OTP —código incorrecto, `$this->reset('codigo')`, vuelve a intentarlo— cambiaba la propiedad y dejaba los seis dígitos escritos en pantalla.

- **El calendario se abría por hoy aunque hoy cayera fuera de `minDate`/`maxDate`.** El usuario se encontraba una rejilla con todos los días deshabilitados y ninguna pista de hacia dónde navegar: con un rango de marzo abierto en agosto, cinco clics a ciegas en la flecha de mes. Ahora se abre por el límite más cercano.

- **Un campo de `fields` sin `key` tumbaba la página entera.** `Undefined array key "key"` apuntando a una línea del paquete: un 500 por un error de quien declara el schema, y sin decir cuál de los campos falta. Ahora lanza una excepción que lo dice.

- **Una casilla marcada nunca ha pintado su palomita.** El cuadrado se rellenaba de color y ya. La clase estaba escrita —`checked:bg-[url("data:image/svg+xml,…")]`— pero Tailwind v4 nunca la generó: extrae los candidatos del texto del archivo partiendo por espacios en blanco, y el SVG en línea llevaba espacios (`viewBox='0 0 16 16'`) y comillas escapadas dentro del valor arbitrario. Ni error de compilación ni regla en el CSS. Los espacios y las comillas van ahora en `%20` y `%27`, y hay un cepo en `tests/Ui/ClasesArbitrariasTest.php` que barre las vistas buscando el mismo patrón. Lo encontró una captura, no una aserción: cualquier `assertSee` de la clase habría pasado, porque la clase sí estaba en el HTML. El test del arreglo lee el `background-image` calculado en un navegador.

- **Una casilla indeterminada era idéntica a una sin marcar.** `appearance-none` quita el guion que pinta el navegador y solo había estilos para `checked`. La propiedad `indeterminate` sí se ponía —el árbol de accesibilidad la anunciaba como «mixed»— pero a la vista no había absolutamente nada que la distinguiera.

- **El selector de color desbordaba cualquier contenedor estrecho.** Dos motivos independientes: las muestras tenían un tamaño fijo, así que ocho columnas imponían un mínimo de unos 266 px; y el campo del color a mano llevaba `flex-1` sin `min-w-0`, que deja el ancho mínimo en `auto` y no le permite encogerse por debajo de su anchura intrínseca. Medido: 121 px de desborde en una columna de un formulario a dos columnas en un iPhone 13.

#### Changed

- **Diez componentes de formulario emiten ahora los atributos que reciben.** `datepicker`, `time-picker`, `color-picker`, `input-otp`, `tag-input`, `key-value`, `upload`, `rating`, `maskable` y `repeater` no volcaban `$attributes` en ninguna parte, `select` solo lo hacía en modo nativo y `radio-group` filtraba todo menos `class`. Un `data-*`, un `class`, un `style`, un `aria-describedby` o un `x-on:` escrito en la etiqueta se quedaba en el bag y no llegaba al DOM: sin error, sin aviso y sin forma de notarlo salvo mirando el HTML. Se vuelcan en la raíz del componente, excepto `id` —que ya lo usa `$fieldId` sobre el control— y `wire:model`, que vive en el input oculto.

  Ojo a la diferencia, porque es la que hay que conocer: los componentes que envuelven un control nativo (`input`, `textarea`, `number` en modo decimal, `checkbox`, `radio`, `toggle`, `range`, `select` nativo) siguen mergeando en **el control**; los compuestos lo hacen en **la raíz**.

- **Los textos por defecto del lote pasan a `kore-ui.form.translations`.** Estaban escritos en inglés dentro de las vistas y del JavaScript mientras el resto de la librería respondía en español: una misma pantalla mezclaba «Añadir» con «No options found». Cambian los valores por defecto de `Search...` → `Buscar...`, `No options found` → `Sin resultados`, `Choose file` → `Elegir archivo`, y los dos mensajes de validación de `upload`. **Si dependías de los textos en inglés, defínelos en la configuración.**

- **El id derivado de un `name` con corchetes se normaliza.** `items[0]` daba `kore-items[0]`, que obliga a escapar en cualquier selector CSS y en cualquier `label[for]`. Ahora es `kore-items-0`.

#### Accessibility

- **`aria-label` en los controles que solo llevan un icono**, que hasta ahora eran botones sin nombre: los dos botones de paso de `number`, el de limpiar de `input`, `select` y `datepicker`, el aspa de cada chip de un `select` múltiple y de cada etiqueta de un `tag-input`, las muestras del `color-picker` (con su color, y con `aria-pressed`), el asa de arrastre y los dos campos de cada par de `key-value`, los botones de reintentar y quitar de `upload`, el de quitar de `chip`, las flechas de mes y de década del calendario y las cuatro del reloj. `title` no cuenta como sustituto: no se expone de forma fiable en navegación táctil ni en todos los lectores.
- **Cada casilla de un `<x-kore::input-otp>` tiene su nombre** («Dígito 1», «Dígito 2»…). Eran seis campos de un carácter, sin etiqueta y sin nada que los distinguiera.
- **Los dos deslizadores de un `range` doble** tienen nombres distintos. Antes eran dos controles idénticos y sin nombre, y ninguno de los dos era el que apuntaba la etiqueta del campo.
- **El buscador del panel de `select`** tenía `placeholder` y nada más. Un placeholder no es un nombre accesible.
- **`<x-kore::float-label>` no nombraba a su campo**, que es lo único que hace el componente: su `<label>` no tenía `for` ni envolvía al control, así que el campo se anunciaba sin nombre. Ahora se enlazan en tiempo de ejecución, porque el control lo pone quien usa el componente y su id no se puede escribir en la vista.
- **`<x-kore::radio-group>` no tenía nombre.** El `for` de su etiqueta apuntaba a un id que no existía en ninguna parte —etiqueta huérfana— y el `role="radiogroup"` se anunciaba anónimo. Y el caso sin `name`, que es el del ejemplo de la documentación, ni siquiera generaba un id. Ahora el grupo lleva el id y se nombra con `aria-labelledby`.
- **El calendario empotrado dejaba una etiqueta huérfana**: sin disparador no había ningún elemento con el id del campo. El panel lo recibe y se anuncia como `role="group"`.
- **Las estrellas de un `rating` de solo lectura o deshabilitado** salen del recorrido de tabulación y del árbol de accesibilidad. Eran botones tabulables que no hacían nada y que además solo recibían `aria-label` en modo interactivo.

#### Docs

- Nueva sección **«Atributos, `id` y morph»** en `docs/form/getting-started.md`: dónde aterriza lo que se escribe en la etiqueta, por qué el `id` tiene que ser estable y qué pasa con `:options` dinámicos.
- `docs/form/number.md` avisa de que **`prefix` y `suffix` son props de moneda**, aunque el nombre no lo diga: en modo `decimal` se aceptan y no se pintan, y el campo sale sin adorno y sin ningún aviso. La tabla los listaba bajo «Currency Props», que es fácil pasar por alto.
- `docs/form/select.md` documenta el nodo de opciones y el coste de un conjunto grande.
- `docs/form/number.md` corrige lo que decía sobre `precision` en modo moneda.

### Overlay y feedback

**Auditoría del lote de overlay y feedback en navegador.** Modal, drawer, bottom-sheet, confirm, apilamiento, toast y spotlight probados en Chrome de escritorio y en WebKit sobre iPhone, con 44 pruebas nuevas. El lote de formulario dejó dicho dónde estaba el punto débil de la librería —el teleport a `body` frente a lo que Livewire y el navegador hacen con el DOM— y aquí se confirma: **seis de los ocho defectos vienen de que un nodo teleportado a `<body>` deja de estar donde el resto del código cree que está**.

Dos merecen ir por delante:

**El `<body>` se quedaba sin scroll para siempre después de cerrar cualquier modal.** No hacía falta anidar nada ni cerrar en un orden raro: abrir un modal y cerrarlo bastaba para que la página no volviera a desplazarse en toda la visita. El scroll lock lleva un conteo de dueños y el overlay manager se pasaba a sí mismo como dueño; cada expresión de Alpine evalúa sobre un **proxy nuevo** del mismo componente, así que el objeto que llegaba a `unlockScroll` nunca era el que había registrado `lockScroll` y el `Set` no lo encontraba. Sin error de consola, sin nada visible hasta que el usuario intenta bajar. La suite de unidad del scroll lock estaba entera escrita con cadenas —que sí funcionan— y por eso pasaba en verde.

**Un solo `Escape` cerraba el desplegable y el modal a la vez.** El manager escucha `Escape` en `window`, así que recibía el mismo evento que el panel abierto dentro: cerrar un desplegable se llevaba por delante el formulario entero.

#### Added

- **Token `--kore-backdrop`** — el color del velo que oscurece la página bajo un overlay, oscuro en los dos temas y configurable en un solo sitio. Lo usan el overlay manager y el spotlight, que antes llevaba un negro fijo escrito a mano en un `style`.
- **Cepo de paneles teleportados** (`tests/Ui/PanelesTeleportadosTest.php`) — ningún panel que se mueva a `<body>` con controles dentro puede quedarse sin forma de recibir el teclado. Vale un `x-on:keydown` en el propio panel o una escucha en `window` desde la raíz; un panel sin nada enfocable dentro —un tooltip— no entra en el reparto.
- **Tests de unidad del overlay manager** (`tests/js/overlay.test.js`) — las clases de posición de cada tipo y la toma y suelta del scroll del body, incluido el caso de que el lock y el unlock lleguen por instancias distintas del componente, que es lo que pasa en el navegador.
- **Suite E2E del lote** en `demo/e2e/specs/33`–`37`: un formulario completo dentro de un modal, la pila de tres overlays de tipos distintos con sus órdenes de cierre, toast y confirm con su coste en viajes al servidor, el spotlight, y una tanda en WebKit móvil. Con su banco de pruebas en `demo/app/Livewire/E2e/OverlayBed.php`.

#### Fixed

- **El `<body>` se quedaba en `position: fixed` para siempre tras cerrar un modal.** Ver arriba. El dueño del lock pasa a identificarse por una clave de cadena, y `lockScroll`/`unlockScroll` **rechazan** cualquier otra cosa en vez de aceptarla en silencio: convertir el objeto a texto habría hecho que dos dueños distintos compartieran clave y se pisaran el lock, que es un fallo todavía más difícil de ver. Regresión en `demo/e2e/specs/34-overlay-pila.spec.js`, que abre y cierra diez veces y comprueba que la rueda del ratón sigue moviendo la página.

- **Un `Escape` cerraba el panel abierto Y el modal.** Ahora `closeOnEscape` descarta el evento que otro ya ha marcado con `preventDefault()` al consumirlo, que es lo que hacen todos los paneles de la librería. Es un contrato y no una lista: cualquier componente que consuma `Escape` como es debido queda cubierto sin tocar el manager. De paso, `select` y `dropdown` dejan de llamar a `preventDefault()` cuando su panel ya está cerrado —marcaban como atendido un `Escape` que no habían usado, y el segundo `Escape` no cerraba nada—.

- **Un `<x-kore::select searchable>` quedaba abierto y sordo: ni flechas, ni Enter, ni Escape.** El `x-on:keydown` vivía solo en la raíz del componente, y el panel está teleportado a `<body>`: los eventos de dentro no burbujean por la raíz. Como el componente lleva el foco a la caja de búsqueda al abrir, a partir de ese momento **ninguna** tecla llegaba a `onKeydown`. La única forma de usarlo era el ratón. El panel escucha ahora el teclado él mismo.

- **`<x-kore::dropdown>` se quedaba sordo tras la primera flecha,** por lo mismo: la primera pulsación mueve el foco a un item del panel teleportado y a partir de ahí ni seguía bajando ni cerraba con `Escape`. Además, al cerrar con `Escape` el foco se perdía —`$refs.trigger` es el envoltorio, un `<div>` sin `tabindex`, así que `focus()` no enfocaba nada y el siguiente tabulador volvía al principio de la página—: ahora se enfoca el control que el consumidor puso dentro.

- **`<x-kore::color-picker>` no se cerraba con `Escape`.** No tenía manejador: solo se cerraba con un clic fuera, y dentro de un modal ese `Escape` sin dueño llegaba al manager y cerraba el modal entero.

- **El calendario y el selector de hora se quedaban abiertos al tabular fuera del campo,** flotando sobre el formulario mientras el usuario escribía dos campos más abajo. El desplegable ya cerraba con `Tab` desde antes.

- **Un modal centraba el texto de todo lo que se pintara dentro.** El contenedor que centra el panel llevaba `text-center`, y eso se hereda: las etiquetas de un formulario, los párrafos y las celdas de una tabla salían centrados sin que nadie lo hubiera pedido. El centrado horizontal lo da `justify-center` y sigue intacto —medido: quitarlo no mueve el panel ni un píxel—. El diálogo de confirmación, que sí quiere su texto centrado, lo pide en su propia vista y no se ve afectado.

- **En tema oscuro, el velo de un modal ACLARABA la página en vez de atenuarla.** Estaba pintado con `--kore-fg`, que es el color de *texto* y se invierte con el tema: en oscuro salía blanco al 50 % y el fondo acababa más claro que el propio modal. Medido con la luminancia efectiva: 7 → 119 antes, 7 → 3 ahora.

- **El spotlight dejaba desplazarse la página de detrás.** Es modal en todo lo demás —velo, `aria-modal`, foco atrapado— pero el panel se quedaba quieto mientras el contenido pasaba de largo con la rueda. Ahora toma el mismo scroll lock que el overlay manager.

- **La caja de búsqueda del spotlight no tenía nombre accesible.** Era el único control del panel y solo llevaba un `placeholder`, que además cambia según el paso de la búsqueda y desaparece en cuanto se escribe algo.

#### Medido, no cambiado

- **Abrir un overlay cuesta dos viajes al servidor** —uno para abrirlo y otro para que el manager limpie su estado al cerrar— y **un diálogo de confirmación, cinco**: dos para abrirlo (la acción del consumidor y el montaje del diálogo) y tres para responder. Es inherente a que el manager sea un componente Livewire aparte. Un toast cuesta uno desde el servidor y **cero** desde el navegador; el spotlight, cero. Los números están fijados en la suite para que nadie los empeore sin enterarse.
- **La pila funciona y conserva el estado**: tres overlays de tipos distintos, cerrados en cualquier orden, dejan intacto lo escrito en los de abajo y sueltan el body al cerrar el último.
- **El foco queda atrapado** en el modal y en el spotlight, y el panel de un select abierto dentro de un modal queda por encima del velo y sin marcar como inerte.

### Navegación y layout

**Auditoría del lote de navegación y layout en navegador.** Shell, sidebar, navbar, breadcrumbs, acordeón, pestañas, pasos, toolbar, splitter y divider probados en Chrome de escritorio y en WebKit sobre iPhone, con 41 pruebas nuevas. Los defectos se reparten en dos familias:

**Estado del cliente sobre marcado del servidor.** El sub-menú del sidebar que el usuario acababa de abrir se cerraba solo en cuanto CUALQUIER cosa de la página hablaba con el servidor: el estado vive en un atributo del DOM que el servidor también emite, y el morph lo devolvía a su valor. Y las barras del splitter, que las crea el JavaScript y por tanto no están en el HTML del servidor, el morph las borraba por sobrantes —con ellas se iba el layout entero—.

**Un padre que decide antes de que existan sus hijos.** `<x-kore::tab>` y `<x-kore::stepper>` resolvían su selección inicial en un `$nextTick` dentro de `init()`, cuando la lista de items todavía está vacía. La condición no se cumplía, nadie volvía a intentarlo, y el componente se quedaba con las pestañas pintadas y NINGÚN panel debajo —o con los tres círculos del stepper apagados— hasta que el usuario pulsaba.

Además, el contrato de `Escape` que estableció el lote de overlay llega a los cuatro componentes que se habían quedado fuera.

#### Added

- **`hayDuenoPorEncima()`** en el scroll lock — el `Set` de dueños conserva el orden de inserción, así que la lista es también el orden de las capas. Es la única forma de que un componente sepa si algo lo tapa sin tener que conocerlo. La usa el drawer del sidebar para decidir si un `Escape` es suyo o de un modal abierto encima.
- **`kore-ui.ui.translations.resize`** — nombre accesible de la barra del splitter, que se crea desde JavaScript y no tiene etiqueta en Blade donde ponerlo.
- **Suite E2E del lote** en `demo/e2e/specs/38`–`41`: el shell completo con su sidebar de tres niveles, las secciones y el layout, el drawer móvil cruzado con un modal, y el contrato de `Escape` aplicado a los controles que escuchaban en `window`. Con su banco en `demo/app/Livewire/E2e/NavBed.php`.
- **Tests de unidad** de la selección inicial y el teclado de `tab` y `stepper` (`tests/js/tab-stepper.test.js`), del orden de capas del scroll lock y de `closeMobileOnEscape`.

#### Fixed

- **El sub-menú abierto del sidebar se cerraba en el primer re-render ajeno.** El estado de apertura vive en `data-kore-open`, que el servidor emite para que la rama de la ruta activa salga ya abierta sin parpadeo; a partir de ahí lo cambia el usuario. Medido: el morph **no** reemplaza el nodo, pero sí reescribe el atributo al valor del servidor —y también el `aria-expanded` del botón, así que el disclosure se anunciaba cerrado mientras el menú se veía abierto—. `wire:ignore.self` congela esos dos atributos y deja vivo todo lo demás: los labels, los badges y los sub-items que pinte el servidor se siguen actualizando, y el item activo se recalcula al navegar porque `wire:navigate` reemplaza el nodo en vez de hacerle morph. Las dos cosas están medidas y con test.

- **`<x-kore::tab>` sin `selected` no mostraba ningún panel.** Ver arriba. Como `onKeydown` sale pronto cuando no hay ninguna pestaña seleccionada, tampoco funcionaban las flechas: el componente entero era inerte salvo al pulsar con el ratón. La selección inicial pasa a decidirse al registrar cada item, saltándose las deshabilitadas.

- **`<x-kore::stepper>` sin `selected` no activaba ningún paso,** por lo mismo: `getStepStatus` devolvía «pending» para todos, ningún círculo salía resaltado y no se veía el contenido de ningún paso.

- **El splitter se destruía en el primer re-render ajeno.** Sus barras las inserta el JavaScript, así que el morph las veía como nodos sobrantes y las borraba; los paneles colapsaban a su tamaño mínimo y no quedaba forma de recuperarlos. Se vuelven a montar en cuanto desaparecen, conservando lo que el usuario hubiera arrastrado.

- **Un `Escape` cerraba el drawer del sidebar Y el modal abierto encima.** Los dos escuchan en `window`, así que reciben el mismo evento. El drawer cede cuando hay una capa por encima —alguien tomó el scroll lock después que él— y marca la tecla cuando sí es suya, que es lo que hace que el manager ceda en el caso contrario.

- **Lo mismo en `theme-switch`, `speed-dial` y el drawer de filtros del DataTable.** Los tres escuchaban `Escape` en `window` y ninguno marcaba el evento. El arreglo no es marcar desde `window` —ahí el orden lo decide quién se registró antes, que es frágil— sino escuchar en el propio elemento y en el panel teleportado, donde el orden de propagación garantiza que el de más adentro ve la tecla primero.

- **Los tres botones del `theme-switch` en su variante por defecto no tenían nombre accesible.** Solo llevan un icono, y sin `labels` no había ni texto ni `aria-label`: un lector de pantalla anunciaba «botón de radio» y nada más. El nombre coincide con la etiqueta visible cuando la hay, como pide WCAG 2.5.3.

- **La barra del splitter no se anunciaba como lo que es.** Un `role="separator"` con `tabindex` es un «window splitter»: sin `aria-label` ni `aria-valuemin`/`max`/`now`, un lector no dice qué separa ni hacia dónde se está moviendo al pulsar las flechas. El valor sigue ahora a las flechas.

#### Changed

- **El velo del drawer móvil del sidebar usa `--kore-backdrop`,** el mismo token que el overlay manager y el spotlight. Era un negro fijo en el CSS; ahora los tres velos de la librería se cambian en un solo sitio.

#### Medido, no cambiado

- **El foco del drawer móvil queda atrapado** —comprobado con dieciséis tabulaciones seguidas—, y lo hace gracias a la copia de `@alpinejs/focus` que trae Livewire, no a nada que cargue KoreUi (§D.2 del informe de overlay).
- **El orden del tabulador por el shell** es sidebar → navbar → contenido, sin saltos hacia atrás. **No hay enlace de salto al contenido**, así que quien navega con teclado recorre el menú entero en cada página: es una función que falta, no un defecto de lo que hay.
- **El acordeón** abre, cierra, respeta `multiple` y conserva lo abierto tras un morph. **Las migas** colapsables despliegan lo que esconden y no anidan `<li>` dentro de `<li>`.

### Datos y visualización

**Auditoría del lote de datos y visualización en navegador.** Árbol, tablero, gráficos, contadores, barras de progreso, tabla estática, descripciones y línea de tiempo probados en Chrome de escritorio y en WebKit sobre iPhone, con 42 pruebas nuevas.

Casi todo este lote lo pinta el servidor, así que el morph de Livewire no le hace nada — **salvo al árbol**, que es el único que se construye entero desde el cliente. Ahí estaba el defecto grave:

**El `<x-kore::tree>` quedaba MUERTO en cuanto el servidor cambiaba sus nodos.** El árbol se pinta con un `x-for` de Alpine, y el morph reemplazaba el `<template>` por el del servidor —donde esas filas no existen—. A partir de ese momento el componente dejaba de reaccionar del todo: medido, el estado pasaba a nueve filas mientras el DOM se quedaba en siete, y ni tocando el estado a mano volvía a pintar.

El otro hallazgo tiene más recorrido del que parece: **el kanban soltaba un `ReferenceError` por cada tarjeta si los ids eran de texto**, exactamente el mismo fallo que `<x-kore::sortable>` ya tenía corregido. Con ids numéricos funcionaba de casualidad, y así llevaba desde que se escribió.

#### Added

- **`caption` y `captionHidden` en `<x-kore::table>`** — el nombre de la tabla. No se podía poner: `$attributes` se vuelca en el `<div>` envolvente, que no tiene rol y por tanto no acepta nombre, así que un lector anunciaba «tabla, 3 columnas, 3 filas» y nada más.
- **Teclado completo en `<x-kore::tree>`** — flechas arriba y abajo por los nodos visibles, derecha e izquierda para abrir y cerrar ramas o subir al padre, `Home`/`End` y `Enter` para elegir. No había ninguno.
- **`kore-ui.ui.translations.tree`, `tree_expand` y `tree_collapse`** — nombre del `role="tree"` y verbos de sus chevrones.
- **`ariaLabel` en `<x-kore::tree>`** — para nombrar un árbol concreto cuando hay varios en la misma página.
- **Suite E2E del lote** en `demo/e2e/specs/42`–`45`: el árbol contra el morph y su escala, el tablero, gráficos y contadores, listas, y una tanda en WebKit móvil. Con su banco en `demo/app/Livewire/E2e/DataBed.php` y `KanbanBed.php`.
- **Tests de unidad** de `tree` —de dónde saca los nodos y cómo se recorre— y de `stats`.

#### Fixed

- **`<x-kore::tree>` quedaba muerto tras un morph que cambiara sus datos.** Ver arriba. La raíz pasa a llevar `wire:ignore` para que el morph no toque los nodos que pinta Alpine, y los datos viajan en un nodo JSON aparte que Livewire sí actualiza y que el componente vigila con un `MutationObserver`. Es el mismo mecanismo que resolvió las opciones de `<x-kore::select>`. De paso, los nodos dejan de viajar **dos veces** en el HTML: estaban en el `<script>` y otra vez dentro del `x-data`, lo que en un árbol de dos mil nodos son 81 kB de más.

- **`<x-kore::kanban>` soltaba un `ReferenceError` por tarjeta con ids de texto.** `x-sort:item` es una **expresión** de JavaScript para Alpine: `x-sort:item="tarea-a"` se lee como una resta de variables. Se emite con `Js::from()`, igual que en `<x-kore::sortable>`.

- **`<x-kore::stats>` ignoraba `prefers-reduced-motion`.** Un número que trepa durante un segundo es justo la clase de animación que esa preferencia pide desactivar; medido con ella activa, el contador seguía subiendo desde cero. Ahora enseña el valor directamente. El resto de la librería ya la respetaba.

- **El tablero no tenía ninguna semántica.** Columnas y tarjetas eran `div` sin rol: para un lector de pantalla no había columnas ni tarjetas, solo texto suelto. Cada columna es ahora una `list` con el nombre de la columna, y cada tarjeta un `listitem`.

- **Los chevrones del árbol se llamaban todos «Toggle expand».** El mismo nombre para cada rama, y en inglés: un lector oía lo mismo una vez por nodo sin saber cuál estaba abriendo. Ahora dicen «Abrir Documentos» / «Cerrar Documentos».

- **Los nodos del árbol no se podían enfocar.** Todos los `treeitem` llevaban `tabindex="-1"` y el único enfocable de cada fila era el chevrón, así que con `selectable` no había forma de elegir un nodo sin ratón. Ahora sigue el patrón de un `tree`: una sola parada del tabulador y flechas dentro.

- **Al árbol le faltaba media semántica.** `role="tree"` sin nombre, `aria-level` puesto en el envoltorio en vez de en el `treeitem` —donde no significa nada— y `aria-expanded=""` vacío en los nodos sin hijos, que es un valor inválido.

- **La caja de filtro del árbol no tenía nombre accesible.** Solo llevaba `placeholder`, que desaparece en cuanto se escribe algo.

#### Medido, no cambiado

- **El árbol no virtualiza.** Con 100 raíces × 20 hijos pinta **2.100 filas y 12.810 nodos de DOM** para dejar 100 a la vista, y manda 79 kB de JSON. No es un defecto —el componente no promete otra cosa— pero conviene tener el número, como se hizo con las 10.000 opciones del select.
- **El tablero no se puede recorrer con el teclado**: cero controles enfocables dentro. Solo se opera arrastrando, así que quien no use ratón no puede mover nada. Añadir teclado es una función nueva, no un arreglo; hay un test que fija el estado actual para que el día que se añada haya que actualizarlo a conciencia.
- **Los gráficos están bien resueltos para lectores de pantalla**: el `<svg>` va `aria-hidden` y al lado se pinta una tabla con los mismos datos, con el `aria-label` del consumidor como `<caption>`. La leyenda oculta series y lo anuncia con `aria-pressed`.
- **Todo lo que pinta el servidor sigue a los datos** y sobrevive a un morph ajeno: gráficos, tablero, tabla, descripciones y línea de tiempo.

### Interacción

**Auditoría del lote de interacción en navegador.** Carrusel, listas reordenables, doble lista, tooltip, portapapeles, menú desplegable y botón de acciones rápidas probados en Chrome de escritorio y en WebKit sobre iPhone, con 31 pruebas nuevas.

El reparto de los doce defectos se explica con una frase: **de los ocho componentes, solo uno se construye desde el cliente sobre HTML que el servidor también emite, y ese uno concentra cinco de los doce**.

**Cualquier morph de Livewire dejaba el `<x-kore::carousel>` en blanco.** El carrusel escribe el ancho de cada diapositiva como estilo en línea, y nada de eso existe en el HTML del servidor: el morph lo borraba y las diapositivas pasaban de 768 px a unos 50 —el ancho de su contenido—. La segunda parte era peor: con los anchos borrados, «siguiente» desplazaba el carril con la cuenta vieja y la vista se quedaba vacía.

El otro defecto grave es de los que no se ven en una captura: **el `<x-kore::tooltip>` no existía para un lector de pantalla.** El panel vive teleportado a `<body>`, sin `id`, y nadie apuntaba a él — así que el `role="tooltip"` no le llegaba a nadie.

Informe completo en `docs/interaccion-auditoria.md`.

#### Added

- **Botón de parar y reanudar en `<x-kore::carousel autoplay>`** — WCAG 2.2.2 pide poder detener cualquier movimiento automático de más de cinco segundos, y `pauseOnHover` solo sirve con ratón. El autoplay se para además cuando el foco entra en el carrusel.
- **Teclado en `<x-kore::carousel>`** — flechas izquierda y derecha para moverse entre diapositivas. No había ninguno. Las flechas que el usuario escribe dentro de un campo no se tocan.
- **`ariaLabel` en `<x-kore::carousel>` y en `<x-kore::dropdown>`** — para nombrar uno concreto cuando hay varios en la misma página.
- **`kore-ui.ui.translations`: `carousel`, `carousel_previous`, `carousel_next`, `carousel_go_to`, `carousel_pause`, `carousel_play`, `copy`, `copied`, `menu`, `speed_dial`, `transfer_search` y `transfer_select`** — los textos de este lote que seguían en inglés, más los nombres accesibles que faltaban.
- **Suite E2E del lote** en `demo/e2e/specs/46`–`49`: el carrusel contra el morph, las tres listas reordenables, los flotantes y una tanda en WebKit móvil. Con su banco en `demo/app/Livewire/E2e/InteraccionBed.php`.
- **Tests de unidad de `carousel`** —el remontaje, el foco dentro de una diapositiva, `inert` y el autoplay— y del nodo JSON de `transfer` y `order-list`.
- **Cepo `tests/Ui/RolesQueMientenTest.php`** — ningún `role="tab"` sin un `role="tabpanel"` al otro lado, y ningún `role="menuitem"` puesto en un envoltorio en vez de en el control.

#### Fixed

- **`<x-kore::carousel>` se destruía con cualquier morph ajeno.** Ver arriba. Un `MutationObserver` sobre el carril reaplica tamaños y posición cuando el morph se los lleva, igual que hacen las barras de `<x-kore::splitter>`. Reaplicarlos vuelve a disparar el observador, pero entonces la condición ya no se cumple y no hay bucle.

- **El carrusel no contaba las diapositivas que llegaban después.** `totalSlides` se calculaba en `init()`, que corre una vez: con el servidor añadiendo una, el estado se quedaba en cuatro con cinco en el DOM, la última era inalcanzable y faltaba un indicador. Ahora las recuenta el mismo observador.

- **Un botón dentro de una diapositiva no recibía el foco.** El carril llevaba `x-on:pointerdown.prevent` para que arrastrar no seleccionara texto, y ese `preventDefault` impide también el enfoque: medido, `document.activeElement` se quedaba en `<body>` al pulsar. Ahora lo decide el JavaScript, que no arranca el arrastre si el gesto empieza sobre un control.

- **Las diapositivas fuera de la ventana seguían en el tabulador.** Un `overflow-hidden` las recorta pero no las saca del recorrido del foco: se enfocaba un botón que nadie veía, y la página no desplazaba a ninguna parte porque el carril se mueve con `transform`. Llevan `inert` mientras están fuera.

- **`<x-kore::tooltip>` no estaba conectado con el control que lo dispara.** Ver arriba. El texto va ahora en un `<span class="sr-only">` del propio componente y el JavaScript cuelga un `aria-describedby` del control que el consumidor puso en el slot — no del envoltorio, que es un `<div>` sin rol y que ningún lector anuncia. El panel flotante pasa a ser decorativo (`aria-hidden`), o el texto se leería dos veces.

  **Por qué el texto no está en el panel**, que era lo natural: darle un `id` al nodo teleportado rompía el DataTable. El panel acaba en `<body>` mientras el `<template>` que lo declara sigue en su celda, así que al re-renderizar la tabla el morph emparejaba por id el nodo del HTML nuevo con el que ya colgaba de `<body>` y lo arrancaba de su ámbito de Alpine — `ReferenceError: show is not defined` con veinticinco tooltips en una página. Asignar el id desde JavaScript tampoco valía: pedir `$refs.tooltip` durante el montaje dejaba paneles sin ámbito por su cuenta. Lo cazó el censo de consola.

- **`<x-kore::tooltip>` no se cerraba con `Escape`.** WCAG 1.4.13 pide poder descartar lo que aparece al pasar por encima o al enfocar, sin mover el foco. Se escucha en el elemento y la tecla solo se marca si había algo abierto, que es el contrato del `Escape` de la librería.

- **`<x-kore::order-list>` y `<x-kore::transfer>` no se enteraban cuando el servidor cambiaba `:items`.** Los recibían dentro del `x-data` con `wire:ignore` en la raíz: medido, el servidor pasaba de cuatro elementos a cinco y los dos seguían enseñando cuatro para siempre. Ahora los items viajan en un nodo JSON de fuera que Livewire sí actualiza. En el `order-list`, al releer se reconcilia el orden: lo que el usuario había movido se queda donde estaba y lo nuevo se añade al final.

- **El disparador de `<x-kore::dropdown>` no decía si estaba desplegado.** Ni `aria-expanded` ni `aria-haspopup`, ni siquiera con el menú abierto. Los pone el JavaScript sobre el control del slot y los reaplica al abrir y cerrar.

- **Los tres botones de `<x-kore::clipboard>` no tenían nombre**, y el campo de solo lectura de la variante `input` tampoco. El de la variante `icon` se apoyaba en un `title`, que no se expone de forma fiable en táctil ni en todos los lectores. Además, **el «copiado» era solo visual**: ahora lo anuncia un `role="status"`.

- **Los indicadores del carrusel decían ser pestañas.** `role="tablist"` con sus `role="tab"` y cero `role="tabpanel"` al otro lado — y con `numVisible` mayor que uno cada punto lleva a un grupo de diapositivas, así que la relación uno a uno que un `tablist` promete no puede existir. Son botones con `aria-current`. El `role="region"` del contenedor tampoco tenía nombre, y las diapositivas no eran nada: ahora son `role="group"` con `aria-roledescription="slide"` y su posición.

- **Los elementos de `<x-kore::speed-dial>` envolvían a sus controles.** `role="menuitem"` estaba en el `<div>` de fuera, con el `<button>` dentro: un menuitem no puede contener un control. El rol pasa al control y el envoltorio a `role="none"`.

- **El menú de `<x-kore::dropdown>` no tenía nombre y su separador no era un separador.** `role="menu"` sin `aria-label` se anuncia como «menú» y nada más; el separador era un `<div>` con un borde, decoración que nadie anunciaba.

#### Medido, no cambiado

- **`<x-kore::sortable>` en modo servidor no tiene ni un control enfocable**, igual que el tablero. Medido: 0, frente a 10 del `order-list` y 8 del `transfer`. Añadirle teclado es una función nueva, no un arreglo; hay un test que fija el número en cero para que el día que se añada haya que actualizarlo a conciencia.
- **`<x-kore::order-list>` y `<x-kore::transfer>` sí se operan sin ratón**, y conviene decirlo: el primero con sus botones de subir y bajar —el cambio llega al servidor, comprobado de punta a punta— y el segundo marcando con `Espacio`. Las casillas del transfer llevan `pointer-events-none`, que parece dejarlas inertes y no lo hace: el teclado no pasa por ahí.
- **Los ids de `order-list` y `transfer` ya estaban bien.** El `x-sort:item` del primero es una expresión evaluada dentro del `x-for` y el segundo no usa `x-sort` en absoluto. Probado con ids de texto desde el principio.
- **Tres `role` sin nombre en componentes de otros lotes**, comprobados a mano y sin tocar: el `role="dialog"` del overlay manager —el más serio: cada modal se anuncia como «diálogo» y nada más—, los dos `role="listbox"` anidados del select y el `role="menu"` del theme-switch.

### Presentación

**Auditoría del lote de presentación en navegador.** Alertas, insignias, avatares, tarjetas, chips, iconos, teclas, indicadores de carga, esqueletos, estados vacíos, botones y booleanos probados en Chrome de escritorio y en WebKit sobre iPhone, con 17 pruebas nuevas. Es el último lote de componentes: con él, la librería entera queda auditada.

Aquí casi nada tiene estado, así que lo que decide el lote es **el color**. Casi todos estos componentes tienen variantes que pintan texto de un color sobre un fondo del MISMO color al diez por ciento, y eso no lo ve un aserto de clases ni se aprecia en una captura: medido componiendo el fondo real capa a capa, **doce de las veintiuna combinaciones de un badge y veinticuatro de las treinta y nueve de un botón** estaban por debajo de AA.

Lo interesante es que **la solución ya existía en la librería y solo se había aplicado a un color de cinco**: el token `--kore-warning-text`, con una nota en el CSS diagnosticando exactamente este problema. Los que se quedaron fuera estaban peor — `success` en 3,01 frente al 2,07 que motivó el token.

Informe completo en `docs/presentacion-auditoria.md`.

#### Added

- **Tokens `--kore-primary-text`, `--kore-success-text`, `--kore-info-text` y `--kore-destructive-text`**, en los dos temas, junto al `--kore-warning-text` que ya existía. Los valores están calibrados midiendo: cada uno es la primera luminosidad que pasa 4,5 sobre el tinte del mismo color al 10 % y al 20 %. El método se validó solo — para `warning` reproduce el `0.52` que el token ya tenía.
- **`live` en `<x-kore::alert>`** — `assertive`, `polite` u `off`, para decidir si el aviso interrumpe al lector.
- **`trueLabel` y `falseLabel` en `<x-kore::boolean>`** — para cuando el booleano significa «Activo/Inactivo» y no «Sí/No».
- **`ariaLabel` en `<x-kore::button-group>`** — nombre del grupo.
- **`announce` en `<x-kore::loading>`** — para callarlo dentro de un componente que ya anuncia su estado.
- **`kore-ui.ui.translations`: `yes`, `no`, `loading`, `close`, `button_group` y `presence_online` / `_offline` / `_busy` / `_away`.**
- **Suite E2E del lote** en `demo/e2e/specs/50`–`52`: el contraste en los dos temas, la semántica y el movimiento, y una tanda en WebKit móvil. Con su banco en `demo/app/Livewire/E2e/PresentacionBed.php`.
- **Cepo `tests/Ui/ColorComoTextoTest.php`** — ningún color de la paleta puede usarse como texto sobre su propio tinte, y los cinco tokens `-text` tienen que existir en los dos temas.

#### Fixed

- **El color base de la paleta se usaba como texto sobre su propio tinte.** Ver arriba. Las variantes `soft`, `outline`, `ghost` y `link` de `<x-kore::badge>`, `<x-kore::chip>`, `<x-kore::alert>` y `<x-kore::button>` pasan a usar el token `-text`, y también las iniciales de `<x-kore::avatar>`, que van al veinte por ciento. El mismo cambio se aplicó a once vistas de otros componentes con el mismo patrón —sidebar, stats, tree, tab, tag-input, spotlight y cuatro del DataTable—: 26 sustituciones en total.

- **`<x-kore::alert>` interrumpía al lector aunque llevara ahí desde el principio.** `role="alert"` es una región *assertive*: interrumpe lo que se esté leyendo. Medido: doce alertas estáticas en una página, las doce anunciándose de golpe al abrirla. Ahora el rol se pone solo cuando la alerta es dinámica de verdad.

- **`<x-kore::loading>` no se anunciaba.** Cero elementos con `role="status"` o `aria-live` en una página con cuatro indicadores: sin texto visible, la animación era la única señal de que algo estaba pasando. Trae además una prop **`announce`** para quien ya anuncia su propio estado: el DataTable la usa, porque su paginación tiene otro `aria-live` con el recuento y con los dos un lector oía «Cargando» y luego «Mostrando 1 de 1» en cada filtrado.

- **Las animaciones del lote ignoraban `prefers-reduced-motion`.** El CSS ya tenía su bloque para esa preferencia; el spinner, los puntos, el pulso, el brillo del esqueleto y el pulso de presencia del avatar se habían quedado fuera. El spinner se **ralentiza** de 1 s a 3 s en vez de apagarse —es la única señal de que algo pasa—; lo demás se apaga entero.

- **Dos objetivos táctiles por debajo del mínimo.** El botón de quitar de `<x-kore::chip>` medía 18×18 y el de cerrar de `<x-kore::alert>`, 20 px de ancho. WCAG 2.2 pide 24×24.

- **La descripción de `<x-kore::alert>` bajaba su propio contraste** con un `opacity-90`: fallaba en once de las doce combinaciones, frente a ocho del título.

- **`<x-kore::boolean>` decía «true» y «false»**, en inglés y sin significado: un lector anunciaba «imagen, true» y nada más.

- **La presencia de `<x-kore::avatar>` era solo color.** Los cuatro estados sin texto ni `aria-label`: para quien no distingue el verde del rojo, «en línea» y «ocupado» se veían idénticos.

- **`<x-kore::button-group>` no era un grupo** para un lector: ni `role="group"` ni nombre.

#### Medido, no cambiado

- **Las variantes `solid` no llegan a AA**, y es la decisión pendiente que deja este lote: pintan el color `-fg` —casi blanco— sobre el color pleno, y ninguno de los cuatro pasa. Medido en tema claro: `primary` 4,41 · `destructive` 4,39 · `info` 3,42 · `success` 3,17. Arreglarlo pide mover la paleta base, y esos mismos tokens pintan gráficos, iconos, estados del sidebar y media tabla.
- **`muted` se queda en 4,48**, a dos centésimas.
- **El `button-group` NO se descoloca con el morph**, que era la sospecha: el cálculo de las esquinas es CSS puro, así que lo rehace el navegador solo. Medido con un cuarto botón llegando desde el servidor.
- **El chip que el usuario oculta no resucita** con un morph ajeno.

---

## [1.7.1] — 2026-08-18

**El bundle que se sirve al navegador iba un día por detrás de las fuentes.** `repeater`, `key-value`, `transfer` y `order-list` se publicaron en la 1.7.0 con su Blade y su JavaScript dentro del paquete, pero `dist/kore-ui.js` — el archivo que el ServiceProvider sirve tal cual en `/vendor/kore-ui/kore-ui.js` — se había construido por última vez el día anterior al commit que añadió esos cuatro módulos. Los componentes existían en el paquete y no existían en el navegador.

### Fixed

- **`<x-kore::repeater>`, `<x-kore::key-value>`, `<x-kore::transfer>` y `<x-kore::order-list>` reventaban en runtime** con `Uncaught ReferenceError: KoreRepeater is not defined` y el `Alpine Expression Error` que le sigue (`rows is not defined` en el `x-for` del componente). `resources/js/index.js` sí los registraba; el `dist/` versionado no los contenía. Reconstruido: los 33 `Alpine.data` y los 2 `Alpine.store` del entry están ahora en el bundle. No hay cambios de código en los componentes — la 1.7.0 ya los traía bien escritos, solo no llegaban.

### Changed

- **CI verifica que `dist/` no se quede atrás.** El paso `npm run build` existía desde siempre, pero construía a un directorio de usar y tirar que nadie comparaba con el commiteado, así que un dist atrasado pasaba el CI entero sin una sola advertencia. Ahora hay dos redes: `npm run dist:check` (`scripts/dist-sync.mjs`) corre **antes** del build y comprueba que cada `Alpine.data`/`Alpine.store` del entry aparece en el bundle versionado — corre antes a propósito, porque después del build el dist siempre está fresco y la comprobación sería ciega; y un `git diff -- dist/` **después** del build atrapa lo que la comprobación semántica no ve, que son los cambios dentro de un componente ya registrado.
- **Presupuesto de bundle: 35 kB → 37 kB gzip.** No es código nuevo entrando: son los cuatro componentes de la 1.7.0 pesando por primera vez. El presupuesto llevaba una versión midiendo un bundle al que le faltaban cuatro componentes que la librería ya prometía en su documentación.

---

## [1.7.0] — 2026-07-15

**Sistemas compuestos (Fase 0 + Fase 1).** El foco deja de ser átomos sueltos y pasa a piezas de varios subcomponentes que resuelven un caso de uso entero: vista de detalle read-only, editores de formulario dinámicos y arrastrar-y-soltar (listas, repetidores, transfer y Kanban).

### Added

- **`<x-kore::descriptions>`** y **`<x-kore::descriptions.item>`** — vista de detalle read-only (pares etiqueta/valor), el espejo del formulario y compañero del DataTable. Dos APIs: subcomponentes (valores con formato: badge/boolean/avatar) y atajo data-driven `:items`. Soporta `columns` (1-3), `layout` (horizontal/vertical), `bordered` y `size`. Los items heredan del contenedor vía `@aware` (primer uso de `@aware` en la librería, justificado por ser un compuesto no interactivo). Ver [docs/ui/descriptions.md](docs/ui/descriptions.md).
- **`<x-kore::result>`** — bloque de estado del resultado de una operación o página (`success`, `info`, `warning`, `error`, `404`, `403`, `500`), con icono y color automáticos por estado y slot `action`. Distinto del `empty-state` (sin datos): Result comunica el desenlace de una acción o una ruta. Ver [docs/ui/result.md](docs/ui/result.md).
- **`<x-kore::key-value>`** — editor de pares clave-valor dinámicos (metadata, settings, cabeceras) con añadir/eliminar y reordenar opcional (`x-sort`). Plugin Alpine `KoreKeyValue`, mismo motor de array que `tag-input`; sincroniza un objeto `{clave: valor}` con `$wire.$set` y va en `wire:ignore`. Ver [docs/form/key-value.md](docs/form/key-value.md).
- **`<x-kore::sortable>`** y **`<x-kore::sortable.item>`** — lista reordenable por arrastre sobre **`wire:sort`** (SortableJS ya viene embebido en Livewire 4, sin dependencias nuevas). Modo `server` (round-trip) o `client` (`x-sort`), tirador opcional y `group` para arrastrar entre listas (base de Kanban/Transfer). El estado lo pone el host. Ver [docs/ui/sortable.md](docs/ui/sortable.md).
- **`<x-kore::repeater>`** — grupos de campos repetibles (ítems de factura, variantes) definidos por un schema `fields` (`text`/`number`/`select`), con añadir/eliminar, reordenar (`x-sort`) y `min`/`max`. Generaliza `key-value` a N campos; plugin Alpine `KoreRepeater`, sincroniza un array de objetos con `$wire.$set`. Ver [docs/form/repeater.md](docs/form/repeater.md).
- **`<x-kore::transfer>`** — selector de doble lista (disponibles ↔ seleccionados) con casillas, búsqueda por panel y botones de mover uno/todos. Para asignar roles, permisos o columnas. Plugin Alpine `KoreTransfer`, sincroniza el array de valores seleccionados. Ver [docs/ui/transfer.md](docs/ui/transfer.md).
- **`<x-kore::order-list>`** — lista única reordenable por arrastre (`x-sort`) o botones ↑/↓, sincroniza el array de valores en su orden. Plugin Alpine `KoreOrderList` con reconciliación de valores. Ver [docs/ui/order-list.md](docs/ui/order-list.md).
- **Kanban** — módulo nuevo. Clase base Livewire **`KoreUi\Kanban\KoreKanban`** (se extiende como `KoreDataTable`: implementas `columns()`, `cards()`, `persistMove()`) más los componentes anónimos **`<x-kore::kanban>`**, **`kanban.column`** y **`kanban.card`** para un board data-driven. Las tarjetas se arrastran dentro y entre columnas con `x-sort` (embebido en Livewire 4, sin dependencias); el drop dispara `moveCard($cardId, $position, $toColumn)`. Ver [docs/kanban/getting-started.md](docs/kanban/getting-started.md).

## [1.6.0] — 2026-07-14

**Gráficos.** Barras, líneas, áreas y donut, **sin ninguna librería de JavaScript**: la geometría se calcula en PHP y el servidor devuelve el `<svg>` ya dibujado.

### Added

- **`<x-kore::chart>`** y sus marcas: `chart.line`, `chart.area`, `chart.bar`, `chart.donut`, `chart.axis-x`, `chart.axis-y`, `chart.legend` y `chart.tooltip`. Ver [docs/chart/getting-started.md](docs/chart/getting-started.md).
- **`Kore\Charts\`**: el motor de geometría, en PHP puro y sin depender de Blade ni de JavaScript. Sirve igual para un PDF, un email o un export.
- **Paleta categórica `--kore-chart-1` … `--kore-chart-8`**, en claro y en oscuro.

- **Eje X temporal.** Ver [docs/chart/time-axis.md](docs/chart/time-axis.md).

  Pásale objetos `DateTime` o `Carbon` y la escala se detecta sola. **No es una feature estética: es una corrección de honestidad.** Hasta ahora había que pre-formatear la fecha en PHP, el gráfico la trataba como una categoría, y una categoría se coloca por su **ordinal en el array** — así que un sensor que estuvo tres días caído se dibujaba con sus lecturas pegadas una a otra. La caída desaparecía. El gráfico se veía perfectamente bien y mentía.

  Y el eje X tenía el defecto que el eje Y ya no tiene: adelgazaba las etiquetas **saltando de N en N**, así que 90 días con 12 etiquetas daban los días 1, 9, 17, 25… — que es exactamente el «1.224» del que presume de haberse librado. Ahora los ticks caen en **fronteras de calendario**, con la tabla de `d3-time`, y hay un test de paridad contra un fixture generado con `d3-time` de verdad: **dan exactamente los mismos ticks que d3**.

  **Aquí PHP no es un mal menor: es estrictamente mejor que JavaScript.** `DateTimeImmutable` hace aritmética de calendario, así que un día de 23 o de 25 horas le sale bien por construcción. d3 tiene que volver a truncar después de cada salto, porque en JavaScript una fecha *es* un número de milisegundos y sumarle un día son 86.400.000 ms — que en el cambio de hora te dejan a las 23:00. Y la zona horaria ya está en el servidor, así que no hace falta un adaptador de fechas: el de Chart.js pesa tanto que hace que su *tree-shaking* salga **más caro que su bundle completo**.

  De propina, y casi inevitable: **el intervalo que elige los ticks es el mismo con el que hay que agrupar la consulta**. Es el `$__interval` de Grafana, en Eloquent, y no lo tiene nadie en Laravel:

  ```php
  $paso = TimeTicks::interval($desde, $hasta, count: 8);   // → «1 week»

  Order::selectRaw('DATE_TRUNC(?, created_at) AS bucket, SUM(total)', [$paso->unit()])
       ->groupBy('bucket');
  ```

- **Eje X lineal** (`scale="linear"`), para valores continuos. `auto` **nunca** lo elige por su cuenta: unos años escritos como enteros son categorías, no una recta numérica.

- **Rampas de color secuencial (`--kore-seq-1…7`) y ordinal (`--kore-ord-1…7`)**, en claro y en oscuro. La secuencial codifica una **magnitud** (el valor de una celda); la ordinal solo codifica un **orden** (la etapa de un embudo, donde el valor ya lo dice la geometría). El color se **cuantiza**, jamás se interpola en PHP: el servidor reparte el valor en escalones y emite el número; el color lo pone el CSS. Así el gráfico sigue repintándose solo al cambiar de tema.

- **Zoom, pan y slider de contexto** — todo resuelto en el servidor. Ver [docs/chart/zoom.md](docs/chart/zoom.md).

  **El cliente manda dos números.** La ventana son dos porcentajes del dominio completo —no dos fechas—, y eso es lo que hace que el zoom no cueste ni una línea de matemática de escalas en el cliente: componer un zoom sobre otro es una regla de tres, y recortar el espacio 0–100 a un tramo es un **remapeo afín** que funciona igual con categorías, con fechas y con números. El servidor invierte el dominio (con `LinearScale::invert()`, que llevaba escrita desde el primer día **sin que la usara nadie**), elige los ticks nuevos, reescala el eje Y y devuelve el `<path>`. Livewire lo morphea.

  Lo más visible: **al ampliar, el eje temporal cambia de unidad solo.** Un año dice trimestres; ampliada una semana, el mismo eje dice días. Un zoom en el cliente tendría que recalcular esos ticks — o sea, portar `TimeTicks`, `TimeInterval` y `TimeFormat` a JavaScript. Todo el JS del zoom son **~60 líneas y 0,7 kB gzip**.

  **El estado vive en el componente Livewire**, no en Alpine. Sale gratis que sobreviva al morph sin ningún hook, que se comparta por URL con `#[Url]` y que se testee con `Livewire::test()`.

  **Y el eje Y se reescala sobre lo que se ve** (el `filterMode: 'filter'` de ECharts): ampliar una semana de un año y dejar el eje llegando al máximo anual deja el gráfico aplastado contra el suelo. Las filas de fuera no se borran —el trazo tiene que seguir *saliendo* por el borde, no cortarse contra él—: el recorte es visual, con `clip-path`.

  Los controles son **`<button>` de verdad**, y la ventana del contexto se desplaza con las flechas: ni ECharts, ni uPlot, ni Highcharts tienen un zoom que se pueda usar con el teclado.

  **No te puedes quedar atrapado.** Hay un **suelo** para la ventana —dos separaciones medias, que calcula el servidor porque es el único que sabe cuántas filas hay—, así que no se puede ampliar hasta que no quede nada. Y como el suelo *no* garantiza que haya datos (en una serie con un hueco puedes ampliar dentro del hueco, y ahí no hay nada), **el estado vacío conserva los controles**: dice «No hay datos en este tramo» y mantiene el botón de restablecer. Un gráfico puede quedarse sin datos que enseñar; lo que no puede es quedarse sin salida.

  ⚠️ **Sin rueda ni pinch, y va escrito en la doc.** Una rueda dispara ~50 eventos/s y cada uno cambia los ticks: hacerlo bien exige portar `Ticks`, `Scales`, `Path` y `Format` a JS y mantener dos implementaciones de la geometría idénticas para siempre. Es la deuda que esta arquitectura se eligió para no contraer.

- **Datos en vivo** con `<x-kore::chart.stream>`. Ver [docs/chart/streaming.md](docs/chart/streaming.md).

  **El morph de Livewire ya ERA la actualización**: cambia el atributo `d` del `<path>` sin recrear el nodo. No hay instancia de JavaScript que proteger, así que no hay nada que parpadee — que es el issue **#20103 de Filament**, abierto porque cada refresco destruye y recrea una instancia de Chart.js. Lo que había que construir no era refrescar: era **saber cuándo no hacerlo**.

  Un `wire:poll` a secas refresca siempre. El gráfico se para solo en los tres momentos en que eso es hostil: **mientras lees un tooltip** (el número que estás mirando no puede cambiar bajo el cursor), **con la pestaña oculta** (diez pestañas serían diez renders por segundo en tu servidor, para nadie) y **con el zoom puesto** (has ido a mirar algo concreto; no se te mueve el suelo).

  La ventana deslizante es un `array_slice`: ni ring buffer, ni motor. Y todo el JavaScript son ~25 líneas.

  **Si el gráfico tiene trazo, no se anima nada.** Animar la línea exigiría `transition: d`, y está medido en los tres motores con Playwright: Firefox interpola, **WebKit ni siquiera lo soporta** y Chromium dice soportarlo pero da un salto seco. Y hay una razón mejor: en una ventana deslizante, interpolar `d` lleva el punto *i* hasta el valor del punto *i+1* — la onda **tiembla en el sitio** en vez de desplazarse.

  Así que el trazo **salta**. Y todo lo que se mueva despacio mientras el trazo salta **se despega de él**: medido, con los puntos animados el peor se iba a **8,36 % del área** de la curva sobre la que se supone que está — unos 24 px. O se anima todo, o no se anima nada; y como el trazo no puede, no se anima nada.

  Las transiciones sólo se encienden cuando **no hay línea ni área** (un gráfico de barras en vivo: ahí no hay trazo del que despegarse), y sólo sobre lo **vertical** — lo horizontal salta, porque las etiquetas del eje X también saltan. Las barras llevan `wire:key` cuando hay stream: sin ella, el morph reutiliza la barra *i* para el dato *i+1* y la barra crece en el sitio en vez de que la de al lado se desplace.

  ⚠️ **El techo va escrito, medido y con excepción.** Un refresco es un round-trip completo de Livewire, y **medido: 44 kB de HTML — 5,1 kB gzip — por refresco** con 40 puntos y 2 series. Son N renders para N clientes. `every` **lanza** por debajo de 500 ms. El techo honesto es **1 Hz con ≤ 200 puntos**; a 10 Hz no aguanta ninguna arquitectura que dibuje en el servidor, ni ésta ni ninguna.

- **`max-gap` en `<x-kore::chart.line>` y `<x-kore::chart.area>`**: el trazo **se parte** cuando entre dos puntos consecutivos pasa más de lo que debería.

  Un `null` explícito ya partía la línea desde el primer día. Lo que **no** partía nada era una fila que sencillamente **no está**: ahí la línea cruzaba el hueco dibujando una curva suave por encima de un rato en el que no hubo dato — y con `curve="monotone"`, un swoop que *parece* dato. **Es la misma mentira que arregló el eje temporal, un piso más arriba**: entonces el hueco desaparecía porque los puntos se colocaban por su ordinal; ahora el hueco se ve, pero el trazo lo tapa.

  Acepta `«30s»`, `«5m»`, `«2h»`, `«7d»`… o un número. En un eje de categorías **lanza**: mide una distancia entre dos puntos, y entre dos categorías no hay ninguna.

- **`min` y `max` en `<x-kore::chart.axis-y>`**, para fijar el dominio. `Domain::fromSeries()` los aceptaba desde el primer día —y los trata como un contrato, no los redondea por debajo de lo que pides— pero **nadie se los pasaba nunca**. En un gráfico en vivo dejan de ser un lujo: un eje que se reescala cada dos segundos porque el dato subió un punto es ilegible.

- **Cascada (`<x-kore::chart.waterfall>`)** — el puente entre un valor inicial y uno final. Ver [docs/chart/waterfall.md](docs/chart/waterfall.md).

  **Es un apilado de una sola serie con la base moviéndose por fila**: la barra flotante ya la calculaba `layoutBars()` para las pilas, así que no hay geometría nueva. Cada etapa es un salto que sube (verde) o baja (rojo); una fila marcada como `total` es un descansillo que va del cero al acumulado, en neutro. **Los totales se calculan solos si los dejas vacíos** — no hay que repetir la suma en el dato. El color codifica **polaridad, no identidad**: es el único sitio donde una serie usa los tokens semánticos, y es legítimo (`--kore-success` significa «esto suma»).

- **Gauge (`<x-kore::chart.gauge>`)** — un número, contra un objetivo, y con rangos de color. Ver [docs/chart/gauge.md](docs/chart/gauge.md).

  **Reutiliza el donut**: vive en su mismo SVG cuadrado con escalado uniforme, y la trigonometría del arco ya estaba en `Arc` (un método nuevo, `Arc::open()`, para trazar un arco abierto en vez de rellenar un anillo). El número y el arco se pintan con el color de la banda en la que cae el valor —verde, ámbar, rojo—, y unos pellizcos marcan dónde empieza cada una. El dominio no tiene por qué ser 0–100, el arco no tiene por qué ser de 270° (`sweep="180"` = semicírculo), y el número no pierde los decimales (un SLA de 99,2 no sale «99»).

  ⚠️ **Sin rangos de color, un gauge es un stat tile con un anillo decorativo**, y la documentación te lo dice: lo que justifica el arco es el contexto de color, no el arco.

- **Embudo (`<x-kore::chart.funnel>`)** — cuánta gente sobrevive cada paso de un proceso. Ver [docs/chart/funnel.md](docs/chart/funnel.md).

  Cada etapa es un trapecio (un `<div>` recortado con `clip-path`) que se estrecha hasta la siguiente: el estrechamiento **es** la caída, y al lado van la conversión (cuánto queda del primero) y la caída de ese paso (cuánto se pierde ahí, en rojo). El color sale de la rampa **ordinal** —`--kore-ord-*`, la que se hizo en la Fase 1—, no de la categórica: las etapas van en secuencia, y ahí el color sólo dice «vas por aquí»; el peso de la información lo lleva el ancho del trapecio. El orden de las filas es el orden del embudo, y no se reordena. Al pasar el ratón sobre una etapa se enciende el par (trapecio + fila) y se apaga el resto — CSS puro, como el donut, y se apaga con `:highlight="false"`.

- **Mapa de calor (`<x-kore::chart.heatmap>`)** — una matriz de columna × fila donde el color es el valor. Ver [docs/chart/heatmap.md](docs/chart/heatmap.md).

  Tres canales (la columna es el `x`, la fila es `row`, el valor es `y`), en formato «largo» —el de un `GROUP BY`—. **El color se cuantiza, no se interpola**: el valor cae en uno de N escalones (`:buckets`, 3–7), la celda lleva un `data-bucket` y el color lo pone el CSS con la rampa secuencial de la Fase 1 — así el tema sigue cambiando sin JavaScript, y una escala de escalones se lee mejor que un degradado. Un cruce sin dato no es un cero: se queda sin color.

  **El hover va por delegación** — es lo único de los cuatro tipos de negocio que toca el JavaScript. Un heatmap de 365×7 son 2.555 celdas; un listener por celda cuesta 30 ms por frame, así que hay **un solo `pointermove`** en la rejilla que lee el `data-*` de la celda bajo el ratón. El resalte, en cambio, es `:hover` de CSS puro: una pseudoclase sobre miles de nodos es barata, miles de listeners no.

- **Barras horizontales (`orientation="horizontal"`)** — el mismo gráfico de barras, transpuesto. Ver [docs/chart/bar.md](docs/chart/bar.md#barras-horizontales).

  Es una decisión de **presentación, no de dato**: `x` sigue siendo la categoría e `y` el valor; sólo cambia a qué coordenada del `<div>` va cada número. Por eso no hay geometría nueva — es `layoutBars()` con los ejes intercambiados —, y funciona igual con barras sueltas, agrupadas, apiladas y con negativos (crecen a la izquierda del cero). Su razón de ser son las **etiquetas de categoría largas**: caben a la izquierda sin rotar, que es donde las verticales fallan. El eje del valor pide **menos ticks** que en vertical —apilados no se pisan, tumbados sí—, así que un eje `0…1.500` sale con cuatro marcas. **Cero JavaScript**: el resalte es `:hover`, y sólo transpone barras (una línea, un donut o un gauge se rechazan).

- **Área apilada (`stack` en `<x-kore::chart.area>`)** — varias áreas con el mismo `stack` se apilan. Ver [docs/chart/area.md](docs/chart/area.md#área-apilada).

  Es el mismo `stack` de las barras, extendido al área: cada banda deja de apoyarse en el cero y pasa a apoyarse en la suma de las de debajo, así que se lee a la vez el **total** (la silueta de arriba) y la **composición** (cada franja). Por dentro, la línea base de la banda deja de ser plana y pasa a ser la curva acumulada (`Path::areaBetween`, el borde de arriba hacia delante y el de abajo del revés, como d3). Funciona con `curve="monotone"` —la banda de arriba se apoya exactamente en la de abajo sin inventar un máximo— y un hueco en cualquiera de los dos bordes parte la banda. **Sin `stack`, las áreas se superponen** translúcidas desde el cero, que es la lectura de comparar formas: dos preguntas distintas, ninguna es la correcta. Cero JavaScript.

- **Guardia de peso del bundle en CI** (`npm run size`). «El JavaScript es poco» es una promesa de la documentación, y una promesa que nadie mide deja de ser verdad sin que nadie se entere.

#### El principio que ordena todo el módulo

**El color nunca es un valor: es un token.** Las series se pintan con `var(--kore-chart-1)`, así que **al cambiar de tema el gráfico se repinta solo, sin ejecutar una sola línea de JavaScript**. Verificado en Chrome, Firefox y WebKit.

Eso es lo contrario de lo que hace un motor de `<canvas>`, donde el color es un valor de JS y hay que volver a ejecutar código en cada cambio de tema — con las consecuencias que lleva años pagando el resto del ecosistema Laravel: colores que se intercambian solos entre gráficos, y gráficos que necesitan una recarga para verse bien en modo oscuro.

Y como el `<svg>` lo emite el servidor, **el morph de Livewire deja de ser una amenaza y pasa a ser el mecanismo de actualización**: cambias el dato en PHP y Livewire actualiza el atributo `d` del `<path>` **sin recrear el nodo**. Sin `wire:ignore`, sin `chart.update()`, sin una instancia de JavaScript que proteger.

#### Detalles que quizá quieras conocer

- **No existe un `type="mixed"`.** Un gráfico de barras con una línea encima son dos marcas, una detrás de otra. El orden en que las escribes es el orden en que se pintan.
- **Los ejes dicen números redondos.** El algoritmo de ticks es un puerto del de d3 (ISC, con atribución en [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md)) y da exactamente los mismos valores. El reparto ingenuo que usa el resto del ecosistema PHP produce ejes que dicen "1.224".
- **Un `null` no es un cero.** La línea se corta en el hueco, en vez de dibujar una caída al suelo que nunca ocurrió. Y la curva monótona reinicia sus tangentes ahí, para no inventarse una curva por encima del vacío.
- **Los datos van también en una tabla, escondida para el lector vidente.** Un `<svg>` es tan mudo para un lector de pantalla como un `<canvas>`; como los datos ya están en PHP, la tabla sale gratis. Nadie más en el ecosistema la sirve.
- **Sin leyenda ni tooltip, el gráfico no carga JavaScript.** Y el resize no lo necesita nunca: la geometría está en porcentajes, no en píxeles.
- **La paleta no se cicla.** La novena serie no existe: repetir el color de la primera es peor que no pintarla. Si lo intentas, salta una excepción.

### Fixed

- **El tooltip enseñaba los datos del render ANTERIOR después de un morph.** El morph de Livewire va en las dos direcciones y solo se atendía una: lo que escribe el cliente, el morph lo borra (y eso ya se reaplicaba); lo que escribe el servidor, Alpine no lo relee. El payload solo se leía en `init()`, y un morph **no reinicializa el `x-data`**. Así que cualquier `wire:model` que cambiara el dataset repintaba el `<path>` con los valores nuevos y dejaba el tooltip enseñando los viejos. Sin ningún error.

- **La tabla accesible arrastraba scroll horizontal a toda la página.** `sr-only` esconde la caja con `width: 1px`, pero sobre una `<table>` **ese ancho se ignora**: el algoritmo de layout de tablas lo acota por abajo al `min-content`. Medido en un móvil de 375 px: la tabla ocupaba **321 px de ancho**. El `clip-path` sí aplicaba —así que no se veía y nadie se enteró— pero la caja seguía ocupando. Ahora el `sr-only` va en un `<div>`, que sí obedece.

- **Las etiquetas del eje X se salían de la tarjeta.** Se anclaban por el borde la primera y la última, y eso funcionaba porque las únicas que se salían eran las de los extremos… en una escala de bandas. En una escala continua un tick puede caer en el **98,9 %** —ni centrado ni en el borde—, ahí ningún umbral salta, y media etiqueta se va fuera. Ahora el servidor emite **lo que mide** la etiqueta, en `ch` (no puede medir texto, pero sí contarlo), y el CSS la acota con un `clamp`: la centra sobre su tick si cabe y la apoya en el borde si no. Es exacto por construcción, no tiene umbrales que afinar, y de paso resuelve los dos casos que el anclaje trataba a mano.

- **Las etiquetas del eje X se pisaban unas a otras en un móvil.** Con categorías largas («Coste de ventas», «/api/pedidos») o un eje de tiempo denso, las etiquetas se solapaban. El `width` en `ch` sólo evitaba que se salieran de la tarjeta — el solape con la vecina es un choque distinto, y no lo tapaba nada. Ahora el servidor emite el **hueco** que cada etiqueta tiene hasta su vecina (lo sabe: conoce dónde cae cada tick), y el CSS la acota a lo menor de su ancho o ese hueco: una etiqueta larga se recorta con puntos suspensivos en vez de meterse encima de la de al lado. Se quitó de paso el adelgazado por *container-query*, que peleaba con esto y dejaba las etiquetas recortadísimas y con huecos.

- **El tirador del zoom era imposible de agarrar en un móvil con el zoom muy cerrado.** Al 5 % la ventana del contexto medía ~16 px, y con dos asas encima no había dónde pinchar. Ahora tiene un mínimo de 24 px — ancho de dedo, no de píxel.

- **Las barras de una cascada tenían las esquinas de abajo en pico.** Una barra normal se redondea sólo por la punta, pero una de cascada FLOTA —no crece desde el suelo—, así que se le redondean las cuatro esquinas por igual.

- **`max-labels` no funcionaba. Nunca.** Una prop con guion declarada en `@props` **no se puede leer con `$attributes->get()`**: Blade ya la ha extraído del bag. Así que `$attributes->get('max-labels')` devolvía `null` siempre, y el tope de etiquetas del eje X no se aplicaba jamás — salían todas. Salió a la luz al añadir `max-gap`, que fallaba igual.

- **Dos gráficos con stream en el mismo componente Livewire eran dos temporizadores.** Un refresco no actualiza un gráfico: actualiza el **componente entero**. Así que había el doble de round-trips contra el servidor, el dato avanzaba al doble de velocidad, y —lo peor— **poner el ratón sobre uno no paraba el otro**: el temporizador del de al lado seguía moviéndole los números bajo el cursor. Ahora conduce uno solo, y el ratón sobre cualquiera los para a todos.

- **La etiqueta de una fila no bajaba de los minutos.** En un gráfico en vivo muestreado cada dos segundos, los treinta puntos del mismo minuto se llamaban todos «21:35»: el tooltip dejaba de decir de cuál hablaba, y la tabla accesible tenía treinta filas con el mismo nombre.

- **Un color mal escrito dejaba la serie INVISIBLE, sin decir nada.** `color` se colaba tal cual al CSS, así que `color="chart-4"` —que es lo primero que uno prueba— acababa en `--kore-series: chart-4`, que no es un color válido: la serie no se pintaba. Medido en la demo, un gráfico de barras entero, invisible. Y lo mismo con cualquier errata (`destructiv`, `rojo`, `blue-500`).

  Ahora `chart-1`…`chart-8`, `seq-1`…`seq-7` y `ord-1`…`ord-7` son tokens válidos, un color CSS explícito (`#e11d48`, `oklch(…)`, `var(…)`) pasa, y **cualquier otra cosa lanza**. Un color que no existe no puede dejar el gráfico en blanco: tiene que decirlo.

- **En una barra apilada se redondeaba cada tramo, no la columna.** La pila se veía como una torre de piezas sueltas en vez de como una barra partida en tramos. Ahora sólo lleva el redondeo la **punta** — y la punta es el último tramo **con valor**, no el último que declaraste: si a un mes le falta la serie de arriba, la punta pasa a ser la de debajo. Un `0` no cuenta (dibuja un tramo de altura sub-píxel, y redondearlo dejaría cuadrado el que sí se ve), y una barra negativa la redondea abajo, que es hacia donde crece.

- **La canaleta del eje Y medía casi el doble de lo que ocupaban sus etiquetas**, y ese hueco vacío empujaba el gráfico entero a la derecha. En un móvil, 31 px de 277 — un 11 % del ancho, robado al dato. Eran dos errores que se multiplicaban:

  - **`ch` se resolvía con la fuente equivocada.** La canaleta heredaba los 16 px del contenedor (`1ch = 10,08 px`) mientras las etiquetas se pintan a 12 px (`1ch = 7,56 px`): un 33 % de más. Ahora fija su propia fuente, la misma con la que pinta.
  - **Se contaba cada carácter como un `ch`.** Medido a 12 px: una cifra mide 1,00 ch (exacto, por `tabular-nums`), pero el punto mide 0,47 y el espacio 0,45. «7.000 €» pedía 7,5 ch cuando ocupa 5,9. Ahora cada carácter pesa lo suyo. Ojo al `%`, que mide **1,47 ch** —más que una cifra—: contarlo como 1 dejaría la etiqueta fuera de la tarjeta.

  Medido: el área de trazado pasa de 193 a 221 px en un móvil, con 3 px de holgura y ninguna etiqueta fuera.

- **En un móvil se leía «EneFeb».** Las etiquetas del eje X se anclaban por su borde —la primera y la última— para no salirse de la caja. Pero **dónde hay que anclar depende del dato, no del orden**, y aplicarlo a ciegas rompe uno de los dos casos:

  - **Con barras**, el tick cae en el CENTRO de la banda y tiene sitio de sobra a los lados. Anclar la primera por su borde izquierdo la empujaba media anchura a la derecha, justo encima de la siguiente: «EneFeb».
  - **Sin barras** (una línea, un área), el primer punto cae en `x=0`, pegado al eje Y. Centrar la etiqueta ahí le mete media anchura debajo de la canaleta del eje Y, y el eje X parece correrse.

  Así que ahora lo decide el servidor, que es quien sabe dónde cae cada tick — y lo decide por la **posición**, no por el orden: con el adelgazado de etiquetas, el último tick pintado no siempre cae en el 100. Medido a 375 px en los dos casos: cero solapes, y las etiquetas de una escala de punto se apoyan exactamente en el borde del área.

- **El donut se salía de la tarjeta en pantallas estrechas.** El arco (16rem) y su leyenda no caben uno al lado del otro en 277 px. Por debajo de 26rem se apilan. Es una *container query*, no una *media query*: el gráfico responde al ancho de su contenedor, así que uno metido en un sidebar estrecho se apila también aunque la ventana sea de escritorio.

- **Once componentes escribían parte de su estado en el `x-data` de un ancestro, no en sí mismos.** Alpine no llama a los métodos de un componente con `this` = el objeto del componente: los llama con un Proxy que fusiona toda la pila de scopes, y su trampa `set` acaba así:

  ```js
  if (!target) target = objects[objects.length - 1];   // el x-data MÁS EXTERNO
  ```

  O sea: `this.foo = x` sobre una propiedad **no declarada** en la factoría no se guarda en el componente — se guarda en el `x-data` ancestro más externo de la página, que comparten todos sus hermanos. Las consecuencias eran silenciosas: cinco gráficos en una página enseñaban en el tooltip los datos del quinto; dos tablas compartían el `ResizeObserver`; y `_floatingCleanup` —que usan seis componentes distintos— se pisaba entre ellos, de modo que cerrar un dropdown ejecutaba la limpieza de un select. Sin un solo error en consola.

  Afecta a `chart`, `datatable`, `select`, `dropdown`, `tooltip`, `overlay`, `upload`, `datepicker`, `color-picker` y `time-picker`. Todas las propiedades están ahora declaradas, y `tests/js/alpine-scope.test.js` recorre el AST de cada componente para que no vuelva a colarse ninguna.

- **El tooltip del gráfico se anclaba al borde superior del área de dibujo**, así que salía siempre flotando por encima del gráfico, tapando el título. Ahora la X se engancha al dato —para que no tiemble mientras el ratón se mueve dentro de la misma banda— y la Y sigue al cursor.

- **Las series perdían sus decimales.** El eje deduce los suyos del paso entre ticks; una serie no tiene paso, así que se escribía con cero decimales: un sensor que marcaba 21,4 °C aparecía en el tooltip y en la tabla accesible como «21». Ahora cada serie deduce los decimales de sus propios valores, los mismos para toda la serie.

- **`theme.js` no avisaba al cambiar el tema del sistema operativo.** `setMode()` sí despachaba `theme-changed`, pero el listener de `matchMedia` no. Con `mode: 'system'` —que es el valor por defecto— cambiar el tema del SO repintaba el CSS sin avisar a nadie, así que cualquier consumidor que necesitara releer colores desde JavaScript se quedaba con el tema anterior. Y solo en la configuración por defecto, que es la peor forma de encontrar un bug.

### Changed

- **Todo se apaga con la misma prop: `:show`.** Los ejes y la rejilla salen por defecto (un gráfico sin ejes no se lee); el tooltip y la leyenda hay que pedirlos. Y en una serie, `:show="false"` **no es lo mismo que envolver la marca en un `@if`**: el color se asigna por orden de registro, así que quitar la marca del árbol de Blade hace que la siguiente herede su color y **todas las series de detrás se recoloquen** — el lector, que ya sabía que «Ingresos» era la naranja, se encuentra otra cosa naranja. Con `:show="false"` la marca se registra, **se queda con su color** y no se dibuja. Lo que sí desaparece es su trazo, su entrada en la leyenda, su copia en el payload, su columna en la tabla accesible y su aportación al dominio, **así que el eje se reajusta solo** — gratis, porque la geometría se calcula en el servidor.

  En el tooltip, `:show="false"` tampoco lo esconde con CSS: **no emite el payload**, que a 2.000 puntos pesa más que el propio trazo.

  El `hide` de los ejes desaparece en favor de `:show` (invertido y a contracorriente del resto). `1.6.0` no llegó a publicarse, así que no rompe a nadie.

- **Dos interruptores que no encendían nada.** `<x-kore::chart.tooltip :crosshair="false" />` registraba la prop y **no la leía nadie**: el crosshair se pintaba siempre. Y `ChartFrame::$grid` llevaba desde el principio en `true` sin que nada le escribiera jamás: la rejilla no se podía quitar. Ahora `crosshair` se honra y la rejilla se apaga con `<x-kore::chart :grid="false">`.

- **Un donut ya no descarta marcas en silencio: lanza.** Una línea, un eje o un tooltip dentro de un donut no se pintaban, no avisaban, y encima el gráfico montaba un componente de Alpine que no hacía nada. Escribías una marca y el gráfico decidía por su cuenta que no valía. Es la misma regla que al mezclar escalas: no se adivina. (Y el donut ya no monta Alpine: su única interacción es CSS puro.)

- **En el donut, al posarte sobre un arco se enciende su fila de la leyenda, y al revés.** Sin esa relación hay que emparejar el color a ojo entre el arco y la leyenda, que es lo que peor funciona —y peor todavía con daltonismo—. Está hecho con `:has()` en CSS puro: el arco y su fila comparten un `data-slice`, así que **no hace falta ni una línea de JavaScript** ni una segunda copia de los datos en el DOM. El donut sigue sin tooltip a propósito: su leyenda ya imprime etiqueta, valor y porcentaje de cada porción.

- **`floating.js` acepta una referencia virtual.** El tooltip de un gráfico no cuelga de un elemento: cuelga de un punto de datos. Y como un ratón moviéndose no dispara scroll, resize ni mutación, el `cleanup` que devuelve `startFloating()` expone ahora un `update()` para pedir el reposicionamiento a mano. Los cuatro componentes que ya lo usaban no cambian.

> **Limitaciones que conviene conocer.** Los puntos de la línea son opt-in, porque son un `<div>` por dato; el techo práctico son unos 2.000 puntos por serie, y no lo pone el dibujo sino el peso del HTML. El streaming con `wire:poll` tiene un techo honesto de ~1 Hz por el mismo motivo. Ocultar una serie **desde la leyenda** no reescala los ejes (sí lo hace `:show="false"` en el render). El zoom es de servidor, con botones y brush: **no hay rueda ni pellizco** a propósito —hacerlos bien exigiría portar la geometría a JavaScript—. Y quedan fuera, por decisión: dispersión/burbujas, treemap, velas, radar, sankey y boxplot.

---

## [1.5.0] — 2026-07-13

**App Shell.** El chasis de la aplicación —sidebar, barra superior y el layout que los coordina— pasa a ser parte de la librería. Con esto se monta un panel de administración completo sin escribir layout a mano.

### Added

- **`<x-kore::shell>`** — el layout. Los sidebars se le anuncian solos al renderizarse, así que reserva el espacio del contenido sin recibir props duplicadas ni inspeccionar el HTML.
- **`<x-kore::sidebar>`** — navegación lateral colapsable, con drawer automático en móvil, modo rail y soporte para dos sidebars a la vez (uno a cada lado).
- **`<x-kore::sidebar.item>`** — enlaces, desplegables a cualquier profundidad, badges y detección de ruta activa.
- **`<x-kore::sidebar.group>`** — secciones con título, opcionalmente plegables.
- **`<x-kore::sidebar.toggle>`** — el botón de menú. Habla con el store de Alpine, así que funciona desde cualquier punto de la página; en escritorio colapsa y en móvil abre el drawer.
- **`<x-kore::navbar>`** — barra superior con tres zonas y el botón de menú incorporado.

Documentación en [`docs/shell/`](docs/shell/getting-started.md).

#### El principio que ordena todo el módulo

**El estado lo resuelve el servidor y lo estampa en el HTML. Alpine solo cambia atributos.**

- **El sidebar recuerda si está colapsado en una cookie** (`kore_sidebar`), no en `localStorage`. La cookie viaja al servidor, así que Laravel emite el ancho correcto **en el primer paint**: cero parpadeo, cero JavaScript en el camino crítico, y el sidebar sale bien aunque el JS no cargue nunca. Con `localStorage` el estado solo se sabría tras arrancar Alpine, y el sidebar aparecería ancho y se encogería a la vista del usuario en cada carga.
- **La ruta activa y los sub-menús abiertos se calculan en PHP.** Si estás en una hoja del árbol, toda su rama sale ya desplegada en el HTML — a cualquier profundidad. Nada de menús que se abren de golpe cuando arranca el JavaScript.
- **El layout móvil es CSS puro.** Media queries, no `matchMedia`: correcto en el primer paint y en cada resize.

#### Detalles que quizá quieras conocer

- Con el sidebar en iconos, los sub-menús salen en **flyouts apilados**: un menú anidado se abre *al lado* de su padre, no en su lugar.
- Los **badges numéricos se mudan a la esquina del icono** al colapsar, con tope configurable (`badge_max`, por defecto `99` → `"99+"`). El valor acortado es solo el visual: un lector de pantalla sigue anunciando el número real.
- **Accesibilidad**: navegación completa por teclado (`↑↓`, `Home`/`End`, `←→` para los sub-menús), focus trap **solo** en el drawer móvil (en escritorio el sidebar es parte de la página, y atrapar el foco ahí sería un fallo, no una mejora), y los enlaces conservan su nombre cuando la etiqueta desaparece al colapsar.
- **`shell.badge_max`, `shell.sidebar.*` y `shell.navbar.*`** en `config/kore-ui.php`. Los anchos son longitudes CSS, no clases de Tailwind: alimentan las custom properties que gobiernan el layout.

> **Una limitación de CSS, no de KoreUi:** el sidebar es `position: fixed` y deja de anclarse a la ventana si un **ancestro** del shell tiene `transform`, `filter` o `contain`. Si aparece en un sitio raro, busca eso.

### Fixed

- **El selector de tema estaba muerto en las variantes `toggle` y `segmented`.** No declaraban `x-data`, y Alpine solo evalúa directivas dentro de un árbol que lo tenga: sus `x-on:click` nunca se registraban y pulsarlos no hacía nada, **sin ningún error**. Funcionaba por accidente en las apps cuyo layout tuviera un `x-data` colgado del `<html>`, y estaba roto en las demás.
- **MCP** — los tokens del shell (`--kore-sidebar-width: 16rem`) se publicaban como si fueran **colores**, con una clase `bg-kore-sidebar-width` inexistente. Ahora tienen su propia categoría `layout`. Además, el filtro por categoría solo conocía 4 de las 9 que existen.

### Changed

- **`<x-kore::toolbar>` acepta `:role="false"`** para no emitir `role="toolbar"`. Ese rol le promete a un lector de pantalla un widget que se recorre con las flechas; una cabecera de página no lo es. Retrocompatible: por defecto se sigue emitiendo.
- **El bloqueo de scroll sale de `overlay.js`** a `utils/scroll-lock.js`, con conteo de dueños. Es un recurso global: sin ese conteo, cerrar un modal devolvería el scroll a la página con el drawer del sidebar todavía abierto.

---

## [1.4.1] — 2026-07-13

### Fixed

- **DataTable — el overlay de carga se quedaba pegado sobre la tabla.** Regresión introducida en 1.4.0: los datos se veían, pero el spinner no desaparecía nunca.

  La causa está en cómo Livewire oculta estos overlays en el primer render. No usa un selector genérico, sino un `<style>` que enumera los selectores de atributo **uno a uno**:

  ```css
  [wire\:loading], [wire\:loading\.delay], [wire\:loading\.flex], [wire\:loading\.block], … { display: none }
  [wire\:loading\.delay\.short], [wire\:loading\.delay\.long], … { display: none }
  ```

  Existe `[wire:loading.delay]` y existe `[wire:loading.flex]`, pero **no existe ningún selector para la combinación *delay + display***. Como el selector es el nombre literal del atributo, un `wire:loading.delay.flex` no encaja con ninguno y nunca recibe su `display:none` inicial: nace visible. Y el JavaScript solo lo apaga al **terminar** una petición, así que si en la primera carga no hay ninguna —o dura menos que el delay— no queda nadie que lo apague.

  El arreglo es declarar el estado inicial a mano, con `style="display: none"` en el propio overlay. El modificador `.flex` sigue siendo necesario para que el spinner quede centrado (sin él Livewire escribe `display:inline-block` en el style inline y pisa la clase `flex`). **Las dos piezas van juntas o no van.**

  Si tienes un template del DataTable publicado, aplícale el mismo cambio:

  ```diff
  - <div wire:loading.delay.flex class="absolute inset-0 …">
  + <div wire:loading.delay.flex style="display: none" class="absolute inset-0 …">
  ```

---

## [1.4.0] — 2026-07-13

Release de correcciones. Dos bugs visibles y una pieza de configuración que llevaba tiempo mintiendo.

### Fixed

- **DataTable — overlay de carga desalineado.** El overlay usaba `wire:loading.delay` sin modificador de display, así que Livewire le aplicaba `display:inline-block` en línea, pisando la clase `flex` y dejando el spinner arriba a la izquierda en vez de centrado. Ahora usa `wire:loading.delay.flex`.

  > ⚠️ **Este cambio salió incompleto y provocó una regresión: el overlay se quedaba pegado sobre la tabla. Corregido en 1.4.1.** Al restaurar el modificador `.flex` se perdió el `display:none` inicial, porque Livewire no tiene selector CSS para la combinación *delay + display*. Ver la entrada de 1.4.1 para la explicación completa.
- **DataTable — overlay que no tapaba la tabla al hacer scroll.** El overlay vivía dentro del contenedor con `overflow-x-auto`, donde un `absolute inset-0` se desplaza junto al contenido: en cuanto había scroll horizontal, las columnas de la derecha quedaban al descubierto durante la carga. Ahora se ancla a un padre que no hace scroll.
- **Toast — el hover borraba la descripción.** `@mouseleave` colapsaba el toast siempre, ignorando `autoExpand`. Bastaba con rozar un toast con el cursor para que perdiera su descripción de forma permanente. El hover ahora solo puede **añadir**: nunca colapsa por debajo del estado de reposo.
- **Toast — un `loading` resuelto no se volvía a expandir.** `resolve()` activa `autoExpand` al llegar una descripción, pero el estado se copiaba una sola vez al montar el componente Alpine, así que el cambio nunca llegaba a la vista.

### Added

- **`feedback.toast.expand_delay` (150 ms) y `collapse_delay` (300 ms)** — estaban en la config y se pasaban al front, pero no los consumía nadie. Ahora se aplican de verdad, con cancelación del temporizador pendiente: pasar el cursor de largo sobre un toast ya no lo abre y lo cierra de golpe.
- **Tests JS del sistema de feedback** — `tests/js/feedback.test.js` cubre `isExpanded()` y `setHovered()` con temporizadores simulados.

### Deprecated

- **`expandable` en el payload del toast.** Duplica lo que ya dicen `description`, `actions` y `options`, y la librería no la lee: el front deriva el estado con `isExpanded()`. Sigue publicándose para no romper templates ya publicados que la consulten, y **se elimina en 2.0**. Si tienes un template de toast publicado, deja de depender de ella.

### Chore

- **Grafo de conocimiento (graphify)** — se versiona `graphify-out/` (informe y grafo) y se añade `.graphifyignore` para que `vendor/`, `node_modules/` y `dist/` no inflen el corpus.

---

## [1.3.0] — 2026-06-04

### Security

Endurecimiento de los 6 hallazgos P0 de la auditoría exhaustiva (cliente→servidor). Cambios compatibles: el uso normal de la API no se ve afectado.

- **Confirm callbacks** — `kore:confirm-callback` solo ejecuta métodos previamente autorizados server-side por `confirm()->onConfirm()/onCancel()->send()` (lista `#[Locked]` consumida en un solo uso). Bloquea eventos forjados desde el navegador que invocaban métodos arbitrarios o `protected`/`private` (p.ej. `runBulkAction`) saltándose el diálogo de confirmación.
- **DataTable export** — `exportAs()` valida `isExportEnabled()` (403) y el formato contra `getExportFormats()` (404); ocultar el botón en la UI ya no era una autorización.
- **Spotlight providers** — `$providers` es `#[Locked]` y solo se instancian clases que extienden `SpotlightProvider`: cierra la instanciación arbitraria de clases vía `app($class)`.
- **Spotlight SSRF** — `searchDependency()` valida el esquema (http/https) y bloquea hosts privados/loopback/link-local (169.254.x, RFC1918) antes de cualquier petición HTTP.
- **Spotlight navegación** — las URLs de acción `url` (de items y de resultados remotos) se sanean server-side; se neutralizan `javascript:`/`data:`/`//host`.
- **ColorColumn** — el valor de celda se escapa con `@js()` en el handler de copiado (XSS almacenado) y se valida como color CSS antes de inyectarlo en `style` (inyección CSS).
- **UrlSanitizer** — rechaza URLs scheme-relative (`//host`, open-redirect) y elimina caracteres de control que disfrazan esquemas peligrosos (`java\tscript:`).
- **DataTable** — `tableName` ahora es `#[Locked]`.

### Added

- **Accesibilidad de formularios** — todos los controles (`input`, `textarea`, `password`, `number`, `maskable`, `checkbox`, `radio`, `toggle`, `radio-group`) exponen `aria-invalid` y asocian su error/hint/descripción al control vía `aria-describedby` con ids deterministas (WCAG 3.3.1 / 4.1.2).
- **Token `--kore-warning-text`** — tono accesible (AA) para `warning` usado como texto en variantes soft/outline de `badge`, `chip` y `alert`.
- **Red de tests JS (Vitest)** — `tests/js/` con scripts `test:js` / `test:js:watch` y primer test de la lógica de `number.js`.
- **CI (GitHub Actions)** — Pest en matriz PHP 8.2–8.4, Vitest y build de assets en cada push/PR.

### Fixed

- **Dark mode** — `ring-offset-kore-bg` en los componentes con foco visible (elimina el halo blanco del anillo de foco en modo oscuro).
- **Rendimiento** — `loading="lazy"` + `decoding="async"` en `ImageColumn` y avatares.
- **i18n** — registrada la clave `datatable.translations.edit_unauthorized` (estaba leída pero ausente del config).
- **Docs** — prefijo de componente corregido a `x-kore::` (dos puntos) en documentación y CHANGELOG.

---

## [1.2.0] — 2026-06-03

Auditoría completa del DataTable en tres sprints (seguridad, escalabilidad y accesibilidad), más selección persistente entre páginas. Es el release que convierte el DataTable en un componente apto para producción con datasets grandes.

### Security

- **Ordenación arbitraria** — `sorts` se valida contra la whitelist de columnas ordenables y la dirección se normaliza en `applySorts()`. Antes se podía ordenar por columnas no expuestas, y una dirección inválida provocaba un 500.
- **IDOR en edición inline** — `updateCell` resuelve el registro vía `query()->where()` en lugar del modelo estático, respetando los scopes del componente. Si el registro no cae dentro del scope, se emite `edit-error` en vez de editarlo.
- **CSV injection** — `CsvExporter` neutraliza las celdas que empiezan por `=`, `+`, `-`, `@`, tab o retorno de carro.
- **Comodines LIKE** — la búsqueda escapa `%` y `_` con cláusula `ESCAPE` explícita (compatible entre motores).

### Added

- **Selección server-side persistente entre páginas.** La selección pasa a ser estado del componente (`$selected`, `$selectAllMatching`) en vez de vivir solo en Alpine, donde se reiniciaba al paginar o al hacer morph. Incluye banner con el total ("N seleccionados, incl. otras páginas"), botón *seleccionar todo lo que coincide* y `executeBulkActionMatching()` / `getAllMatchingIds()`, que operan sobre la query filtrada en el backend.
- **Selección por rango con shift-click.**
- **Header sticky vía `maxHeight()`.** El `sticky` del `thead` no ancla dentro de un contenedor con `overflow-x`, porque scrollea con la página. `maxHeight` convierte el wrapper en una región de scroll interno (`overflow-auto` + `max-height`), que es donde el sticky sí funciona.
- **`Column::maxWidth()`** — truncado con `title` en `th` y `td`.
- **Persistencia de `perPage` en la URL**, validada contra las opciones permitidas y respetada en `mount()`.
- **`docs/getting-started.md`** — guía de instalación que refleja el enfoque de `@koreScripts` (bundle precompilado, sin imports npm).
- **`docs/data/hardening.md`** — documenta las garantías de seguridad y la API nueva de la auditoría.

### Changed

- **Cap real de filas en export.** El `Exporter` recibe `maxRows` y `CsvExporter` lo respeta con un contador: `chunk()` ignora `->limit()`, así que hasta ahora se exportaba el dataset entero pese al límite.
- **`presetCounts` cacheado** (`#[Locked]`, invalidado tras bulk o edición inline). Antes se lanzaba un `COUNT` por preset **en cada render**.
- **Offsets de columnas fijadas** recalculados en cliente midiendo anchos reales, con un único hook `morphed` global y `resize` con throttle.
- **Responsividad por contenedor** — `ResizeObserver` sobre el root en lugar del viewport.
- **Debounce de 500 ms** en los filtros `number` y `number-range`.
- **Dropdown de export teleportado a `body`** (z-50), con la escala de z-index documentada.

### Fixed

- **Hook `morphed` de pinning** — envuelto en `try/catch` con validación de `el`. Una excepción ahí rompía el ciclo de morph de Livewire y dejaba `wire:loading` congelado, con el overlay de carga visible para siempre.
- **Botón anidado en columnas con interacción propia** — `ColorColumn->copyable()` envolvía la celda en el botón genérico de copiar mientras `color.blade.php` renderiza su propio `<button>`, produciendo un `<button>` dentro de otro. HTML inválido que rompía el DOM y el scope de Alpine. Las columnas `color`, `component` y `action` quedan excluidas del wrapper genérico.
- **Overlay de carga descentrado** — se sustituye el modificador `.flex` por la clase `flex` fija. *(Nota: este diagnóstico resultó ser incorrecto y se revirtió en 1.4.0; ver esa entrada.)*
- **Agregaciones** — el campo se valida con regex y se envuelve con `wrap()` en el `selectRaw`; `AVG` sobre un dataset vacío preserva `null` en vez de devolver `0`.
- **Ordenación con dot-notation** — se omiten esas columnas en lugar de generar SQL inválido.
- **`Ctrl+A` secuestrado** — el listener de teclado se acota al hover/foco del datatable, en vez de capturarlo en toda la página.
- **Guard de `per_page_options` vacío** en `updatedPerPage`.
- **Orden determinista en export** — `orderBy(pk)` como tiebreaker antes de `chunk()`.

### Accessibility

- `scope="col"`, `aria-sort` dinámico y `aria-label` en los botones de orden (tabla, modo collapse y componente genérico).
- `aria-selected` en las filas seleccionadas y `aria-label` en checkboxes (tabla, card y collapse) y en los botones de cierre de las pills.
- `aria-label` en las flechas de paginación deshabilitadas.

---

## [1.1.0] — 2026-04-04

### Added

- **Pipeline de build y directiva `@koreScripts`.** Los plugins de Alpine se compilan en un bundle IIFE (`dist/kore-ui.js`) que se sirve desde una ruta de Laravel. `@koreScripts` inyecta el `<script>` que apunta a ella, así que instalar la librería ya no obliga a tocar la configuración de npm de la aplicación ni a importar desde `vendor/`.

---

## [1.0.0] — 2026-04-03

Primera versión estable. El salto desde `0.2.5` marca la estabilización de la API pública: a partir de aquí el versionado sigue SemVer con garantías de compatibilidad. El único cambio de código respecto a `0.2.5` es el fix de abajo.

### Fixed

- **`dropdown.item` sin compilar en `ActionColumn`.** Blade no compila componentes `<x-kore::*>` que llevan directivas `@if` dentro de su etiqueta de apertura. Se sustituyen por expresiones ternarias (`:style`, `:target`, `:rel`) para que las acciones por fila se rendericen.

---

## [0.1.0] — 2026-03-18

Primera versión pre-release de kore-ui. Incluye el sistema base completo con overlay, feedback, theming, 18 componentes de formulario y 20+ componentes UI.

### Added

#### Sistema Overlay
- `OverlayManager` — Componente Livewire que gestiona el stack de overlays
- Tipos soportados: modal, drawer (izquierda/derecha), bottom-sheet, fullscreen, confirm
- Stacking con transiciones, close on escape/click-away, scroll locking
- Swipe-to-close en bottom-sheet
- Backdrop blur configurable
- Trait `HasOverlayBehavior` para integrar en cualquier componente Livewire
- Plugin Alpine `KoreOverlay` para gestión de estado frontend

#### Sistema Feedback
- `FeedbackManager` — Componente Livewire para toasts
- `ConfirmDialog` — Componente Livewire para diálogos de confirmación
- API fluida: `kore_toast()->success('Guardado')->send()`
- Posiciones configurables, timeout, swipe-to-dismiss
- Agrupación de toasts idénticos con contador
- Sole mode para limpiar toasts previos
- Plugin Alpine `KoreFeedback`

#### Sistema de Theming
- `kore-theme.css` con tokens semánticos en OKLCH (`--kore-primary`, `--kore-bg`, etc.)
- Dark mode automático vía `.dark` / `[data-theme="dark"]`
- Tokens de radio (`--kore-radius-sm/md/lg/xl`)
- `<x-kore::theme-switch>` — Selector light/dark/system
- Alpine store `koreTheme` con detección de preferencia del sistema
- Anti-FOUC integrado

#### Componentes de Formulario (18)
- `<x-kore::input>` — Input con íconos, prefix/suffix, clearable, tamaños
- `<x-kore::textarea>` — Multi-línea con rows configurables
- `<x-kore::select>` — Native o custom con búsqueda, async, creatable, multi-select
- `<x-kore::checkbox>` — Con manejo de errores
- `<x-kore::radio>` — Con radio groups
- `<x-kore::toggle>` — Switch component
- `<x-kore::password>` — Con toggle de visibilidad e indicador de fortaleza
- `<x-kore::number>` — Input numérico con soporte de moneda (USD configurable)
- `<x-kore::datepicker>` — Calendario completo con rangos, time, presets, formato/locale
- `<x-kore::time-picker>` — Selector de hora
- `<x-kore::upload>` — Drag & drop, progreso, validación, retry, auto-upload
- `<x-kore::range>` — Slider con track/thumb sizing
- `<x-kore::rating>` — Estrellas/íconos configurables, clearable
- `<x-kore::tag-input>` — Entrada multi-valor con tags
- `<x-kore::color-picker>` — Grid de colores personalizable
- `<x-kore::maskable>` — Input con máscara
- `<x-kore::input-otp>` — Input de código OTP
- `<x-kore::field>` — Wrapper para label, hint y mensajes de error
- `<x-kore::float-label>` — Label animado en focus

#### Componentes UI (20+)
- `<x-kore::button>` — Tamaños (sm/md/lg), variantes (solid/outline/ghost/soft/link), colores, íconos
- `<x-kore::button-group>` — Agrupación de botones
- `<x-kore::alert>` — Notificaciones con ícono y título
- `<x-kore::badge>` — Indicadores de estado con variantes
- `<x-kore::card>` — Con imagen, colapsable, bordered/shadowed
- `<x-kore::dropdown>` — Menú popup con posicionamiento y modo persistente
- `<x-kore::tooltip>` — Tooltips con posicionamiento
- `<x-kore::avatar>` — Con fallback
- `<x-kore::avatar-group>` — Múltiples avatars agrupados
- `<x-kore::loading>` — Spinner de carga
- `<x-kore::page-loading>` — Overlay de carga full-page con blur
- `<x-kore::accordion>` — Secciones expandibles con variantes
- `<x-kore::divider>` — Separador visual
- `<x-kore::progress>` — Barra de progreso lineal
- `<x-kore::progress-circle>` — Progreso circular
- `<x-kore::stepper>` — Indicador de pasos (horizontal/vertical)
- `<x-kore::tab>` — Interfaz de pestañas (line/block)
- `<x-kore::toolbar>` — Contenedor de herramientas
- `<x-kore::skeleton>` — Placeholder con shimmer/pulse
- `<x-kore::empty-state>` — Estado vacío
- `<x-kore::chip>` — Componente compacto
- `<x-kore::timeline>` — Línea temporal con variantes
- `<x-kore::stats>` — Estadísticas con animación
- `<x-kore::clipboard>` — Copy-to-clipboard
- `<x-kore::kbd>` — Tecla de teclado
- `<x-kore::boolean>` — Indicador true/false con íconos configurables
- `<x-kore::speed-dial>` — FAB menu flotante
- `<x-kore::splitter>` — Layout redimensionable (horizontal/vertical)
- `<x-kore::carousel>` — Carrusel con autoplay, navegación, indicadores
- `<x-kore::tree>` — Árbol jerárquico con selección y filtrado
- `<x-kore::breadcrumbs>` — Migas de pan con JSON-LD, separadores configurables

#### Configuración
- `config/kore-ui.php` publicable con secciones: overlay, feedback, form, ui, breadcrumbs, theme
- Defaults sensatos para todos los módulos
- Helpers globales: `kore_toast()`, `kore_confirm()`, `kore_breadcrumbs()`

#### Infraestructura
- Service Provider con auto-discovery de Laravel
- Componentes anónimos con namespace `kore` (`<x-kore::*>`)
- Plugins Alpine registrados automáticamente via `alpine:init`
- Tests con Pest 3 + Orchestra Testbench 10

### Fixed
- Centrado vertical de modales usando `min-h-dvh`
- Scroll y layout de modales largos, drawers y bottom-sheets

---

[2.0.0]: https://github.com/koreui/kore-ui/releases/tag/v2.0.0
[1.7.1]: https://github.com/koreui/kore-ui/releases/tag/v1.7.1
[1.7.0]: https://github.com/koreui/kore-ui/releases/tag/v1.7.0
[1.6.0]: https://github.com/koreui/kore-ui/releases/tag/v1.6.0
[1.5.0]: https://github.com/koreui/kore-ui/releases/tag/v1.5.0
[1.4.1]: https://github.com/koreui/kore-ui/releases/tag/v1.4.1
[1.4.0]: https://github.com/koreui/kore-ui/releases/tag/v1.4.0
[1.3.0]: https://github.com/koreui/kore-ui/releases/tag/v1.3.0
[1.2.0]: https://github.com/koreui/kore-ui/releases/tag/v1.2.0
[1.1.0]: https://github.com/koreui/kore-ui/releases/tag/v1.1.0
[1.0.0]: https://github.com/koreui/kore-ui/releases/tag/v1.0.0
[0.1.0]: https://github.com/koreui/kore-ui/releases/tag/v0.1.0
