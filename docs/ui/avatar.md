# Avatar

Avatar con imagen, iniciales auto-generadas, icono fallback, indicador de presencia y grupos.

## Uso básico

```blade
<x-kore::avatar src="/avatar.jpg" name="Juan Pérez" />
<x-kore::avatar name="María García" />
<x-kore::avatar />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `src` | `string\|null` | `null` | URL de imagen |
| `name` | `string\|null` | `null` | Nombre (genera iniciales) |
| `icon` | `string` | `user` | Icono Lucide fallback |
| `size` | `string` | `md` | Tamaño: `xs`, `sm`, `md`, `lg`, `xl` |
| `shape` | `string` | `config(circle)` | Forma: `circle`, `square` |
| `presence` | `string\|null` | `null` | Indicador: `online`, `offline`, `busy`, `away` |
| `presencePulse` | `bool` | `false` | Animación pulse en online |

## Prioridad de render

1. `src` → Imagen
2. `name` → Iniciales auto-generadas
3. `icon` → Icono Lucide fallback

## Avatar Group

```blade
<x-kore::avatar-group>
    <x-kore::avatar src="/user1.jpg" name="User 1" />
    <x-kore::avatar src="/user2.jpg" name="User 2" />
    <x-kore::avatar name="User 3" />
    <x-slot:overflow>
        <x-kore::avatar name="+5" />
    </x-slot:overflow>
</x-kore::avatar-group>
```

## Presencia

```blade
<x-kore::avatar name="Juan" presence="online" />
<x-kore::avatar name="María" presence="busy" />
<x-kore::avatar name="Pedro" presence="away" />
<x-kore::avatar name="Ana" presence="offline" />
```

## Accesibilidad

- **La presencia se dice con palabras**, no solo con color: cada estado lleva su texto en un `sr-only` (`kore-ui.ui.translations.presence_*`). Para quien no distingue el verde del rojo, «en línea» y «ocupado» se veían idénticos.
- Las iniciales usan el token `text-kore-{color}-text`, calibrado también para el tinte al veinte por ciento que usan. Medido antes: las cinco combinaciones fallaban, de 2,67 a 3,52.
- El pulso de `presencePulse` se apaga con `prefers-reduced-motion`: es decoración, el punto de color sigue ahí.
