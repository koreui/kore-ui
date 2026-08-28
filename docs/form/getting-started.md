# Form Components - Getting Started

## What are the Form Components?

The kore-ui form components provide **10 anonymous Blade components** with Alpine.js interactivity for building forms in Laravel. All components support `wire:model`, automatic error detection from Laravel's `$errors` bag, and semantic design tokens.

- **Input** — Text input with icon, prefix/suffix, clearable, sizes
- **Textarea** — Multi-line with auto-resize and character counter
- **Select** — Single/multi, searchable, native, grouped, async-ready
- **Checkbox** — Custom styled with description and indeterminate state
- **Radio** — Radio buttons with radio group wrapper
- **Toggle** — Switch with on/off labels and sizes
- **Password** — Input with toggleable visibility
- **Number** — Increment/decrement controls with long-press
- **Input OTP** — One-time password with auto-advance and paste
- **Float Label** — Floating label wrapper with 3 variants

## Prerequisites

1. The `kore-ui` package installed and set up — see [Getting Started](../getting-started.md).
2. `@koreScripts` directive in your layout (provides all form Alpine.js plugins).
3. CSS configured — `kore-theme.css` imported and Tailwind sources registered.

## Usage

All components use the `x-kore::` prefix and work inside any Blade or Livewire view:

```html
<form wire:submit="save">
    <x-kore::input wire:model="name" label="Name" icon="user" />
    <x-kore::input wire:model="email" label="Email" type="email" />
    <x-kore::password wire:model="password" label="Password" />
    <x-kore::select wire:model="country" label="Country" :options="$countries" searchable />
    <x-kore::textarea wire:model="bio" label="Bio" auto-resize :max-length="500" />
    <x-kore::toggle wire:model="active" label="Active" />
    <x-kore::checkbox wire:model="terms" label="I accept the terms" />
    <button type="submit">Save</button>
</form>
```

## Shared Features

All form components share these capabilities:

### Automatic Error Detection

Components detect errors from Laravel's `$errors` bag by matching the `name` prop (or `wire:model` attribute):

```html
{{-- Automatic: uses $errors->first('email') --}}
<x-kore::input wire:model="email" name="email" label="Email" />

{{-- Manual error --}}
<x-kore::input label="Email" error="Invalid email format" />

{{-- Suppress error display --}}
<x-kore::input wire:model="email" label="Email" :show-error="false" />
```

### Sizes

Three sizes available on all components: `sm`, `md` (default), `lg`.

```html
<x-kore::input label="Small" size="sm" />
<x-kore::input label="Medium" />
<x-kore::input label="Large" size="lg" />
```

### Hint Text

Shown below the input. Hidden when an error is displayed.

```html
<x-kore::input label="Email" hint="We won't share it" />
```

### Required Indicator

Adds a red asterisk next to the label and the HTML `required` attribute.

```html
<x-kore::input label="Email" required />
```

### Texto enriquecido

`<x-kore::editor>` produce HTML, y lo que produce **hay que sanearlo en el
servidor** antes de guardarlo: el saneado del navegador no es una frontera de
seguridad. Ver [editor](editor.md#lo-primero-lo-que-se-guarda-hay-que-sanearlo-en-el-servidor).

### `disabled` y `readonly`

Los dos bloquean la edición; se diferencian en el envío. Un campo `disabled` **no
viaja con el formulario**; uno `readonly` sí. El modo consulta de un formulario
—enseñar lo que hay guardado sin dejar cambiarlo, pero conservando el valor— es
`readonly`, no `disabled`.

```html
<x-kore::select label="País" :options="$paises" wire:model="pais" readonly />
```

Los doce campos compuestos lo aceptan: `number`, `select`, `datepicker`,
`time-picker`, `color-picker`, `tag-input`, `key-value`, `input-otp`, `upload`,
`repeater`, `transfer` y `order-list`, además de los de texto (`input`,
`textarea`, `password`, `maskable`).

Qué hace en cada uno:

| | `disabled` | `readonly` |
|---|---|---|
| Aspecto | atenuado al 50% | normal |
| Foco y tabulador | fuera del recorrido | dentro: el valor se puede leer y copiar |
| Se envía | no | **sí** |
| Paneles (select, fechas, hora, color) | no abren | no abren |
| Botones auxiliares (flechas, «x», añadir, borrar, arrastrar) | apagados | apagados |
| Buscar dentro del campo (`transfer`, `select`) | no | **sí** — buscar no es editar |

Dos detalles que no se ven desde fuera:

- **`<select>` no tiene `readonly` en HTML.** El atributo existe para los inputs
  de texto y ahí se acaba, y deshabilitarlo tampoco sirve porque entonces no se
  envía. En modo nativo se bloquean el ratón y el teclado dejando pasar el
  tabulador, y se marca `aria-readonly`.
- **En `upload` la zona de selección desaparece** con `readonly` —no hay nada que
  arrastrar ahí—, mientras que con `disabled` se queda a la vista, atenuada.

## Configuration

Customize defaults in `config/kore-ui.php`:

```php
'form' => [
    'size' => 'md',              // Default size for all form components
    'show_errors' => true,       // Auto-detect errors from $errors bag
    'select' => [
        'debounce' => 300,       // Debounce for async search (ms)
        'min_search' => 2,       // Min chars before async search fires
        'search_threshold' => 10,
    ],
    'password' => ['toggleable' => true],
    'textarea' => ['rows' => 4],
],
```

## Architecture

Form components are **anonymous Blade components** registered via `Blade::anonymousComponentPath()`. This means:

- No PHP class per component — logic lives in `@props` / `@php` blocks
- Alpine.js handles interactivity (Select, InputOtp)
- `$attributes->merge()` passes `wire:model` and other attributes directly to the native input
- The `<x-kore::field>` component wraps all inputs with label, error, and hint

---

## Atributos, `id` y morph

Tres cosas que conviene saber si los componentes de formulario van dentro de un componente Livewire, porque explican casi todo lo raro que puede pasar.

### Dónde aterriza lo que escribes en la etiqueta

Un atributo que ningún `@props` declara —`class`, `data-*`, `style`, `aria-*`, `x-on:*`— se emite en el DOM, pero **no siempre en el mismo sitio**:

| Componentes | Dónde va |
|---|---|
| `input`, `textarea`, `number` (decimal), `password`, `checkbox`, `radio`, `toggle`, `range`, `select` nativo | En **el control** (`<input>`, `<select>`, `<textarea>`) |
| `datepicker`, `time-picker`, `color-picker`, `input-otp`, `tag-input`, `key-value`, `upload`, `rating`, `maskable`, `repeater`, `select` (modo Alpine), `radio-group` | En **la raíz** del componente |

La diferencia no es caprichosa: los primeros envuelven un control nativo y lo natural es que un `placeholder` o un `autocomplete` acaben en él; los segundos son widgets compuestos, donde el «control» son varios elementos y no hay uno solo al que apuntar.

`class` se **suma** a las clases del componente, no las sustituye. Dos atributos se quedan siempre fuera de ese volcado: `id`, que ya se usa para el propio campo, y `wire:model`, que vive en el input oculto.

```blade
{{-- El data-* llega, y la clase se suma a las del componente --}}
<x-kore::datepicker label="Fecha" wire:model="fecha" class="w-64" data-cy="fecha-alta" />
```

### El `id` de un campo tiene que ser estable

Si no le pasas `name` ni `wire:model`, el componente se inventa un `id`. Ese id **es el mismo en cada render** de la misma vista, y no es un detalle estético.

El morph de Livewire empareja los nodos viejos con los nuevos por `id`. Si el id cambia, no reconoce el nodo: lo quita, pone otro en su lugar y Alpine arranca el componente desde cero. En la práctica eso significa que el desplegable se cierra solo, el calendario vuelve al mes de hoy y la búsqueda a medio escribir se borra — cada vez que **cualquier otro** componente de la página habla con el servidor.

Es la misma razón por la que los gráficos numeran sus ids (`ChartContext`). Si generas ids a mano para pasárselos a un componente, que sean deterministas:

```blade
{{-- Mal: distinto en cada render --}}
<x-kore::select :id="'pais-'.uniqid()" :options="$paises" />

{{-- Bien: derivado de algo estable --}}
<x-kore::select :id="'pais-'.$fila->id" :options="$paises" />
```

### `:options` que cambian desde el servidor

Funcionan, pero por un camino concreto. El panel de un `<x-kore::select>` se teleporta a `body` para escapar de los `overflow:hidden`, y ahí el morph de Livewire ya no lo alcanza. Por eso las opciones no viajan dentro del `x-data` —que Alpine evalúa una sola vez— sino en un nodo JSON aparte que el componente vigila:

```blade
{{-- Provincia según país: al cambiar $pais, el panel se actualiza --}}
<x-kore::select wire:model.live="pais" :options="$paises" />
<x-kore::select wire:model="provincia" :options="$this->provinciasDe($pais)" />
```

Con muchas opciones esto deja de compensar: el componente **no virtualiza**, así que las pinta todas. Diez mil opciones son unos 120.000 nodos en el DOM y algo más de un segundo hasta que el panel se abre. A partir de unos pocos cientos, usa `async` y deja que el servidor filtre.
