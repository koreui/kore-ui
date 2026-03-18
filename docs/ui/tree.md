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
| `filterPlaceholder` | `string` | `Filter...` | Placeholder del filtro |

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
