# Speed Dial

Botón de acción flotante (FAB) con menú expandible de acciones rápidas.

## Uso básico

```blade
<x-kore::speed-dial :items="[
    ['icon' => 'edit', 'label' => 'Editar'],
    ['icon' => 'trash', 'label' => 'Eliminar'],
    ['icon' => 'share', 'label' => 'Compartir'],
]" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `icon` | `string` | `config(plus)` | Icono del FAB principal |
| `direction` | `string` | `config(up)` | Dirección de expansión: `up`, `down`, `left`, `right` |
| `position` | `string\|null` | `null` | Posición fija: `bottom-right`, `bottom-left`, `top-right`, `top-left` |
| `items` | `array` | `[]` | Acciones del menú |
| `color` | `string` | `primary` | Color del FAB: `primary`, `secondary`, `destructive` |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg` |

## Estructura de items

```php
[
    'icon' => 'edit',          // Icono Lucide (requerido)
    'label' => 'Editar',       // Tooltip (opcional)
    'href' => '/edit',         // Link (opcional)
    'wireClick' => 'edit()',   // Wire click (opcional)
]
```

## Posición fija

```blade
<x-kore::speed-dial position="bottom-right" :items="$items" />
```

## Direcciones

```blade
<x-kore::speed-dial direction="up" :items="$items" />
<x-kore::speed-dial direction="down" :items="$items" />
<x-kore::speed-dial direction="left" :items="$items" />
<x-kore::speed-dial direction="right" :items="$items" />
```
