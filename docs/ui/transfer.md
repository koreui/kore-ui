# Transfer

Selector de doble lista para mover elementos entre "disponibles" y "seleccionados" con casillas, búsqueda y botones. Patrón clásico de back-office: asignar roles/permisos, elegir columnas visibles, miembros de un grupo. El `wire:model` guarda el array de valores seleccionados.

## Uso básico

```blade
<x-kore::transfer
    wire:model="selectedRoles"
    label="Permisos"
    :items="[
        ['value' => 'create', 'label' => 'Crear'],
        ['value' => 'edit', 'label' => 'Editar'],
        ['value' => 'delete', 'label' => 'Eliminar'],
    ]"
/>
```

```php
public array $selectedRoles = ['edit'];
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `items` | `array` | `[]` | Opciones `[['value' => , 'label' => ], ...]` |
| `label`, `hint`, `name`, `error`, `required`, `disabled`, `readonly`, `showError` | — | — | Convención de formulario. Las listas se leen y se buscan, pero no se marca ni se mueve nada con `readonly`, y el valor se sigue enviando |
| `searchable` | `bool` | `true` | Muestra el buscador en cada panel |
| `titles` | `array` | `['Disponibles', 'Seleccionados']` | Encabezados de los dos paneles |

## Comportamiento

- **Casilla + botones**: marca elementos y usa `›` / `‹` para mover los seleccionados, o `»` / `«` para mover todos.
- **Búsqueda** independiente por panel (filtra por etiqueta).
- El estado seleccionado se sincroniza con `$wire.$set(model, valores)`.

## Accesibilidad

- Se opera entero con el teclado: `Tab` recorre búsquedas, casillas y botones, y `Espacio` marca. Las casillas llevan `pointer-events-none`, que **no** afecta al teclado.
- Cada casilla lleva `aria-label` con la etiqueta de su elemento, y cada caja de búsqueda con el título de su panel. El `placeholder` no vale como nombre: desaparece en cuanto se escribe algo.

## Notas de implementación

Plugin Alpine `KoreTransfer`. El contenedor va con `wire:ignore`; las listas se derivan de `items` filtrando por el array `target` (fuente de verdad), así que el orden de `items` se respeta en ambos paneles.

Los `items` **no viajan dentro del `x-data`**: van en un `<script type="application/json">` que vive fuera del `wire:ignore`, y un `MutationObserver` los relee cuando Livewire lo actualiza. Dentro del `x-data` se quedaban con los de la primera carga, así que un `:items` que cambiara en el servidor no llegaba nunca.
