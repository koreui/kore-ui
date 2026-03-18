# Skeleton

Placeholder animado que muestra la estructura del contenido mientras se carga.

## Uso básico

```blade
<x-kore::skeleton />
<x-kore::skeleton shape="circle" size="3rem" />
<x-kore::skeleton shape="text" :lines="3" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `shape` | `string` | `rect` | Forma: `rect`, `circle`, `text` |
| `width` | `string\|null` | `null` | Ancho CSS (ej: `200px`, `100%`) |
| `height` | `string\|null` | `null` | Alto CSS (ej: `1rem`, `40px`) |
| `size` | `string\|null` | `null` | Atajo para width=height (ideal para circles) |
| `lines` | `int` | `1` | Número de líneas para `shape="text"` |
| `animation` | `string` | `shimmer` | Tipo de animación: `shimmer`, `pulse`, `none` |
| `rounded` | `string\|null` | `null` | Border radius personalizado |

## Formas

```blade
{{-- Rectángulo (default) --}}
<x-kore::skeleton width="100%" height="1rem" />

{{-- Círculo --}}
<x-kore::skeleton shape="circle" size="3rem" />

{{-- Texto multilínea --}}
<x-kore::skeleton shape="text" :lines="4" />
```

## Composición (Card skeleton)

```blade
<div class="rounded-kore-lg border border-kore-border p-4 space-y-4">
    <x-kore::skeleton width="100%" height="8rem" />
    <x-kore::skeleton shape="text" :lines="2" />
    <div class="flex items-center gap-3">
        <x-kore::skeleton shape="circle" size="2.5rem" />
        <div class="flex-1 space-y-2">
            <x-kore::skeleton width="60%" height="0.75rem" />
            <x-kore::skeleton width="40%" height="0.75rem" />
        </div>
    </div>
</div>
```

## Animaciones

```blade
{{-- Shimmer (default) — gradiente que barre de izquierda a derecha --}}
<x-kore::skeleton animation="shimmer" />

{{-- Pulse — fade de opacidad --}}
<x-kore::skeleton animation="pulse" />

{{-- Sin animación --}}
<x-kore::skeleton animation="none" />
```

## Configuración

En `config/kore-ui.php`:

```php
'skeleton' => [
    'animation' => 'shimmer',  // shimmer|pulse|none
],
```
