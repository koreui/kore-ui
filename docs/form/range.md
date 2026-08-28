# Range

Deslizador con un asa o con dos, mínimo, máximo y paso propios, valor a la vista
y `wire:model`.

## Uso básico

```blade
<x-kore::range wire:model="volumen" label="Volumen" />
```

## Modo doble

```blade
<x-kore::range wire:model="precio" label="Precio" range :min="0" :max="200" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `hint` | `string\|null` | `null` | Texto de ayuda debajo |
| `name` | `string\|null` | `null` | Nombre. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `min` | `number` | `0` | Valor mínimo |
| `max` | `number` | `100` | Valor máximo |
| `step` | `number` | `1` | Salto entre valores |
| `range` | `bool` | `false` | Dos asas en vez de una |
| `showValue` | `bool` | `false` | Enseña el valor sobre el deslizador |
| `showLabels` | `bool` | `false` | Enseña el mínimo y el máximo debajo |
| `disabled` | `bool` | `false` | Desactivado |
| `required` | `bool` | `false` | Obligatorio, con asterisco |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## wire:model

- **Un asa**: un número (`50`)
- **Dos asas**: un array de dos (`[20, 80]`)

```php
// En el componente Livewire
public int $volumen = 50;
public array $precio = [20, 80];
```

## Tamaños

La barra y el asa escalan con `size`:

| Tamaño | Barra | Asa |
|--------|-------|-----|
| `sm` | 4 px | 14 px |
| `md` | 8 px | 18 px |
| `lg` | 12 px | 22 px |

## Valor y extremos

```blade
<x-kore::range show-value show-labels :min="0" :max="100" />
```

## Accesibilidad

Los dos deslizadores de un `range` doble tienen **nombres distintos**: la
etiqueta del campo más «mínimo» y «máximo» (`kore-ui.form.translations.range_min`
y `range_max`). Con `label="Precio"` se anuncian «Precio — mínimo» y «Precio —
máximo».

Antes eran dos controles idénticos y sin nombre, y ninguno de los dos era el que
apuntaba la etiqueta del campo: quien navegaba por voz o con lector no tenía
forma de saber cuál estaba tocando.

> Ojo con los nombres parecidos: `range_from` y `range_to` («Desde» / «Hasta») no
> son de este componente, sino del filtro de rango numérico del DataTable.

## Dónde aterrizan los atributos

Depende del modo, porque el HTML no es el mismo:

- **Un asa**: hay un solo control, y ahí se mergean —como en `input` o
  `textarea`—.
- **Dos asas**: hay dos deslizadores y un input oculto, así que se vuelcan en la
  **raíz** del componente, como en el resto de compuestos. Se quedan fuera el
  `id`, que lo lleva el input oculto, y el `wire:model`, que vive en él.

En modo doble desaparecían sin dejar rastro hasta la 2.1: el barrido que arregló
esto en los demás componentes dio a `range` por bueno mirando solo su modo
simple.
