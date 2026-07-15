# Result

Bloque de estado del resultado de una operación o una página: éxito, error, aviso, o páginas 404/403/500. Cada estado trae su icono y color automáticos. Es **distinto** del `empty-state` (que representa "sin datos"): Result comunica el desenlace de una acción o el estado de una ruta.

## Uso básico

```blade
<x-kore::result
    status="success"
    title="¡Pago recibido!"
    description="Te enviamos el recibo por correo."
>
    <x-slot:action>
        <x-kore::button label="Ir al inicio" href="/" />
    </x-slot:action>
</x-kore::result>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `status` | `string` | `info` | `success`, `info`, `warning`, `error`, `404`, `403`, `500` |
| `title` | `string\|null` | `null` | Título principal |
| `description` | `string\|null` | `null` | Texto secundario |
| `icon` | `string\|null` | `null` | Icono Lucide para sobrescribir el del estado |

## Slots

| Slot | Descripción |
|------|-------------|
| `action` | Botones de acción (centrados bajo el texto) |
| default | Contenido extra entre la descripción y las acciones |

## Iconos y colores por estado

| `status` | Icono | Color |
|----------|-------|-------|
| `success` | `circle-check` | success |
| `info` | `info` | info |
| `warning` | `triangle-alert` | warning |
| `error` | `circle-x` | destructive |
| `404` | `search-x` | muted |
| `403` | `lock` | warning |
| `500` | `server-crash` | destructive |

## Páginas de error

```blade
{{-- 404 --}}
<x-kore::result status="404" title="Página no encontrada"
    description="La página que buscas no existe o fue movida.">
    <x-slot:action>
        <x-kore::button label="Volver" variant="outline" href="/" />
    </x-slot:action>
</x-kore::result>

{{-- 403 --}}
<x-kore::result status="403" title="Sin permiso"
    description="No tienes acceso a este recurso." />
```
