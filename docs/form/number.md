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

## Currency Mode

Formatted currency input using `Intl.NumberFormat`. Shows the raw numeric value on focus for easy editing, and formats on blur.

```html
<x-kore::number wire:model="amount" label="Amount (USD)"
    mode="currency" :controls="false" />
```

### Currency Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `mode` | string | `'decimal'` | `'decimal'` or `'currency'` |
| `currency` | string | `'USD'` | ISO 4217 currency code (configurable via `kore-ui.form.number.currency`) |
| `locale` | string | null | BCP 47 locale for formatting (configurable via `kore-ui.form.number.locale`) |
| `precision` | int | 2 | Decimal places (configurable via `kore-ui.form.number.precision`) |
| `prefix` | string | null | Custom prefix (alternative to currency) |
| `suffix` | string | null | Custom suffix (e.g. `' kg'`) |

### Different Currencies

```html
{{-- Mexican Peso --}}
<x-kore::number wire:model="monto" label="Monto (MXN)"
    mode="currency" currency="MXN" locale="es-MX" />

{{-- Euro --}}
<x-kore::number wire:model="betrag" label="Betrag (EUR)"
    mode="currency" currency="EUR" locale="de-DE" />
```

### Currency with Controls

```html
<x-kore::number wire:model="donation" label="Donation"
    mode="currency" :step="10" :min="0" :max="10000" />
```

### Custom Precision

For whole currency amounts:

```html
<x-kore::number wire:model="budget" label="Budget"
    mode="currency" :precision="0" />
```

> **Note:** Currency mode uses the hidden input pattern (like maskable) — a hidden `<input>` holds the raw numeric value for `wire:model`, while a visible `<input type="text">` shows the formatted display. The `min`/`max` constraints apply to the raw value.

## Wire:model Compatibility

In decimal mode, Alpine modifies the input value and dispatches a native `input` event with `{ bubbles: true }` so Livewire intercepts it correctly. In currency mode, the hidden input dispatches the event. Floating point precision is handled with `Math.round(val * 1e10) / 1e10`.
