# Order List

Lista única reordenable: el usuario arrastra los elementos (o usa los botones ↑/↓) para fijar un orden. El `wire:model` guarda el array de valores en el orden elegido. Útil para prioridades, pasos de un flujo, columnas o cualquier secuencia editable.

## Uso básico

```blade
<x-kore::order-list
    wire:model="priorityOrder"
    label="Prioridad"
    :items="[
        ['value' => 1, 'label' => 'Urgente'],
        ['value' => 2, 'label' => 'Alta'],
        ['value' => 3, 'label' => 'Media'],
        ['value' => 4, 'label' => 'Baja'],
    ]"
/>
```

```php
// Vacío = usa el orden de :items; tras reordenar guarda p.ej. [3, 1, 2, 4]
public array $priorityOrder = [];
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `items` | `array` | `[]` | Elementos `[['value' => , 'label' => ], ...]` |
| `reorderable` | `bool` | `true` | Permite arrastrar (`x-sort`); si `false`, solo lectura |
| `label`, `hint`, `name`, `error`, `required`, `disabled`, `readonly`, `showError` | — | — | Convención de formulario. El orden se ve pero no se cambia con `readonly`, y el valor se sigue enviando |

## Comportamiento

- **Arrastre** por el tirador (usa `x-sort`, el plugin de Alpine embebido en Livewire 4).
- **Botones ↑/↓** como alternativa accesible por teclado.
- El orden se sincroniza como array de valores con `$wire.$set`.
- Si el `wire:model` llega vacío, se usa el orden de `items`; los valores desconocidos se descartan y los que falten se añaden al final (reconciliación).

## Notas de implementación

Plugin Alpine `KoreOrderList`. La lista renderiza desde el array `order` (fuente de verdad), con `:key="item.value"` estable y `x-sort:item="item.value"` para que el reordenamiento sea por valor, no por índice.

## Accesibilidad

Se reordena sin ratón: cada fila lleva botones de subir y bajar además del tirador de arrastre, y el cambio viaja al servidor por el `wire:model`.

## Notas de implementación

Los `items` van en un `<script type="application/json">` **fuera** del `wire:ignore` de la raíz, y un `MutationObserver` los relee cuando el servidor los cambia: dentro del `x-data` se quedaban congelados en los de la primera carga.

Al releerlos, el orden se reconcilia — lo que el usuario había movido se queda donde estaba y los elementos nuevos se añaden al final.
