# Splitter

Paneles redimensionables con separador draggable. Soporta orientación horizontal/vertical, límites min/max y persistencia de estado.

## Uso básico

```blade
<x-kore::splitter class="h-64">
    <x-kore::splitter.panel :size="30">
        Panel izquierdo
    </x-kore::splitter.panel>
    <x-kore::splitter.panel :size="70">
        Panel derecho
    </x-kore::splitter.panel>
</x-kore::splitter>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `orientation` | `string` | `config(horizontal)` | Orientación: `horizontal`, `vertical` |
| `gutterSize` | `int` | `config(8)` | Ancho del separador en px |
| `stateKey` | `string\|null` | `null` | Clave para persistir tamaños en localStorage |

## Props del panel

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `size` | `int\|null` | `null` | Tamaño inicial en % |
| `minSize` | `int` | `0` | Tamaño mínimo en % |
| `maxSize` | `int` | `100` | Tamaño máximo en % |

## Vertical

```blade
<x-kore::splitter orientation="vertical" class="h-96">
    <x-kore::splitter.panel :size="40">Arriba</x-kore::splitter.panel>
    <x-kore::splitter.panel :size="60">Abajo</x-kore::splitter.panel>
</x-kore::splitter>
```

## Persistencia

```blade
<x-kore::splitter stateKey="my-layout">
    <!-- Los tamaños se guardan en localStorage -->
</x-kore::splitter>
```
