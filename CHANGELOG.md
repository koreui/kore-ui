# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y el proyecto usa [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.6.0] — 2026-07-14

**Gráficos.** Barras, líneas, áreas y donut, **sin ninguna librería de JavaScript**: la geometría se calcula en PHP y el servidor devuelve el `<svg>` ya dibujado.

### Added

- **`<x-kore::chart>`** y sus marcas: `chart.line`, `chart.area`, `chart.bar`, `chart.donut`, `chart.axis-x`, `chart.axis-y`, `chart.legend` y `chart.tooltip`. Ver [docs/chart/getting-started.md](docs/chart/getting-started.md).
- **`Kore\Charts\`**: el motor de geometría, en PHP puro y sin depender de Blade ni de JavaScript. Sirve igual para un PDF, un email o un export.
- **Paleta categórica `--kore-chart-1` … `--kore-chart-8`**, en claro y en oscuro.

#### El principio que ordena todo el módulo

**El color nunca es un valor: es un token.** Las series se pintan con `var(--kore-chart-1)`, así que **al cambiar de tema el gráfico se repinta solo, sin ejecutar una sola línea de JavaScript**. Verificado en Chrome, Firefox y WebKit.

Eso es lo contrario de lo que hace un motor de `<canvas>`, donde el color es un valor de JS y hay que volver a ejecutar código en cada cambio de tema — con las consecuencias que lleva años pagando el resto del ecosistema Laravel: colores que se intercambian solos entre gráficos, y gráficos que necesitan una recarga para verse bien en modo oscuro.

Y como el `<svg>` lo emite el servidor, **el morph de Livewire deja de ser una amenaza y pasa a ser el mecanismo de actualización**: cambias el dato en PHP y Livewire actualiza el atributo `d` del `<path>` **sin recrear el nodo**. Sin `wire:ignore`, sin `chart.update()`, sin una instancia de JavaScript que proteger.

#### Detalles que quizá quieras conocer

- **No existe un `type="mixed"`.** Un gráfico de barras con una línea encima son dos marcas, una detrás de otra. El orden en que las escribes es el orden en que se pintan.
- **Los ejes dicen números redondos.** El algoritmo de ticks es un puerto del de d3 (ISC, con atribución en [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md)) y da exactamente los mismos valores. El reparto ingenuo que usa el resto del ecosistema PHP produce ejes que dicen "1.224".
- **Un `null` no es un cero.** La línea se corta en el hueco, en vez de dibujar una caída al suelo que nunca ocurrió. Y la curva monótona reinicia sus tangentes ahí, para no inventarse una curva por encima del vacío.
- **Los datos van también en un `<table class="sr-only">`.** Un `<svg>` es tan mudo para un lector de pantalla como un `<canvas>`; como los datos ya están en PHP, la tabla sale gratis. Nadie más en el ecosistema la sirve.
- **Sin leyenda ni tooltip, el gráfico no carga JavaScript.** Y el resize no lo necesita nunca: la geometría está en porcentajes, no en píxeles.
- **La paleta no se cicla.** La novena serie no existe: repetir el color de la primera es peor que no pintarla. Si lo intentas, salta una excepción.

### Fixed

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

> **Limitaciones que conviene conocer.** El eje X es categórico: las fechas se pre-formatean en PHP y entran como categorías (una escala temporal de verdad es otro algoritmo entero). Los puntos de la línea son opt-in, porque son un `<div>` por dato. El techo práctico son unos 2.000 puntos por serie, y no lo pone el dibujo sino el peso del HTML. Ocultar una serie desde la leyenda no reescala los ejes. Y no hay zoom, ni pan, ni tipos exóticos.

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

[1.5.0]: https://github.com/koreui/kore-ui/releases/tag/v1.5.0
[1.4.1]: https://github.com/koreui/kore-ui/releases/tag/v1.4.1
[1.4.0]: https://github.com/koreui/kore-ui/releases/tag/v1.4.0
[1.3.0]: https://github.com/koreui/kore-ui/releases/tag/v1.3.0
[1.2.0]: https://github.com/koreui/kore-ui/releases/tag/v1.2.0
[1.1.0]: https://github.com/koreui/kore-ui/releases/tag/v1.1.0
[1.0.0]: https://github.com/koreui/kore-ui/releases/tag/v1.0.0
[0.1.0]: https://github.com/koreui/kore-ui/releases/tag/v0.1.0
