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
| `label`, `hint`, `name`, `error`, `required`, `disabled`, `showError` | — | — | Convención de formulario |
| `searchable` | `bool` | `true` | Muestra el buscador en cada panel |
| `titles` | `array` | `['Disponibles', 'Seleccionados']` | Encabezados de los dos paneles |

## Comportamiento

- **Casilla + botones**: marca elementos y usa `›` / `‹` para mover los seleccionados, o `»` / `«` para mover todos.
- **Búsqueda** independiente por panel (filtra por etiqueta).
- El estado seleccionado se sincroniza con `$wire.$set(model, valores)`.

## Notas de implementación

Plugin Alpine `KoreTransfer`. El contenedor va con `wire:ignore`; las listas se derivan de `items` filtrando por el array `target` (fuente de verdad), así que el orden de `items` se respeta en ambos paneles.
