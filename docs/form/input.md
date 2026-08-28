# Input

Campo de texto con icono, adornos de prefijo y sufijo, botón de limpiar y
`wire:model`.

## Uso básico

```blade
<x-kore::input wire:model="nombre" label="Nombre" placeholder="Tu nombre" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta sobre el campo |
| `hint` | `string\|null` | `null` | Texto de ayuda debajo. Se oculta mientras hay error |
| `name` | `string\|null` | `null` | Nombre del campo. Si falta se deduce del `wire:model`, y con él se busca el error |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `type` | `string` | `text` | Tipo del `<input>` |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `icon` | `string\|null` | `null` | Icono Lucide a la izquierda |
| `iconRight` | `string\|null` | `null` | Icono Lucide a la derecha |
| `prefix` | `string\|null` | `null` | Adorno de texto a la izquierda |
| `suffix` | `string\|null` | `null` | Adorno de texto a la derecha |
| `clearable` | `bool` | `false` | Aspa para vaciar el campo cuando tiene contenido |
| `disabled` | `bool` | `false` | Desactivado |
| `readonly` | `bool` | `false` | Se lee y se envía, pero no se edita |
| `required` | `bool` | `false` | Obligatorio, con asterisco en la etiqueta |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## Iconos

```blade
<x-kore::input label="Buscar" icon="search" placeholder="Buscar…" />
<x-kore::input label="Correo" icon="mail" icon-right="check" />
```

Los iconos son de [Lucide](https://lucide.dev/), vía `blade-lucide-icons`. El
nombre va sin el prefijo `lucide-`.

## Prefijo y sufijo

Adornos de texto, en un segmento propio con fondo atenuado:

```blade
<x-kore::input label="Web" prefix="https://" suffix=".com" />
<x-kore::input label="Precio" prefix="€" suffix="EUR" />
```

Van en un `flex`, así que ocupan su anchura natural sea cual sea el texto. Con
prefijo o sufijo el borde pasa al contenedor y el `<input>` se queda sin el suyo:
el foco se dibuja alrededor del conjunto.

## Limpiar

```blade
<x-kore::input wire:model="busqueda" label="Buscar" icon="search" clearable />
```

El aspa aparece cuando el campo tiene contenido. Al pulsarla vacía el valor,
dispara un evento `input` —para que `wire:model` se entere— y devuelve el foco al
campo. Su nombre accesible sale de `kore-ui.form.translations.clear`
(«Limpiar»): es un botón que solo lleva un icono, y sin nombre un lector de
pantalla anuncia «botón» y nada más.

## Estados

```blade
<x-kore::input label="Desactivado" disabled />
<x-kore::input label="Solo lectura" value="No se toca" readonly />
<x-kore::input label="Obligatorio" required />
<x-kore::input label="Con error" error="Este campo es obligatorio" />
```

## Dónde aterrizan los atributos

Todo lo que se escriba en la etiqueta y no sea una prop llega al `<input>`
nativo: es un componente que envuelve un control, no uno compuesto. La diferencia
importa y está contada en [«Atributos, `id` y morph»](getting-started.md).

```blade
<x-kore::input wire:model.live="email" label="Correo" type="email"
    placeholder="tu@ejemplo.test" autocomplete="email" />
```

## Accesibilidad

- El `id` del campo es **estable entre renders** —lo da `IdContext`, no un
  `uniqid()`—, que es lo que mantiene en pie el `label[for]` y evita que Livewire
  sustituya el nodo en cada ida y vuelta al servidor.
- El `hint` y el mensaje de error se enlazan con el control por
  `aria-describedby`, y un campo con error lleva `aria-invalid="true"`.
- Un `name` con corchetes se normaliza al derivar el `id`: `items[0]` da
  `kore-items-0` y no `kore-items[0]`, que obliga a escapar en cualquier selector.
