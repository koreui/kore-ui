# Empty State

Indicador visual para secciones vacías con soporte para iconos, imágenes y acciones.

## Uso básico

```blade
<x-kore::empty-state
    title="No results found"
    description="Try adjusting your search or filter." />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `title` | `string\|null` | `null` | Título principal |
| `description` | `string\|null` | `null` | Texto descriptivo |
| `icon` | `string\|null` | `null` | Icono Lucide (se muestra en círculo muted) |
| `image` | `string\|null` | `null` | URL de imagen (alternativa al icono) |

## Slots

| Slot | Descripción |
|------|-------------|
| default | Contenido personalizado debajo de la descripción |
| `action` | Zona para botones de acción |

## Con icono

```blade
<x-kore::empty-state
    icon="inbox"
    title="Your inbox is empty"
    description="New messages will appear here." />
```

## Con imagen

```blade
<x-kore::empty-state
    image="/images/empty.svg"
    title="No favorites yet"
    description="Items you favorite will appear here." />
```

## Con acción

```blade
<x-kore::empty-state
    icon="file-plus"
    title="No documents"
    description="Get started by creating a new document.">
    <x-slot:action>
        <x-kore::button icon="plus" label="Create document" />
    </x-slot:action>
</x-kore::empty-state>
```
