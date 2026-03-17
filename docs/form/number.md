# Number

Number input with increment/decrement controls and long-press support.

## Basic Usage

```html
<x-kore::number wire:model="quantity" label="Quantity" :min="1" :max="99" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `min` | number | null | Minimum value |
| `max` | number | null | Maximum value |
| `step` | number | 1 | Increment/decrement step |
| `controls` | bool | true | Show +/- buttons |
| `disabled` | bool | false | Disabled state |
| `readonly` | bool | false | Readonly state |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors |

## Custom Step

For decimal values:

```html
<x-kore::number wire:model="price" label="Price" :min="0" :step="0.01" />
```

## Without Controls

Plain number input without +/- buttons:

```html
<x-kore::number wire:model="amount" label="Amount" :controls="false" />
```

## Long Press

Hold the +/- buttons for rapid increment/decrement. Starts after 400ms, repeats every 75ms.

## Wire:model Compatibility

Alpine modifies the input value and dispatches a native `input` event with `{ bubbles: true }` so Livewire intercepts it correctly. Floating point precision is handled with `Math.round(val * 1e10) / 1e10`.
