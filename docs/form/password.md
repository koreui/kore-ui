# Password

Password input with toggleable visibility.

## Basic Usage

```html
<x-kore::password wire:model="password" label="Password" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `icon` | string | null | Left Lucide icon name |
| `toggleable` | bool | true | Show eye toggle button (configurable via `kore-ui.form.password.toggleable`) |
| `disabled` | bool | false | Disabled state |
| `readonly` | bool | false | Readonly state |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors |

## With Icon

```html
<x-kore::password wire:model="password" label="Password" icon="lock" />
```

## Without Toggle

```html
<x-kore::password wire:model="pin" label="PIN" :toggleable="false" />
```

## Implementation

Uses Alpine inline `x-data="{ show: false }"`. The input type toggles between `password` and `text` via `x-bind:type`. The eye icon switches between `lucide-eye` and `lucide-eye-off`.
