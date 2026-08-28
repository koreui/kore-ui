# Float Label

Etiqueta flotante, en tres variantes. Envuelve a cualquier campo de la librería.

## Uso básico

```blade
<x-kore::float-label label="Nombre completo">
    <x-kore::input wire:model="nombre" placeholder=" " />
</x-kore::float-label>
```

**Dos detalles que no se pueden saltar**: el campo de dentro necesita un
`placeholder=" "` (un espacio) para que el navegador lo dé por «no vacío» solo
cuando de verdad tiene contenido; y no se le pasa `label`, que es lo que aporta
el envoltorio.

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | El texto de la etiqueta flotante |
| `variant` | `string` | `over` | Variante: `over`, `in`, `on` |

## Variantes

### Over (por defecto)

La etiqueta arranca centrada dentro del campo, como si fuera el marcador de
posición. Al enfocar —o si ya hay contenido— sube hasta el borde superior y se
recorta el fondo a su alrededor.

```blade
<x-kore::float-label label="Correo">
    <x-kore::input wire:model="email" type="email" placeholder=" " />
</x-kore::float-label>
```

```
En reposo:                 Activa:
┌─────────────────┐        ──Correo───────────
│  Correo         │   →    │ ana@ejemplo.test │
└─────────────────┘        └──────────────────┘
```

### In

La etiqueta arranca dentro del campo, arriba. Al enfocar encoge pero se queda
dentro, y el campo lleva más relleno por arriba para que quepan la etiqueta y el
texto debajo.

```blade
<x-kore::float-label label="Usuario" variant="in">
    <x-kore::input wire:model="usuario" placeholder=" " />
</x-kore::float-label>
```

```
En reposo:                 Activa:
┌─────────────────┐        ┌─Usuario──────────┐
│ Usuario         │   →    │                  │
│                 │        │ anagarcia        │
└─────────────────┘        └──────────────────┘
```

### On

La etiqueta está siempre sobre el borde, con el fondo recortado. No se mueve
nunca: solo cambia de color al enfocar.

```blade
<x-kore::float-label label="Nombre" variant="on">
    <x-kore::input wire:model="nombre" placeholder=" " />
</x-kore::float-label>
```

```
Siempre:
──Nombre──────────
│ Ana             │
└─────────────────┘
```

## Con qué funciona

Con cualquier componente que acabe pintando un `<input>`, un `<textarea>` o un
`<select>`:

```blade
<x-kore::float-label label="Contraseña">
    <x-kore::password wire:model="password" placeholder=" " />
</x-kore::float-label>

<x-kore::float-label label="Biografía" variant="in">
    <x-kore::textarea wire:model="biografia" placeholder=" " />
</x-kore::float-label>
```

## Cómo funciona

Alpine escucha `focusin`, `focusout` e `input` sobre el envoltorio, y de ahí
salen `focused` y `filled`, que colocan la etiqueta con `x-bind:class`. Un
`x-init` mira además si el campo ya trae valor al cargar la página, que es el
caso de un formulario de edición.

Y **enlaza la etiqueta con el control**, que es lo único que este componente
tiene que hacer: busca el primer `input` visible, `textarea` o `select` de dentro
y le apunta el `for`. El enlace se hace en tiempo de ejecución porque el control
lo pone quien usa el componente, así que su `id` no se puede escribir en la
vista; si el campo no trae ninguno, se le pone uno.

Sin ese enlace —y no lo tenía: su `<label>` no llevaba `for` ni envolvía al
control— el campo se anunciaba **sin nombre**, que es justo lo contrario de lo
que promete un float-label.
