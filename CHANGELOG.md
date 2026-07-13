# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y el proyecto usa [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.4.0] — 2026-07-13

Release de correcciones. Dos bugs visibles y una pieza de configuración que llevaba tiempo mintiendo.

### Fixed

- **DataTable — overlay de carga desalineado.** El overlay usaba `wire:loading.delay` sin modificador de display, así que Livewire le aplicaba `display:inline-block` en línea, pisando la clase `flex` y dejando el spinner arriba a la izquierda en vez de centrado. Ahora usa `wire:loading.delay.flex`.
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

[1.3.0]: https://github.com/koreui/kore-ui/releases/tag/v1.3.0
[0.1.0]: https://github.com/koreui/kore-ui/releases/tag/v0.1.0
