# Toolbar

Contenedor flex para agrupar acciones y controles en una barra horizontal.

## Uso básico

```blade
<x-kore::toolbar>
    <x-slot:start>
        <x-kore::button label="Guardar" />
    </x-slot:start>

    <x-slot:end>
        <x-kore::button label="Cancelar" variant="outline" />
    </x-slot:end>
</x-kore::toolbar>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `variant` | `string` | `default` | Variante visual: `default`, `bordered` |
| `justify` | `string` | `between` | Justificación: `between`, `start`, `end`, `center` |
| `role` | `string\|false` | `toolbar` | Rol ARIA del contenedor. Con `:role="false"` no se emite ninguno |

## Slots

- `start`: Contenido alineado al inicio
- `default`: Contenido central
- `end`: Contenido alineado al final

## Con borde

```blade
<x-kore::toolbar variant="bordered">
    <x-slot:start>
        <x-kore::button icon="bold" variant="ghost" size="sm" />
        <x-kore::button icon="italic" variant="ghost" size="sm" />
        <x-kore::button icon="underline" variant="ghost" size="sm" />
    </x-slot:start>

    <x-slot:end>
        <x-kore::button icon="settings" variant="ghost" size="sm" />
    </x-slot:end>
</x-kore::toolbar>
```

## Justificación

```blade
{{-- Todo al centro --}}
<x-kore::toolbar justify="center">
    <x-kore::button label="Acción 1" />
    <x-kore::button label="Acción 2" />
</x-kore::toolbar>

{{-- Todo al final --}}
<x-kore::toolbar justify="end">
    <x-kore::button label="Cancelar" variant="outline" />
    <x-kore::button label="Guardar" />
</x-kore::toolbar>
```

## Tres secciones

```blade
<x-kore::toolbar>
    <x-slot:start>
        <x-kore::button icon="arrow-left" variant="ghost" />
    </x-slot:start>

    <span class="font-semibold">Editor de texto</span>

    <x-slot:end>
        <x-kore::button label="Publicar" />
    </x-slot:end>
</x-kore::toolbar>
```

## Cuándo quitar el rol

`role="toolbar"` le promete a un lector de pantalla un widget que se recorre con
las flechas, no con el tabulador. Si la barra es solo una fila de botones
sueltos, la promesa es falsa y conviene retirarla —así lo hace la navbar del
shell—:

```blade
<x-kore::toolbar :role="false">
    <x-kore::button label="Guardar" />
</x-kore::toolbar>
```

Tiene que ser `false`, no `null`: `@props` resuelve con `??`, así que un `null`
explícito cae en el valor por defecto igual que si no hubieras escrito nada, y
el `role="toolbar"` seguiría ahí. Cualquier otra cadena se emite tal cual, por
si la barra es en realidad un `group`:

```blade
<x-kore::toolbar role="group" aria-label="Formato de texto">…</x-kore::toolbar>
```
