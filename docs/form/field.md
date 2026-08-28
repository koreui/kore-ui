# Field

El envoltorio que ponen todos los campos de la librería: la etiqueta encima, el
error o la ayuda debajo, y los `id` que enlazan las tres cosas.

No suele hacer falta usarlo a mano —`input`, `select`, `datepicker` y compañía ya
lo llevan dentro—, pero está disponible para envolver un control propio con el
mismo aspecto y los mismos enlaces de accesibilidad que el resto del formulario.

## Uso básico

```blade
<x-kore::field label="Color favorito" field-id="mi-campo" hint="El que más uses">
    <input id="mi-campo" type="text" class="…" />
</x-kore::field>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | La etiqueta. Sin ella no se pinta ningún `<label>` |
| `hint` | `string\|null` | `null` | Texto de ayuda. **Solo se ve si no hay error** |
| `hasError` | `bool` | `false` | Si el campo está en error |
| `errorMessage` | `string\|null` | `null` | El mensaje. Hacen falta los dos para que se pinte |
| `fieldId` | `string\|null` | `null` | El `id` del control. De él salen los tres `id` de abajo |
| `required` | `bool` | `false` | Añade el asterisco a la etiqueta |
| `labelable` | `bool` | `true` | Si lo que envuelve es un control etiquetable. Ver abajo |

## Los tres `id`

De `fieldId` salen tres identificadores, y los componentes de la librería cuentan
con ellos para enlazar el control con lo que lo describe:

| Elemento | `id` | Para qué |
|----------|------|----------|
| La etiqueta | `{fieldId}-label` | El `aria-labelledby` de los contenedores |
| El error | `{fieldId}-error` | El `aria-describedby` del control |
| La ayuda | `{fieldId}-hint` | Igual, cuando no hay error |

Sin `fieldId` no se emite ninguno, y el `<label>` se queda sin `for`.

## El error tapa la ayuda

Nunca se ven los dos a la vez: si hay error, la ayuda desaparece. Es a propósito
—dos textos bajo el mismo campo compiten— y por eso el `aria-describedby` de los
componentes apunta a uno o a otro, no a los dos.

El error se pinta con `role="alert"`, así que un lector de pantalla lo anuncia en
cuanto aparece.

## `labelable`: cuando lo de dentro no es un control

`<label for>` solo vale contra un control de formulario. Si lo que se envuelve es
un contenedor —un `role="radiogroup"`, un calendario empotrado—, el `for` apunta
a algo que no es etiquetable: la etiqueta se queda huérfana y el grupo, sin
nombre.

En ese caso se pasa `:labelable="false"` y se nombra el contenedor desde dentro
con `aria-labelledby="{fieldId}-label"`:

```blade
<x-kore::field label="Elige tu plan" :field-id="$fieldId" :labelable="false">
    <div role="radiogroup" id="{{ $fieldId }}" aria-labelledby="{{ $fieldId }}-label">
        …
    </div>
</x-kore::field>
```

Es lo que hacen [`radio-group`](radio-group.md) y el calendario empotrado del
[`datepicker`](datepicker.md).

## Qué NO hace

- **No resuelve el error por su cuenta.** No mira el bag `$errors`: recibe
  `hasError` y `errorMessage` ya masticados. Quien decide es cada componente, que
  es donde vive `showError`.
- **No genera el `id`.** Se lo dan. Los componentes lo sacan de
  `KoreUi\Core\Support\IdContext`, que da ids estables entre renders.
- **No pasa atributos al control.** Del bag solo usa `class`, y la suma a la suya
  (`kore-field`). Todo lo demás se queda fuera: lo que va al control lo pone el
  componente que lo pinta.
