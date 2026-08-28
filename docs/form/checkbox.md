# Checkbox

Casilla con estilo propio: descripción, tamaños, etiqueta a un lado o al otro y
estado indeterminado.

## Uso básico

```blade
<x-kore::checkbox wire:model="terminos" label="Acepto las condiciones" />

<x-kore::checkbox wire:model="avisos" label="Avisos por correo"
    description="Un resumen semanal de tu cuenta" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `description` | `string\|null` | `null` | Texto secundario bajo la etiqueta |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `labelPosition` | `string` | `right` | Lado de la etiqueta: `left`, `right` |
| `indeterminate` | `bool` | `false` | Tercer estado, para el «seleccionar todo» |
| `disabled` | `bool` | `false` | Desactivada |
| `name` | `string\|null` | `null` | Nombre. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## Indeterminado

Para el patrón «seleccionar todo» cuando hay hijos marcados y otros no:

```blade
<x-kore::checkbox wire:model="todos" label="Seleccionar todo" indeterminate />
```

Se pone con `x-init` sobre la propiedad `indeterminate` del control, que no es un
atributo de HTML y solo existe en el DOM.

**Y se ve.** `appearance-none` quita el guion que pinta el navegador por su
cuenta, y durante mucho tiempo solo había estilos para `checked`: una casilla en
«mixto» era idéntica a una sin marcar. El árbol de accesibilidad sí la anunciaba
como *mixed*, pero a la vista no había nada que la distinguiera.

## Posición de la etiqueta

```blade
<x-kore::checkbox label="No cerrar sesión" label-position="left" />
```

## Cómo se pinta

La casilla es `appearance-none` con la palomita y el guion como imagen de fondo
SVG en línea, y toma `kore-primary` al marcarse.

El SVG va con los espacios en `%20` y las comillas en `%27` a propósito, no por
gusto: Tailwind v4 extrae las clases partiendo el texto del archivo por espacios
en blanco, así que un valor arbitrario con espacios dentro —y un `viewBox` los
tiene— se corta en el primero y la utilidad **no llega a generarse**. Sin error
de compilación y sin nada en el CSS: la casilla marcada se quedaba en un cuadrado
de color liso, sin palomita. Hay un cepo en `tests/Ui/ClasesArbitrariasTest.php`
que barre las vistas buscando el mismo patrón.

## Accesibilidad

- El `id` es estable entre renders (`IdContext`), que es lo que mantiene el
  `label[for]` apuntando a su casilla después del primer re-render.
- La `description` se enlaza con `aria-describedby`; si hay error, se enlaza el
  error, que se pinta en un `role="alert"`.
- Los atributos escritos en la etiqueta llegan al `<input type="checkbox">`.
