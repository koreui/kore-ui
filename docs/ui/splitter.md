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

## Teclado y lectores de pantalla

La barra es enfocable y se mueve con las flechas: ←→ en horizontal, ↑↓ en vertical, de dos en dos por ciento.

Se anuncia como un *window splitter*: lleva `role="separator"`, `aria-orientation`, un nombre accesible y `aria-valuemin` / `aria-valuemax` / `aria-valuenow`. El valor sigue a las flechas, así que un lector de pantalla dice en qué posición queda cada vez. El nombre sale de `kore-ui.ui.translations.resize` —la barra la crea el JavaScript, así que no hay etiqueta en Blade donde ponerlo—:

```php
'ui' => [
    'translations' => [
        'resize' => 'Redimensionar paneles',
    ],
],
```

## Convivencia con Livewire

Las barras las inserta el JavaScript, así que **no están en el HTML que emite el servidor**. El morph de Livewire las veía como nodos sobrantes y las borraba: con ellas se iba el layout entero y los paneles colapsaban a su tamaño mínimo. Ahora se vuelven a montar en cuanto desaparecen, conservando lo que el usuario hubiera arrastrado.

No hace falta `wire:ignore`: el contenido de los paneles se sigue actualizando con normalidad.
