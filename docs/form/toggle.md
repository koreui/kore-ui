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

A single native `<input type="checkbox" role="switch">` (visually hidden with `sr-only peer`) drives everything: it keeps full `wire:model` / form-submission compatibility, and the visual track + thumb react purely via CSS `peer-checked` — no Alpine or state-syncing required.

## Accessibility

- **State.** `role="switch"` turns the control into a switch. The on/off state is taken from the input's native `checked` attribute, which the browser exposes to assistive tech automatically. Per [ARIA in HTML](https://www.w3.org/TR/html-aria/#el-input-checkbox), `aria-checked` **must not** be added to a native checkbox — it would be redundant and could desync from `wire:model`. (Note: most modern browser/screen-reader pairs announce it as a "switch", but a few still fall back to "checkbox"; the on/off state is conveyed correctly either way.)
- **Name.** Always give the toggle an accessible name: pass `label` (renders a `<label for>`), or, when there is no visible label, an `aria-label` (forwarded to the input via attribute merge). Without either, the switch has no accessible name (WCAG 4.1.2).
- The `on-label` / `off-label` text inside the track is decorative (`aria-hidden`); it never contributes to the accessible name.

| Size | Track | Thumb |
|------|-------|-------|
| `sm` | `h-5 w-9` | `size-3.5` |
| `md` | `h-6 w-11` | `size-4.5` |
| `lg` | `h-7 w-14` | `size-5.5` |
