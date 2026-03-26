# Icon

Componente para renderizar iconos con tamaños predefinidos, colores semánticos, animaciones y accesibilidad automática. Usa Blade Lucide Icons por defecto.

## Uso basico

```blade
<x-kore::icon name="search" />
<x-kore::icon name="home" />
<x-kore::icon name="settings" />
```

## Props

| Prop | Tipo | Default | Descripcion |
|------|------|---------|-------------|
| `name` | `string` | (requerido) | Nombre del icono Lucide (ej: `search`, `check-circle`) |
| `size` | `string` | `config(md)` | Tamano: `xs`, `sm`, `md`, `lg`, `xl`, `2xl` |
| `color` | `string\|null` | `null` | Color: `primary`, `secondary`, `success`, `warning`, `destructive`, `info`, `muted` |
| `animation` | `string\|null` | `null` | Animacion: `spin`, `pulse`, `bounce`, `ping` |
| `error` | `bool` | `false` | Atajo para `color="destructive"` |
| `label` | `string\|null` | `null` | Texto junto al icono |
| `labelPosition` | `string` | `right` | Posicion del label: `left`, `right` |
| `strokeWidth` | `float\|null` | `null` | Override del stroke-width SVG (Lucide default: 2) |
| `raw` | `bool` | `false` | Usa `name` como nombre de componente completo sin prefijar |

## Tamanos

```blade
<x-kore::icon name="star" size="xs" />   {{-- 12px --}}
<x-kore::icon name="star" size="sm" />   {{-- 14px --}}
<x-kore::icon name="star" size="md" />   {{-- 16px (default) --}}
<x-kore::icon name="star" size="lg" />   {{-- 20px --}}
<x-kore::icon name="star" size="xl" />   {{-- 24px --}}
<x-kore::icon name="star" size="2xl" />  {{-- 32px --}}
```

## Colores

Sin color por defecto, el icono hereda `currentColor` del padre.

```blade
<x-kore::icon name="check-circle" color="success" />
<x-kore::icon name="alert-triangle" color="warning" />
<x-kore::icon name="x-circle" color="destructive" />
<x-kore::icon name="info" color="info" />
<x-kore::icon name="circle" color="primary" />
<x-kore::icon name="minus-circle" color="muted" />
```

### Estado de error

Atajo para usar el color destructive, util en formularios:

```blade
<x-kore::icon name="alert-circle" error />
<x-kore::icon name="alert-circle" :error="$errors->has('email')" />
```

## Animaciones

```blade
<x-kore::icon name="loader-2" animation="spin" />
<x-kore::icon name="bell" animation="pulse" />
<x-kore::icon name="arrow-down" animation="bounce" />
<x-kore::icon name="circle" animation="ping" />
```

## Con etiqueta

El icono se envuelve en un `<span>` con flexbox cuando se usa label:

```blade
<x-kore::icon name="save" label="Guardar" />
<x-kore::icon name="arrow-left" label="Regresar" labelPosition="left" />
```

## Accesibilidad

Por defecto los iconos son decorativos (`aria-hidden="true"`). Para iconos significativos, usar `aria-label`:

```blade
{{-- Decorativo (default) --}}
<x-kore::icon name="search" />

{{-- Significativo --}}
<x-kore::icon name="triangle-alert" aria-label="Advertencia" />
```

## Stroke width

Controla el grosor del trazo del icono (Lucide default: 2):

```blade
<x-kore::icon name="heart" :strokeWidth="1" />    {{-- Ligero --}}
<x-kore::icon name="heart" />                      {{-- Normal (2) --}}
<x-kore::icon name="heart" :strokeWidth="2.5" />   {{-- Bold --}}
```

## Proveedor de iconos

Por defecto usa Lucide. Para otros proveedores blade-icons, usa `raw`:

```blade
{{-- Lucide (default) --}}
<x-kore::icon name="search" />

{{-- Otro proveedor (raw = sin prefijar) --}}
<x-kore::icon name="heroicon-o-home" raw />
```

El prefijo por defecto se configura en `config/kore-ui.php`:

```php
'ui' => [
    'icon' => [
        'prefix' => 'lucide',   // blade-icons prefix
        'size' => null,          // null = usa ui.size (md)
    ],
],
```

## Configuracion

```php
// config/kore-ui.php
'ui' => [
    'icon' => [
        'prefix' => 'lucide',
        'size' => null,
    ],
],
```
