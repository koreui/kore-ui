# Radio Group

Envuelve varios [`radio`](radio.md) con etiqueta, ayuda, error y maquetación, y
los presenta como **un solo campo** ante quien usa un lector de pantalla.

## Uso básico

```blade
<x-kore::radio-group label="Elige tu plan">
    <x-kore::radio wire:model="plan" value="gratis" label="Gratis" description="0 €/mes" />
    <x-kore::radio wire:model="plan" value="pro" label="Pro" description="29 €/mes" />
    <x-kore::radio wire:model="plan" value="empresa" label="Empresa" description="Habla con nosotros" />
</x-kore::radio-group>
```

Fíjate en dónde va el `wire:model`: en **cada opción**, no en el grupo. El grupo
no ata ningún valor; lo suyo es el nombre, la maquetación y el error.

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta del grupo |
| `hint` | `string\|null` | `null` | Texto de ayuda |
| `inline` | `bool` | `false` | En línea en vez de en columna |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `name` | `string\|null` | `null` | Nombre con el que buscar el error en el bag |
| `required` | `bool` | `false` | Obligatorio, con asterisco |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## En línea

```blade
<x-kore::radio-group label="Vista" inline>
    <x-kore::radio wire:model="vista" value="rejilla" label="Rejilla" />
    <x-kore::radio wire:model="vista" value="lista" label="Lista" />
</x-kore::radio-group>
```

Cambia `space-y-2` por `flex flex-wrap gap-4`. El `flex-wrap` importa en un móvil
estrecho: sin él, cuatro opciones en línea sacan la página de su ancho.

## De dónde sale el error

De dos sitios, en este orden:

1. El `error` que se escriba en la etiqueta.
2. El bag `$errors` de Laravel, buscando por `name`. Si no hay `name`, se deduce
   del `wire:model` **del grupo** — así que para que el error automático
   funcione, o pones `name`, o pones el `wire:model` también en el grupo:

```blade
{{-- El wire:model del grupo aquí solo sirve para encontrar el error --}}
<x-kore::radio-group label="Plan" wire:model="plan">
    <x-kore::radio wire:model="plan" value="pro" label="Pro" />
</x-kore::radio-group>
```

## Accesibilidad

El grupo es un `role="radiogroup"` **con nombre**: se lo da su propia etiqueta
por `aria-labelledby`, y el grupo lleva el `id` al que esa etiqueta apunta. Con
error, además, `aria-invalid` y un `aria-describedby` que apunta al mensaje.

No siempre fue así, y el caso roto era justo el del ejemplo de arriba: con el
`wire:model` en cada opción, el grupo se queda sin `name`; sin `name` no se
generaba ningún `id`; y sin `id`, la etiqueta apuntaba a un elemento que no
existía —etiqueta huérfana— y el `radiogroup` se anunciaba anónimo.

Su etiqueta no usa `for`, y no es un descuido: `<label for>` solo vale contra un
control de formulario, y apuntarlo a un `role="radiogroup"` deja otra vez la
etiqueta colgando. Es para lo que existe `:labelable="false"` en
[`field`](field.md).

## Atributos

Lo que se escriba en la etiqueta llega al `<div>` del `radiogroup` —antes se
filtraba todo menos `class`—, salvo el `id`, que ya lo lleva el grupo, y el
`wire:model`, que aquí solo sirve para localizar el error.
