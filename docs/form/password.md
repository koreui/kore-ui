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

## Strength Meter

Visual password strength indicator with a progress bar and rules checklist:

```html
<x-kore::password wire:model="password" label="New Password" :strength="true" />
```

### Strength Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `strength` | bool | false | Enable strength meter (configurable via `kore-ui.form.password.strength`) |
| `minLength` | int | 8 | Minimum password length (configurable via `kore-ui.form.password.min_length`) |
| `showRules` | bool | true | Show individual rules checklist |

### Strength Levels

The meter evaluates 5 rules: minimum length, uppercase letter, lowercase letter, number, and special character.

| Rules Passed | Level | Color |
|-------------|-------|-------|
| 0 | — | Muted |
| 1 | Weak | Red |
| 2 | Fair | Orange |
| 3 | Good | Yellow |
| 4-5 | Strong | Green |

### Without Rules Checklist

Show only the progress bar without the individual rules:

```html
<x-kore::password wire:model="password" label="Password"
    :strength="true" :show-rules="false" />
```

### Custom Min Length

```html
<x-kore::password wire:model="password" label="Admin Password"
    :strength="true" :min-length="12" />
```

### With Icon and Strength

```html
<x-kore::password wire:model="password" label="Password"
    icon="lock" :strength="true" />
```

> **Note:** When `strength` is enabled, the component uses an external Alpine component (`KorePassword`) instead of inline `x-data`. The `wire:model` is applied directly on the `<input>` — no hidden input pattern is used. The strength meter only reads the input value to calculate strength.

## Implementation

Without `strength`, uses Alpine inline `x-data="{ show: false }"`. With `strength`, uses `KorePassword` Alpine component. The input type toggles between `password` and `text` via `x-bind:type`. The eye icon switches between `lucide-eye` and `lucide-eye-off`.
