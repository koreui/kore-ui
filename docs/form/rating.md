# Rating

Valoración con estrellas: medias estrellas, modo de solo lectura y `wire:model`.

## Uso básico

```blade
<x-kore::rating wire:model="nota" label="Valoración" />
```

## Media estrella

```blade
<x-kore::rating wire:model="nota" label="Valoración" allow-half />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `hint` | `string\|null` | `null` | Texto de ayuda debajo |
| `name` | `string\|null` | `null` | Nombre. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `stars` | `int` | `5` | Cuántas estrellas |
| `allowHalf` | `bool` | `false` | Permite medias estrellas |
| `readonly` | `bool` | `false` | Solo se ve, no se toca |
| `clearable` | `bool` | `true` | Pulsar la estrella ya elegida vuelve a cero |
| `disabled` | `bool` | `false` | Desactivado |
| `required` | `bool` | `false` | Obligatorio, con asterisco |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## wire:model

El valor es un número: `0` (sin valoración), `1`-`N` para estrellas enteras, o
`0.5`, `1.5`… para las medias.

```php
// En el componente Livewire
public int $nota = 0;
public float $notaMedia = 0;
```

## Tamaños

`size` cambia la estrella, no el botón que la envuelve:

```blade
<x-kore::rating size="sm" />  {{-- estrella de 16 px --}}
<x-kore::rating />            {{-- estrella de 20 px (por defecto) --}}
<x-kore::rating size="lg" />  {{-- estrella de 28 px --}}
```

El **objetivo táctil** es siempre de al menos 24×24 px, el mínimo de la WCAG 2.2,
sea cual sea el tamaño de la estrella. Eran 20×20 y 16×16 en el tamaño pequeño,
el último objetivo pequeño que le quedaba a la librería.

Como consecuencia, **un rating de muchas estrellas es más ancho que antes** y el
conjunto baja de línea si no cabe (`flex-wrap`): diez estrellas de 24 px ocupan
240 px y no caben en un móvil de 390 con sus márgenes. Que bajen de línea es
feo; arrastrar la página de lado, peor.

## Solo lectura

```blade
<x-kore::rating readonly allow-half value="3.5" />
```

## Otro número de estrellas

```blade
<x-kore::rating :stars="10" label="Nota (sobre 10)" />
```

## Accesibilidad

- **Interactivo**: `role="radiogroup"`, y cada estrella es un `role="radio"` con
  su `aria-checked` y su nombre («3 de 5 estrellas»).
- **Solo lectura o desactivado**: el conjunto es un `role="img"` con nombre, y
  las estrellas salen del recorrido de tabulación (`tabindex="-1"`) y del árbol
  de accesibilidad (`aria-hidden`). Eran botones tabulables que no hacían nada,
  y encima sin nombre: `aria-label` solo se emitía en modo interactivo.
- Los dos textos salen de `kore-ui.form.translations`: `rating` («Valoración»)
  para el conjunto, y `rating_stars` para cada estrella, que es una **plantilla
  entera** con dos marcadores:

  ```php
  'rating_stars' => ':n of :total stars',   // «3 of 5 stars»
  ```

  Entera y no por trozos porque el «de» que separaba los números estaba escrito
  en la vista, y en otro idioma ni siquiera va en el mismo sitio.
- Lo que se escriba en la etiqueta se vuelca en la raíz: es un compuesto.
