# Maskable

Campo con máscara: formato por tokens, máscaras dinámicas, mayúsculas
automáticas, pegado y `wire:model`.

## Uso básico

```blade
<x-kore::maskable wire:model="telefono" label="Teléfono" mask="(##) ####-####" />
```

## Tokens de la máscara

| Token | Acepta | Ejemplo |
|-------|--------|---------|
| `#` | Un dígito (0-9) | Teléfono: `(##) ####-####` |
| `A` | Una letra (a-z, A-Z) | |
| `*` | Cualquier carácter | |
| `!` | Una letra, y la pone en mayúscula | NIF: `########!` |

Todo lo demás es un carácter literal: paréntesis, guiones, espacios.

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `hint` | `string\|null` | `null` | Texto de ayuda debajo |
| `name` | `string\|null` | `null` | Nombre. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `mask` | `string\|array\|null` | `null` | La máscara. Un array para las dinámicas |
| `emitFormatted` | `bool\|null` | config `false` | Manda a Livewire el valor formateado en vez del crudo |
| `slotChar` | `string\|null` | config `_` | Carácter de hueco del marcador de posición |
| `autoClear` | `bool\|null` | config `false` | Vacía el campo al salir si está a medias |
| `icon` | `string\|null` | `null` | Icono Lucide a la izquierda |
| `iconRight` | `string\|null` | `null` | Icono Lucide a la derecha |
| `clearable` | `bool` | `false` | Aspa para vaciar el campo |
| `disabled` | `bool` | `false` | Desactivado |
| `readonly` | `bool` | `false` | Se lee y se envía, pero no se edita |
| `required` | `bool` | `false` | Obligatorio, con asterisco |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## wire:model

Por defecto Livewire recibe el **valor crudo**: solo dígitos y letras, sin los
literales de la máscara.

```php
// El usuario escribe: (55) 1234-5678
// Livewire recibe:    "5512345678"
public string $telefono = '';
```

Con `emit-formatted`, recibe el valor tal y como se ve:

```blade
<x-kore::maskable wire:model="telefono" mask="(##) ####-####" emit-formatted />
{{-- Livewire recibe: "(55) 1234-5678" --}}
```

La diferencia no es solo de forma: **cambia dónde vive el `wire:model`**. Con el
valor crudo hay un input oculto que lo lleva, y el campo visible es solo la
presentación; con `emit-formatted` el `wire:model` va en el campo visible y no
hay input oculto.

## Máscaras dinámicas

Un array, para lo que puede tener dos longitudes:

```blade
<x-kore::maskable
    :mask="['(##) ####-####', '(##) #####-####']"
    label="Teléfono"
/>
```

Se elige sola la que mejor encaje con lo escrito.

## El marcador de posición

Sale de la máscara, usando el carácter de hueco:

- Máscara `(##) ####-####` → marcador `(__) ____-____`
- Se sustituye escribiendo un `placeholder` propio

## Configuración

```php
// config/kore-ui.php
'form' => [
    'maskable' => [
        'slot_char' => '_',
        'auto_clear' => false,
        'emit_formatted' => false,
    ],
],
```

Los tres se ignoraban hasta la 2.1 —las props traían su valor escrito, así que el
`??` de la vista no llegaba a mirar la configuración—, y lo que se escriba en la
etiqueta sigue ganando a lo que diga aquí.

## Accesibilidad

- El botón de limpiar se anuncia «Limpiar»
  (`kore-ui.form.translations.clear`). No tenía nombre hasta la 2.1: era un aspa y
  nada más.
- El `id` es estable entre renders (`IdContext`), y el `hint` y el error se
  enlazan con `aria-describedby`.
- Es un componente **compuesto**: lo que se escriba en la etiqueta se vuelca en
  su raíz, no en el campo visible. Se quedan fuera el `id` —que ya lleva el
  control— y el `wire:model`, que vive en el input oculto.
