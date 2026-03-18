# Rating

Star rating component with half-star support, readonly mode, and wire:model binding.

## Basic Usage

```blade
<x-kore::rating wire:model="rating" label="Rating" />
```

## Half Star

```blade
<x-kore::rating wire:model="rating" label="Rating" allow-half />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Label text |
| `hint` | `string\|null` | `null` | Hint text below the field |
| `name` | `string\|null` | `null` | Input name (auto-detected from wire:model) |
| `error` | `string\|null` | `null` | Manual error message |
| `size` | `string` | `'md'` | Size variant: `sm`, `md`, `lg` |
| `stars` | `int` | `5` | Number of stars |
| `allow-half` | `bool` | `false` | Enable half-star selection |
| `readonly` | `bool` | `false` | Display-only mode |
| `clearable` | `bool` | `true` | Allow clearing by clicking the selected star |
| `disabled` | `bool` | `false` | Disabled state |
| `required` | `bool` | `false` | Required indicator |
| `show-error` | `bool` | `true` | Show validation errors |

## wire:model

The rating value is a number: `0` (empty), `1`-`N` (full stars), or `0.5`, `1.5`... (half stars).

```php
// Livewire component
public int $rating = 0;
public float $halfRating = 0;
```

## Sizes

```blade
<x-kore::rating size="sm" />  {{-- 16px stars --}}
<x-kore::rating />             {{-- 20px stars (default) --}}
<x-kore::rating size="lg" />  {{-- 28px stars --}}
```

## Readonly

```blade
<x-kore::rating readonly allow-half value="3.5" />
```

## Custom Star Count

```blade
<x-kore::rating :stars="10" label="Score (out of 10)" />
```

## Accessibility

- Interactive: `role="radiogroup"` with individual `role="radio"` per star
- Readonly: `role="img"` with descriptive `aria-label`
- Keyboard: Arrow keys to navigate, Enter/Space to select
