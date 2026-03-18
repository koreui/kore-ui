# Tag Input

Tag/chips input with keyboard support, paste handling, max limit, custom separator, and wire:model binding.

## Basic Usage

```blade
<x-kore::tag-input wire:model="skills" label="Skills" placeholder="Add a skill..." />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Label text |
| `hint` | `string\|null` | `null` | Hint text below the field |
| `name` | `string\|null` | `null` | Input name (auto-detected from wire:model) |
| `error` | `string\|null` | `null` | Manual error message |
| `size` | `string` | `'md'` | Size variant: `sm`, `md`, `lg` |
| `separator` | `string` | `','` | Character that triggers tag addition |
| `max` | `int\|null` | `null` | Maximum number of tags |
| `allow-duplicate` | `bool` | `false` | Allow duplicate tags |
| `add-on-blur` | `bool` | `true` | Add current text as tag on blur |
| `placeholder` | `string\|null` | `null` | Input placeholder text |
| `clearable` | `bool` | `false` | Show clear-all button |
| `disabled` | `bool` | `false` | Disabled state |
| `required` | `bool` | `false` | Required indicator |
| `show-error` | `bool` | `true` | Show validation errors |

## wire:model

The value is an array of strings. Syncs via `$wire.$set()` (same pattern as multi-select).

```php
// Livewire component
public array $skills = [];
```

## Keyboard Interactions

| Key | Action |
|-----|--------|
| `Enter` | Add current text as tag |
| Separator char (`,` by default) | Add current text as tag |
| `Backspace` (empty input) | Remove last tag |

## Paste Support

Pasting text automatically splits by the separator character and adds each part as a tag.

## Max Limit

```blade
<x-kore::tag-input :max="5" label="Tags (max 5)" />
```

## Custom Separator

```blade
<x-kore::tag-input separator=";" label="Semicolon separated" />
```
