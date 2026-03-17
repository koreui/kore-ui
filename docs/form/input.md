# Input

Text input with icon, prefix/suffix addons, clearable button, and full `wire:model` support.

## Basic Usage

```html
<x-kore::input wire:model="name" label="Name" placeholder="Enter your name" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text above the input |
| `hint` | string | null | Help text below (hidden when error shown) |
| `name` | string | null | Input name (also used for error detection) |
| `error` | string | null | Manual error message |
| `type` | string | `'text'` | HTML input type |
| `size` | string | `'md'` | Size variant: `sm`, `md`, `lg` |
| `icon` | string | null | Left Lucide icon name |
| `iconRight` | string | null | Right Lucide icon name |
| `prefix` | string | null | Text addon on the left |
| `suffix` | string | null | Text addon on the right |
| `clearable` | bool | false | Show clear (X) button when input has value |
| `disabled` | bool | false | Disabled state |
| `readonly` | bool | false | Readonly state |
| `required` | bool | false | Required with asterisk indicator |
| `showError` | bool | true | Auto-detect errors from `$errors` bag |

## Icons

```html
<x-kore::input label="Search" icon="search" placeholder="Search..." />
<x-kore::input label="Email" icon="mail" icon-right="check" />
```

Icons use [Lucide](https://lucide.dev/) via `blade-lucide-icons`. Pass the icon name without the `lucide-` prefix.

## Prefix & Suffix

Text addons rendered as inline segments with a muted background:

```html
<x-kore::input label="Website" prefix="https://" suffix=".com" />
<x-kore::input label="Price" prefix="$" suffix="USD" />
```

Prefix/suffix use a flex layout — they take their natural width regardless of text length.

## Clearable

```html
<x-kore::input wire:model="search" label="Search" icon="search" clearable />
```

Shows an X button when the input has a value. Clicking it clears the value and dispatches an `input` event for `wire:model` compatibility.

## States

```html
<x-kore::input label="Disabled" disabled />
<x-kore::input label="Readonly" value="Can't change" readonly />
<x-kore::input label="Required" required />
<x-kore::input label="Error" error="This field is required" />
```

## Attribute Forwarding

All extra attributes pass through to the native `<input>` element:

```html
<x-kore::input wire:model.live="email" label="Email" type="email"
    placeholder="you@example.com" autocomplete="email" />
```
