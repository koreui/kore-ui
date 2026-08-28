# Textarea

Campo de varias líneas, con crecimiento automático y contador de caracteres.

## Uso básico

```blade
<x-kore::textarea wire:model="biografia" label="Biografía"
    placeholder="Cuéntanos algo de ti…" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `hint` | `string\|null` | `null` | Texto de ayuda debajo |
| `name` | `string\|null` | `null` | Nombre. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `rows` | `int\|null` | config `4` | Filas iniciales. Sale de `kore-ui.form.textarea.rows` |
| `autoResize` | `bool` | `false` | Crece con el contenido |
| `maxLength` | `int\|null` | `null` | Límite de caracteres, con contador a la vista |
| `disabled` | `bool` | `false` | Desactivado |
| `readonly` | `bool` | `false` | Se lee y se envía, pero no se edita |
| `required` | `bool` | `false` | Obligatorio, con asterisco |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## Crecer con el contenido

```blade
<x-kore::textarea wire:model.live="notas" label="Notas" auto-resize rows="2" />
```

El alto se lleva en una propiedad de Alpine y se aplica con `x-bind:style`, no
escribiendo `style.height` a mano. La diferencia importa: lo que el JavaScript
escribe directamente en el DOM y el servidor no emite, el morph de Livewire se lo
lleva por delante en la siguiente ida y vuelta — el campo volvería de golpe a su
alto inicial.

## Contador

Enseña `actual/máximo` debajo del campo. Con un contador puesto, el `hint` se va
a la izquierda para dejarle sitio:

```blade
<x-kore::textarea label="Titular" :max-length="280" hint="Que sea corto" />
```

## Los dos a la vez

```blade
<x-kore::textarea label="Descripción" auto-resize :max-length="500" rows="3" />
```

## Accesibilidad

- El `id` es estable entre renders (`IdContext`).
- El `hint` y el error se enlazan con `aria-describedby`, y el `id` del `hint` es
  el mismo lo pinte el campo o lo pinte el bloque del contador.
- Un campo con error lleva `aria-invalid="true"`.
- Los atributos escritos en la etiqueta llegan al `<textarea>`.
