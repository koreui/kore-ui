# Alert

Componente de alerta con soporte para tipos, variantes, iconos automáticos, cierre y auto-dismiss.

## Uso básico

```blade
<x-kore::alert title="Éxito" description="Operación completada." type="success" />
<x-kore::alert title="Error" type="destructive">Algo salió mal.</x-kore::alert>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `title` | `string\|null` | `null` | Título de la alerta |
| `description` | `string\|null` | `null` | Descripción textual |
| `type` | `string` | `info` | Tipo: `info`, `success`, `warning`, `destructive` |
| `icon` | `string\|null` | auto | Icono Lucide (auto-resuelto por tipo) |
| `variant` | `string` | `config(soft)` | Variante: `solid`, `soft`, `outline` |
| `closeable` | `bool` | `false` | Muestra botón de cierre |
| `timeout` | `int\|null` | `null` | Auto-dismiss en segundos |
| `showIcon` | `bool` | `true` | Mostrar/ocultar icono |

## Tipos

```blade
<x-kore::alert type="info" title="Información" description="Nota informativa." />
<x-kore::alert type="success" title="Éxito" description="Todo bien." />
<x-kore::alert type="warning" title="Advertencia" description="Cuidado." />
<x-kore::alert type="destructive" title="Error" description="Falló." />
```

## Closeable y auto-dismiss

```blade
<x-kore::alert title="Cerrable" closeable />
<x-kore::alert title="Auto-dismiss" :timeout="5" />
```

## Con acción

```blade
<x-kore::alert title="Actualización disponible" type="info">
    <x-slot:action>
        <x-kore::button label="Actualizar" size="sm" />
    </x-slot:action>
</x-kore::alert>
```

## Accesibilidad

### Cuándo interrumpe al lector

`role="alert"` es una región *assertive*: interrumpe lo que el lector esté diciendo. Está bien para un aviso que aparece de pronto, y mal para uno que ya estaba en la página al cargar — medido, doce alertas estáticas se anunciaban de golpe al abrirla.

El rol se pone solo cuando la alerta es dinámica de verdad (lleva `timeout`). La prop `live` lo decide a mano:

```blade
<x-kore::alert title="Guardado" live="polite" />   {{-- role="status" --}}
<x-kore::alert title="Error" live="assertive" />   {{-- role="alert" --}}
<x-kore::alert title="Nota" live="off" />          {{-- sin rol --}}
```

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `live` | `string\|null` | `assertive` con `timeout`, `off` sin él | `assertive`, `polite` u `off` |

### Color y tamaño

- Las variantes `soft` y `outline` usan el token `text-kore-{color}-text`. Ver la nota de `badge`.
- La descripción ya no lleva `opacity-90`: hundía el contraste de un texto que en once de doce combinaciones ya iba justo.
- El botón de cerrar mide 24×24 (WCAG 2.2). Medía 20 de ancho.
