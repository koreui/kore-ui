# Password

Campo de contraseña con botón para verla, y un medidor de fuerza opcional.

## Uso básico

```blade
<x-kore::password wire:model="password" label="Contraseña" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta |
| `hint` | `string\|null` | `null` | Texto de ayuda |
| `name` | `string\|null` | `null` | Nombre. Si falta se deduce del `wire:model` |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `size` | `string\|null` | config `md` | Tamaño: `sm`, `md`, `lg`. Sale de `kore-ui.form.size` |
| `icon` | `string\|null` | `null` | Icono Lucide a la izquierda |
| `toggleable` | `bool\|null` | config `true` | Botón del ojo. Sale de `kore-ui.form.password.toggleable` |
| `strength` | `bool\|null` | config `false` | Medidor de fuerza. Sale de `kore-ui.form.password.strength` |
| `minLength` | `int\|null` | config `8` | Longitud mínima que exige el medidor. Sale de `kore-ui.form.password.min_length` |
| `showRules` | `bool` | `true` | Lista de reglas bajo la barra |
| `disabled` | `bool` | `false` | Desactivado |
| `readonly` | `bool` | `false` | Se lee y se envía, pero no se edita |
| `required` | `bool` | `false` | Obligatorio, con asterisco |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## Con icono

```blade
<x-kore::password wire:model="password" label="Contraseña" icon="lock" />
```

## Sin el ojo

```blade
<x-kore::password wire:model="pin" label="PIN" :toggleable="false" />
```

## Medidor de fuerza

Una barra de cuatro tramos y la lista de reglas que faltan:

```blade
<x-kore::password wire:model="password" label="Contraseña nueva" :strength="true" />
```

### Cómo puntúa

Se miden cinco reglas: longitud mínima, mayúscula, minúscula, número y carácter
especial. El nivel sale de **cuántas se cumplen**, no de la longitud:

| Reglas cumplidas | Nivel | Color |
|------------------|-------|-------|
| 0 | — | Atenuado |
| 1 | Débil | Rojo |
| 2 | Regular | Naranja |
| 3 | Buena | Amarillo |
| 4-5 | Fuerte | Verde |

### Sin la lista de reglas

Solo la barra:

```blade
<x-kore::password wire:model="password" label="Contraseña"
    :strength="true" :show-rules="false" />
```

### Otro mínimo

```blade
<x-kore::password wire:model="password" label="Contraseña de administrador"
    :strength="true" :min-length="12" />
```

El mínimo viaja a la regla de longitud: con `12` la lista dice «Al menos 12
caracteres».

### Los textos se traducen

Los nombres de los cuatro niveles y de las cinco reglas salen de
`kore-ui.form.translations`, igual que el resto de la librería:

```php
// config/kore-ui.php
'form' => [
    'translations' => [
        'password_weak'          => 'Weak',
        'password_fair'          => 'Fair',
        'password_good'          => 'Good',
        'password_strong'        => 'Strong',
        'password_rule_length'   => 'At least :min characters',
        'password_rule_uppercase' => 'One uppercase letter',
        // …
    ],
],
```

`:min` se sustituye por el mínimo que tenga el campo. Estos textos vivían dentro
de `resources/js/form/password.js`, en inglés, y eran los únicos de la librería
que no se podían cambiar ni publicando las vistas: hacía falta recompilar el
bundle.

## Cómo está montado

Sin `strength`, el componente es un `x-data="{ show: false }"` en línea. Con
`strength`, monta el plugin `KorePassword`, que recibe el mínimo y los textos.

En los dos casos el `wire:model` va **directamente en el `<input>`**: no hay
input oculto de por medio. El medidor solo lee el valor del campo para puntuarlo.

El tipo del campo alterna entre `password` y `text` con `x-bind:type`, y el icono
entre `lucide-eye` y `lucide-eye-off`.

## Accesibilidad

- El botón del ojo dice qué va a hacer, y cambia con el estado: «Mostrar la
  contraseña» / «Ocultar la contraseña», de
  `kore-ui.form.translations.password_show` y `password_hide`. Decía «Show
  password» / «Hide password» hasta la 2.1: estaba escrito dentro de la expresión
  de Alpine, que es donde no miraba el cepo de textos.
- El `id` es estable entre renders (`IdContext`), y el `hint` y el error se
  enlazan con `aria-describedby`.
