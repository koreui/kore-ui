# Checkbox

Custom styled checkbox with description, sizes, label position, and indeterminate state.

## Basic Usage

```html
<x-kore::checkbox wire:model="terms" label="I accept the terms" />

<x-kore::checkbox wire:model="notifications" label="Email notifications"
    description="Receive weekly updates about your account" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `description` | string | null | Secondary text below the label |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `labelPosition` | string | `'right'` | Label side: `left`, `right` |
| `indeterminate` | bool | false | Third state for "select all" patterns |
| `disabled` | bool | false | Disabled state |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error message |
| `showError` | bool | true | Auto-detect errors |

## Indeterminate

For "select all" patterns where some but not all children are checked:

```html
<x-kore::checkbox wire:model="selectAll" label="Select all" indeterminate />
```

Uses Alpine `x-init` to set the native `indeterminate` property on the checkbox element.

## Label Position

```html
<x-kore::checkbox label="Remember me" label-position="left" />
```

## Custom Styling

The checkbox uses `appearance-none` with a CSS background-image SVG checkmark on `:checked`. It inherits the `kore-primary` token for the checked state.
