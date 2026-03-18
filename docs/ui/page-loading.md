# Page Loading

Loading fullscreen a nivel de página. Se monta una vez en el layout y se controla via eventos desde Alpine o Livewire.

## Setup

Agregar una vez en el layout principal (junto al feedback-manager y overlay-manager):

```blade
<x-kore::page-loading />
```

## Uso desde Alpine

```blade
{{-- Mostrar --}}
<button x-on:click="$dispatch('kore-page-loading', { text: 'Cargando...' })">
    Mostrar
</button>

{{-- Cerrar --}}
<button x-on:click="$dispatch('kore-page-loading-close')">
    Cerrar
</button>

{{-- O cerrar con show: false --}}
<button x-on:click="$dispatch('kore-page-loading', { show: false })">
    Cerrar
</button>
```

## Uso desde Livewire

```php
// Mostrar
$this->dispatch('kore-page-loading', show: true, text: 'Procesando...');

// Cerrar
$this->dispatch('kore-page-loading', show: false);

// O con el evento dedicado
$this->dispatch('kore-page-loading-close');
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `type` | `string` | `config(spinner)` | Tipo de indicador: `spinner`, `dots`, `pulse` |
| `blur` | `bool` | `config(true)` | Backdrop blur en el overlay |
| `text` | `string\|null` | `config(null)` | Texto default (override por evento) |

## Eventos

| Evento | Detalle | Descripción |
|--------|---------|-------------|
| `kore-page-loading` | `{ text?, show? }` | Muestra el loading. Si `show: false`, lo cierra |
| `kore-page-loading-close` | — | Cierra el loading |

## Configuración

```php
// config/kore-ui.php
'ui' => [
    'page-loading' => [
        'type' => 'spinner',
        'blur' => true,
        'text' => null,        // texto default
    ],
],
```
