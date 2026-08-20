# Chip

Elemento compacto para representar etiquetas, filtros o selecciones.

## Uso básico

```blade
<x-kore::chip label="Tag" />
<x-kore::chip label="Active" color="success" />
<x-kore::chip label="Laravel" icon="code" color="primary" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Texto del chip |
| `icon` | `string\|null` | `null` | Icono Lucide |
| `image` | `string\|null` | `null` | URL de imagen (avatar circular) |
| `removable` | `bool` | `false` | Muestra botón para eliminar |
| `color` | `string` | `muted` | Color: `primary`, `success`, `warning`, `destructive`, `info`, `muted` |
| `size` | `string` | `md` | Tamaño: `sm`, `md` |
| `variant` | `string` | `soft` | Variante: `soft`, `solid`, `outline` |

## Variantes

```blade
<x-kore::chip label="Soft" variant="soft" color="primary" />
<x-kore::chip label="Solid" variant="solid" color="primary" />
<x-kore::chip label="Outline" variant="outline" color="primary" />
```

## Con imagen

```blade
<x-kore::chip label="John Doe" image="/avatar.jpg" :removable="true" />
```

## Removable

```blade
<x-kore::chip label="Remove me" :removable="true" />
```

El chip se oculta al hacer clic en la X y dispara un evento `chip-removed`.

## Eventos

| Evento | Descripción |
|--------|-------------|
| `chip-removed` | Disparado cuando el usuario elimina el chip |

## Configuración

En `config/kore-ui.php`:

```php
'chip' => [
    'variant' => 'soft',
],
```

## Accesibilidad

- Las variantes `soft` y `outline` usan el token `text-kore-{color}-text`. Ver la nota de `badge`.
- El botón de quitar mide 24×24 (WCAG 2.2). Medía 18×18.
