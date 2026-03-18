# Range / Slider

Range slider with single and dual-handle modes, custom min/max/step, value display, and wire:model binding.

## Basic Usage

```blade
<x-kore::range wire:model="volume" label="Volume" />
```

## Range Mode

```blade
<x-kore::range wire:model="priceRange" label="Price" range :min="0" :max="200" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Label text |
| `hint` | `string\|null` | `null` | Hint text below the field |
| `name` | `string\|null` | `null` | Input name (auto-detected from wire:model) |
| `error` | `string\|null` | `null` | Manual error message |
| `size` | `string` | `'md'` | Size variant: `sm`, `md`, `lg` |
| `min` | `number` | `0` | Minimum value |
| `max` | `number` | `100` | Maximum value |
| `step` | `number` | `1` | Step increment |
| `range` | `bool` | `false` | Enable dual-handle range mode |
| `show-value` | `bool` | `false` | Display current value above the slider |
| `show-labels` | `bool` | `false` | Display min/max labels below the slider |
| `disabled` | `bool` | `false` | Disabled state |
| `required` | `bool` | `false` | Required indicator |
| `show-error` | `bool` | `true` | Show validation errors |

## wire:model

- **Single mode**: Number value (e.g., `50`)
- **Range mode**: Array with two values (e.g., `[20, 80]`)

```php
// Livewire component
public int $volume = 50;
public array $priceRange = [20, 80];
```

## Sizes

Track height and thumb size scale with the size prop:

| Size | Track | Thumb |
|------|-------|-------|
| `sm` | 4px | 14px |
| `md` | 8px | 18px |
| `lg` | 12px | 22px |

## Value Display

```blade
<x-kore::range show-value show-labels :min="0" :max="100" />
```
