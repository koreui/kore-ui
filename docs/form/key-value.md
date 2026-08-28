# Key-Value

Editor de pares clave-valor dinámicos, ideal para metadata, settings, variables de entorno o cabeceras HTTP. El usuario añade, edita, elimina y (opcionalmente) reordena filas. Se sincroniza con Livewire como un objeto `{clave: valor}`.

## Uso básico

```blade
<x-kore::key-value wire:model="meta" label="Metadatos" />
```

```php
// En el componente Livewire
public array $meta = ['env' => 'production'];
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta del campo |
| `hint` | `string\|null` | `null` | Texto de ayuda |
| `name` | `string\|null` | `null` | Nombre (se deriva de `wire:model` si se omite) |
| `error` | `string\|null` | `null` | Mensaje de error manual |
| `size` | `string` | config `md` | `sm`, `md`, `lg` |
| `keyPlaceholder` | `string` | `Clave` | Placeholder de la columna clave |
| `valuePlaceholder` | `string` | `Valor` | Placeholder de la columna valor |
| `addLabel` | `string` | `Añadir` | Texto del botón de añadir |
| `addable` | `bool` | `true` | Muestra el botón de añadir |
| `deletable` | `bool` | `true` | Muestra el botón de eliminar por fila |
| `reorderable` | `bool` | `false` | Permite reordenar arrastrando (usa `x-sort`) |
| `max` | `int\|null` | `null` | Límite de filas |
| `disabled` | `bool` | `false` | Desactiva el editor |
| `readonly` | bool | false | No añade, borra ni reordena pares; el valor se envía |
| `required` | `bool` | `false` | Marca el campo como obligatorio |
| `showError` | `bool` | `true` | Pinta los errores de validación. A `false` calla **todos**: ni el bag `$errors` ni un `error` escrito a mano |

## Reordenable

```blade
<x-kore::key-value wire:model="headers" label="Cabeceras HTTP"
    key-placeholder="Header" value-placeholder="Valor" reorderable />
```

> El reordenamiento usa `x-sort` (el plugin de Alpine que Livewire trae embebido), así que requiere Livewire en la página.

## Formato del valor

El componente sincroniza un **objeto** `{clave: valor}` (las claves vacías se descartan). Si prefieres un array de pares, guarda el `wire:model` como array y transfórmalo en el backend.

```php
public array $meta = [];

// Resultado tras editar:
// ['env' => 'production', 'region' => 'mx-central']
```

## Notas de implementación

- Usa el plugin Alpine `KoreKeyValue` (registrado automáticamente), con el mismo motor de array que `tag-input`: estado en Alpine, sincronizado a Livewire con `$wire.$set`.
- El contenedor va con `wire:ignore` para que el morphing de Livewire no pise el DOM que Alpine controla.
