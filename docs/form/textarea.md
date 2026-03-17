# Textarea

Multi-line text input with auto-resize and character counter.

## Basic Usage

```html
<x-kore::textarea wire:model="bio" label="Bio" placeholder="Tell us about yourself..." />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text below |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error message |
| `size` | string | `'md'` | Size variant: `sm`, `md`, `lg` |
| `rows` | int | 4 | Initial row count (configurable via `kore-ui.form.textarea.rows`) |
| `autoResize` | bool | false | Grow with content |
| `maxLength` | int | null | Character limit with visible counter |
| `disabled` | bool | false | Disabled state |
| `readonly` | bool | false | Readonly state |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors |

## Auto Resize

The textarea grows vertically as the user types. Uses Alpine with reactive `x-bind:style` to survive Livewire morphs.

```html
<x-kore::textarea wire:model.live="notes" label="Notes" auto-resize rows="2" />
```

## Character Counter

Shows `current/max` below the textarea. The hint text moves to the left when a counter is present.

```html
<x-kore::textarea label="Tweet" :max-length="280" hint="Keep it short" />
```

## Combined

Both features work together:

```html
<x-kore::textarea label="Description" auto-resize :max-length="500" rows="3" />
```
