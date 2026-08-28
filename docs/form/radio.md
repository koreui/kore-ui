# Radio

Un botón de opción. Para varios, ver [radio-group](radio-group.md).

## Uso básico

```blade
<x-kore::radio wire:model="plan" value="basico" label="Básico" name="plan" />
<x-kore::radio wire:model="plan" value="pro" label="Pro" name="plan" />
```

## Props de `radio`

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `description` | `string\|null` | `null` | Texto secundario |
| `value` | `string\|null` | `null` | Valor de la opción |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `disabled` | `bool` | `false` | Desactivado |
| `name` | `string\|null` | `null` | Nombre del grupo. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

El `id` de cada opción lleva su `value` pegado (`kore-plan-pro`), que es lo que
mantiene distintas a dos opciones del mismo grupo.

## Grupo

Las opciones sueltas funcionan, pero lo normal es envolverlas en un
[`radio-group`](radio-group.md), que les pone etiqueta, ayuda y error, y las
presenta como **un solo campo** ante un lector de pantalla:

```blade
<x-kore::radio-group label="Elige tu plan">
    <x-kore::radio wire:model="plan" value="gratis" label="Gratis" description="0 €/mes" />
    <x-kore::radio wire:model="plan" value="pro" label="Pro" description="29 €/mes" />
</x-kore::radio-group>
```

Sus props, la maquetación en línea y el porqué de su `aria-labelledby` están en
[radio-group](radio-group.md).

## Accesibilidad

- El `id` de cada opción es estable entre renders (`IdContext`) y lleva su
  `value` pegado, que es lo que mantiene el `label[for]` apuntando a la opción
  correcta.
- La `description` se enlaza con `aria-describedby`; si hay error, se enlaza el
  error, que se pinta en un `role="alert"`.
- Los atributos escritos en la etiqueta llegan al `<input type="radio">`.
