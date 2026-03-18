# Button

Botón versátil con soporte para variantes, colores, tamaños, iconos, estados de loading y enlaces.

## Uso básico

```blade
<x-kore::button label="Guardar" />
<x-kore::button label="Cancelar" variant="outline" />
<x-kore::button label="Eliminar" color="destructive" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Texto del botón |
| `icon` | `string\|null` | `null` | Icono Lucide a la izquierda |
| `iconRight` | `string\|null` | `null` | Icono Lucide a la derecha |
| `href` | `string\|null` | `null` | URL — renderiza como `<a>` |
| `target` | `string\|null` | `null` | Target del enlace (`_blank`, etc.) |
| `size` | `string` | `config(md)` | Tamaño: `sm`, `md`, `lg` |
| `variant` | `string` | `config(solid)` | Variante: `solid`, `outline`, `ghost`, `soft`, `link` |
| `color` | `string` | `config(primary)` | Color: `primary`, `secondary`, `destructive`, `success`, `warning`, `info` |
| `loading` | `bool` | `false` | Muestra spinner y deshabilita |
| `disabled` | `bool` | `false` | Estado deshabilitado |
| `block` | `bool` | `false` | Ancho completo (`w-full`) |
| `type` | `string` | `button` | Tipo HTML: `button`, `submit`, `reset` |

## Variantes

```blade
<x-kore::button label="Solid" variant="solid" />
<x-kore::button label="Outline" variant="outline" />
<x-kore::button label="Ghost" variant="ghost" />
<x-kore::button label="Soft" variant="soft" />
<x-kore::button label="Link" variant="link" />
```

## Colores

```blade
<x-kore::button label="Primary" color="primary" />
<x-kore::button label="Secondary" color="secondary" />
<x-kore::button label="Destructive" color="destructive" />
<x-kore::button label="Success" color="success" />
<x-kore::button label="Warning" color="warning" />
<x-kore::button label="Info" color="info" />
```

## Con iconos

```blade
<x-kore::button label="Guardar" icon="save" />
<x-kore::button label="Siguiente" icon-right="arrow-right" />
<x-kore::button label="Copiar" icon="copy" icon-right="check" />
```

## Loading

```blade
<x-kore::button label="Guardando..." :loading="true" />
```

## Como enlace

```blade
<x-kore::button label="Ir a inicio" href="/" />
<x-kore::button label="Docs" href="https://docs.com" target="_blank" icon="external-link" />
```

## Button Group

```blade
<x-kore::button-group>
    <x-kore::button label="Izquierda" />
    <x-kore::button label="Centro" />
    <x-kore::button label="Derecha" />
</x-kore::button-group>
```

## Configuración

```php
// config/kore-ui.php
'ui' => [
    'size' => 'md',
    'button' => [
        'variant' => 'solid',
        'color' => 'primary',
    ],
],
```
