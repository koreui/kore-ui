# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y el proyecto usa [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.4.0] — 2026-07-13

Release de correcciones. Dos bugs visibles y una pieza de configuración que llevaba tiempo mintiendo.

### Fixed

- **DataTable — overlay de carga desalineado.** El overlay usaba `wire:loading.delay` sin modificador de display, así que Livewire le aplicaba `display:inline-block` en línea, pisando la clase `flex` y dejando el spinner arriba a la izquierda en vez de centrado. Ahora usa `wire:loading.delay.flex`.

  > Esto **revierte** el cambio hecho en 1.2.0, que atribuía al modificador `.flex` un overlay que se quedaba visible para siempre. La causa real de aquel síntoma era el hook `morphed` de pinning, que al lanzar una excepción rompía el ciclo de morph y congelaba `wire:loading` — y se corrigió por separado en el mismo 1.2.0. El modificador `.flex` está documentado en Livewire y es la forma correcta de que un overlay `flex` no reciba `inline-block`.
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

[1.4.0]: https://github.com/koreui/kore-ui/releases/tag/v1.4.0
[1.3.0]: https://github.com/koreui/kore-ui/releases/tag/v1.3.0
[1.2.0]: https://github.com/koreui/kore-ui/releases/tag/v1.2.0
[1.1.0]: https://github.com/koreui/kore-ui/releases/tag/v1.1.0
[1.0.0]: https://github.com/koreui/kore-ui/releases/tag/v1.0.0
[0.1.0]: https://github.com/koreui/kore-ui/releases/tag/v0.1.0
