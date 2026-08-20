# Tree

Componente de árbol jerárquico con expand/collapse, selección, filtrado y múltiples modos de selección.

## Uso básico

```blade
<x-kore::tree :nodes="[
    ['key' => 'docs', 'label' => 'Documents', 'icon' => 'folder', 'children' => [
        ['key' => 'readme', 'label' => 'README.md', 'icon' => 'file-text', 'children' => []],
    ]],
    ['key' => 'src', 'label' => 'Source', 'icon' => 'folder', 'children' => []],
]" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `nodes` | `array` | `[]` | Datos del árbol |
| `selectable` | `bool` | `false` | Habilitar selección |
| `selectionMode` | `string` | `single` | Modo: `single`, `multiple`, `checkbox` |
| `expandedKeys` | `array` | `[]` | Nodos expandidos inicialmente |
| `selectedKeys` | `array` | `[]` | Nodos seleccionados inicialmente |
| `filter` | `bool` | `false` | Mostrar campo de filtro |
| `filterPlaceholder` | `string` | `Filter...` | Placeholder del filtro, y nombre accesible del campo |
| `ariaLabel` | `string\|null` | `config` | Nombre del árbol, para distinguir varios en la misma página |

## Teclado

| Tecla | Qué hace |
|---|---|
| ↑ ↓ | Sube y baja por los nodos **visibles** |
| → | Abre la rama; si ya está abierta, entra en el primer hijo |
| ← | Cierra la rama; si ya está cerrada, sube al padre |
| `Home` / `End` | Al primero y al último visible |
| `Enter` / espacio | Elige el nodo con el foco |

El árbol es **una** parada del tabulador, no una por nodo: entra en el que tenga el foco —o en el primero— y a partir de ahí se recorre con las flechas. Es el patrón ARIA de un `tree`.

## Convivencia con Livewire

El árbol se pinta entero desde el cliente con un `x-for`, así que la raíz lleva `wire:ignore`: el morph de Livewire compara el DOM con un HTML del servidor donde esas filas no existen, y al reemplazar el `<template>` dejaba el componente muerto para siempre.

Los nodos viajan por eso en un `<script type="application/json">` **fuera** del `wire:ignore`, que Livewire sí actualiza y que el componente vigila. Cambiar `:nodes` desde el servidor funciona sin más:

```blade
{{-- Al cambiar $nodes en el componente Livewire, el árbol se entera. --}}
<x-kore::tree :nodes="$nodes" />
```

Lo que **no** vuelve del servidor es el estado de apertura: manda el usuario a partir de la primera carga. `expandedKeys` es el estado inicial, no una atadura.

## Escala

El árbol **no virtualiza**: pinta una fila por nodo aunque esté plegada, y las esconde con `x-show`. Medido con 100 raíces de 20 hijos:

| | |
|---|---|
| Nodos de datos | 2.100 |
| Filas en el DOM | 2.100 |
| Filas visibles | 100 |
| Nodos de DOM totales | 12.810 |
| JSON en el HTML | 79 kB |

A partir de unos cientos de nodos, conviene cargar los hijos por demanda en vez de mandar el árbol entero.

## Estructura de nodo

```php
[
    'key' => 'unique-id',       // Clave única (requerido)
    'label' => 'Display Text',  // Texto visible (requerido)
    'icon' => 'folder',         // Icono Lucide (opcional)
    'children' => [],           // Hijos (array de nodos)
]
```

## Selección

```blade
<x-kore::tree :nodes="$nodes" :selectable="true" selectionMode="multiple" />
<x-kore::tree :nodes="$nodes" :selectable="true" selectionMode="checkbox" />
```

## Filtro

```blade
<x-kore::tree :nodes="$nodes" :filter="true" filterPlaceholder="Buscar..." />
```

## Eventos

El componente emite `tree-selection-change` con `{ selectedKeys: [...] }` al cambiar la selección.
