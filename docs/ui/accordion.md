# Accordion

Paneles colapsables para organizar contenido en secciones expandibles. Usa Alpine.js para la interactividad.

## Uso básico

```blade
<x-kore::accordion>
    <x-kore::accordion.item id="faq-1" title="¿Cómo funciona?">
        Contenido de la respuesta.
    </x-kore::accordion.item>

    <x-kore::accordion.item id="faq-2" title="¿Cuánto cuesta?">
        Información de precios.
    </x-kore::accordion.item>
</x-kore::accordion>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `multiple` | `bool` | `false` | Permite abrir múltiples ítems a la vez |
| `variant` | `string` | `bordered` | Variante visual: `bordered`, `flush`, `separated` |

## Props de item

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `id` | `string` | requerido | Identificador único del ítem |
| `title` | `string` | requerido | Título del panel |
| `icon` | `string\|null` | `null` | Icono Lucide junto al título |
| `disabled` | `bool` | `false` | Desactiva la interacción |
| `open` | `bool` | `false` | Estado inicial abierto |

## Múltiples abiertos

```blade
<x-kore::accordion multiple>
    <x-kore::accordion.item id="s1" title="Sección 1" open>
        Abierta por defecto.
    </x-kore::accordion.item>

    <x-kore::accordion.item id="s2" title="Sección 2">
        Cerrada por defecto.
    </x-kore::accordion.item>

    <x-kore::accordion.item id="s3" title="Sección 3" open>
        También abierta por defecto.
    </x-kore::accordion.item>
</x-kore::accordion>
```

## Variantes

```blade
{{-- Sin bordes exteriores --}}
<x-kore::accordion variant="flush">
    <x-kore::accordion.item id="a" title="Flush A">Contenido</x-kore::accordion.item>
    <x-kore::accordion.item id="b" title="Flush B">Contenido</x-kore::accordion.item>
</x-kore::accordion>

{{-- Ítems separados con gap --}}
<x-kore::accordion variant="separated">
    <x-kore::accordion.item id="a" title="Separado A">Contenido</x-kore::accordion.item>
    <x-kore::accordion.item id="b" title="Separado B">Contenido</x-kore::accordion.item>
</x-kore::accordion>
```

## Con icono y deshabilitado

```blade
<x-kore::accordion>
    <x-kore::accordion.item id="user" title="Perfil" icon="user">
        Configuración del perfil.
    </x-kore::accordion.item>

    <x-kore::accordion.item id="billing" title="Facturación" icon="credit-card" disabled>
        No disponible.
    </x-kore::accordion.item>
</x-kore::accordion>
```

## Integración con Livewire

```blade
<x-kore::accordion wire:model="openPanels">
    <x-kore::accordion.item id="panel-1" title="Panel 1">
        El estado se sincroniza con la propiedad $openPanels del componente Livewire.
    </x-kore::accordion.item>
</x-kore::accordion>
```

## Plugin Alpine

El componente usa el plugin `KoreAccordion` que se registra automáticamente. Gestiona el estado de apertura/cierre, las animaciones y la accesibilidad (ARIA attributes).
