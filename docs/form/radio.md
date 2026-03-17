# Radio

Radio buttons with radio group wrapper for vertical and inline layouts.

## Basic Usage

```html
<x-kore::radio wire:model="plan" value="basic" label="Basic" name="plan" />
<x-kore::radio wire:model="plan" value="pro" label="Pro" name="plan" />
```

## Radio Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `description` | string | null | Secondary text |
| `value` | string | null | Radio value |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `disabled` | bool | false | Disabled state |
| `name` | string | null | Group name |
| `error` | string | null | Manual error |
| `showError` | bool | true | Auto-detect errors |

## Radio Group

Wraps radios with label, error handling, and layout control:

```html
<x-kore::radio-group label="Select your plan">
    <x-kore::radio wire:model="plan" value="free" label="Free" description="$0/month" />
    <x-kore::radio wire:model="plan" value="pro" label="Pro" description="$29/month" />
    <x-kore::radio wire:model="plan" value="enterprise" label="Enterprise" description="Contact us" />
</x-kore::radio-group>
```

## Radio Group Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Group label |
| `hint` | string | null | Help text |
| `inline` | bool | false | Horizontal layout |
| `error` | string | null | Manual error |
| `name` | string | null | Name for error detection |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors |

## Inline Layout

```html
<x-kore::radio-group label="Layout" inline>
    <x-kore::radio wire:model="layout" value="grid" label="Grid" />
    <x-kore::radio wire:model="layout" value="list" label="List" />
</x-kore::radio-group>
```

Uses `flex gap-4` instead of `space-y-2`.
