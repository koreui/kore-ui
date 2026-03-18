# Float Label

Floating label wrapper with three variants. Wraps any kore-ui input component.

## Basic Usage

```html
<x-kore::float-label label="Full Name">
    <x-kore::input wire:model="name" placeholder=" " />
</x-kore::float-label>
```

**Important**: The inner input must have `placeholder=" "` (a space) for the component to detect the empty/filled state correctly. Do not pass a `label` prop to the inner input — the float-label provides it.

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | The floating label text |
| `variant` | string | `'over'` | Variant: `over`, `in`, `on` |

## Variants

### Over (default)

Label starts centered in the input like a placeholder. On focus or when filled, it floats up to sit on the top border with a background cutout.

```html
<x-kore::float-label label="Email">
    <x-kore::input wire:model="email" type="email" placeholder=" " />
</x-kore::float-label>
```

```
Resting:                   Active:
┌─────────────────┐        ──Email────────────
│  Email          │   →    │ john@example.com │
└─────────────────┘        └──────────────────┘
```

### In

Label starts inside the input near the top. On focus, it shrinks but stays inside. The input has extra top padding to accommodate both the label and the text below it.

```html
<x-kore::float-label label="Username" variant="in">
    <x-kore::input wire:model="username" placeholder=" " />
</x-kore::float-label>
```

```
Resting:                   Active:
┌─────────────────┐        ┌─Username─────────┐
│ Username        │   →    │                  │
│                 │        │ johndoe          │
└─────────────────┘        └──────────────────┘
```

### On

Label always sits on the top border with a background cutout. It never moves — only changes color on focus.

```html
<x-kore::float-label label="First Name" variant="on">
    <x-kore::input wire:model="firstName" placeholder=" " />
</x-kore::float-label>
```

```
Always:
──First Name──────
│ John            │
└─────────────────┘
```

## Works With

The float-label wrapper works with any form component that renders an `<input>` or `<textarea>`:

```html
<x-kore::float-label label="Password">
    <x-kore::password wire:model="password" placeholder=" " />
</x-kore::float-label>

<x-kore::float-label label="Bio" variant="in">
    <x-kore::textarea wire:model="bio" placeholder=" " />
</x-kore::float-label>
```

## How It Works

Uses Alpine.js with `focusin`/`focusout`/`input` event listeners on the wrapper div. The `focused` and `filled` reactive properties drive the label position via `x-bind:class`. An `x-init` hook checks if the input already has a value on page load (for edit forms).
