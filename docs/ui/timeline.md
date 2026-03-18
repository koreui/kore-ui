# Timeline

Lista cronológica de eventos con marcadores, iconos y contenido personalizable.

## Uso básico

```blade
<x-kore::timeline>
    <x-kore::timeline.item label="Order placed" timestamp="March 15, 2024">
        Your order has been placed successfully.
    </x-kore::timeline.item>
    <x-kore::timeline.item label="Shipped" timestamp="March 16, 2024">
        Your package is on its way.
    </x-kore::timeline.item>
</x-kore::timeline>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `variant` | `string` | `left` | Layout: `left`, `right`, `alternate` |
| `color` | `string` | `primary` | Color por defecto para marcadores |

## Props del item

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `icon` | `string\|null` | `null` | Icono Lucide para el marcador |
| `color` | `string\|null` | `null` | Color del marcador: `primary`, `success`, `warning`, `destructive`, `info`, `muted` |
| `label` | `string\|null` | `null` | Título del evento |
| `timestamp` | `string\|null` | `null` | Fecha/hora del evento |

## Con iconos y colores

```blade
<x-kore::timeline>
    <x-kore::timeline.item label="Created" icon="git-branch" color="primary" timestamp="Day 1" />
    <x-kore::timeline.item label="Tests passing" icon="check" color="success" timestamp="Day 3" />
    <x-kore::timeline.item label="Bug found" icon="bug" color="destructive" timestamp="Day 5" />
    <x-kore::timeline.item label="Deployed" icon="rocket" color="success" timestamp="Day 7" />
</x-kore::timeline>
```

## Variante alternate

```blade
<x-kore::timeline variant="alternate">
    <x-kore::timeline.item label="Kickoff" timestamp="Jan 2024" />
    <x-kore::timeline.item label="Design" timestamp="Feb 2024" />
    <x-kore::timeline.item label="Development" timestamp="Mar 2024" />
    <x-kore::timeline.item label="Launch" timestamp="Apr 2024" />
</x-kore::timeline>
```

Los items impares se alinean a la izquierda y los pares a la derecha.

## Variante right

```blade
<x-kore::timeline variant="right">
    <x-kore::timeline.item label="Step 1" timestamp="Start" />
    <x-kore::timeline.item label="Step 2" timestamp="Middle" />
</x-kore::timeline>
```

## Configuración

En `config/kore-ui.php`:

```php
'timeline' => [
    'variant' => 'left',
],
```
