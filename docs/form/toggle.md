# Toggle

Switch toggle with on/off labels, sizes, and label position.

## Basic Usage

```html
<x-kore::toggle wire:model="darkMode" label="Dark mode" />

<x-kore::toggle wire:model="twoFactor" label="Two-factor authentication"
    description="Add an extra layer of security" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `description` | string | null | Secondary text |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `labelPosition` | string | `'right'` | Label side: `left`, `right` |
| `onLabel` | string | null | Text inside track when on |
| `offLabel` | string | null | Text inside track when off |
| `disabled` | bool | false | Disabled state |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error |
| `showError` | bool | true | Auto-detect errors |

## On/Off Labels

Text rendered inside the toggle track. Best with `lg` size:

```html
<x-kore::toggle wire:model="maintenance" label="Maintenance mode"
    size="lg" on-label="ON" off-label="OFF" />
```

## Implementation

Uses a hidden `<input type="checkbox">` for `wire:model` compatibility. A `<button role="switch">` provides the visual toggle. Alpine syncs the button state with the hidden checkbox via `x-bind:checked` and event dispatching.

| Size | Track | Thumb |
|------|-------|-------|
| `sm` | `h-5 w-9` | `size-3.5` |
| `md` | `h-6 w-11` | `size-4.5` |
| `lg` | `h-7 w-14` | `size-5.5` |
