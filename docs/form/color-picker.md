# Color Picker

Palette-based color picker with dropdown and inline modes, custom hex input, clearable, and wire:model binding.

## Basic Usage

```blade
<x-kore::color-picker wire:model="color" label="Color" />
```

## Inline Mode

```blade
<x-kore::color-picker wire:model="color" label="Color" inline />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Label text |
| `hint` | `string\|null` | `null` | Hint text below the field |
| `name` | `string\|null` | `null` | Input name (auto-detected from wire:model) |
| `error` | `string\|null` | `null` | Manual error message |
| `size` | `string` | `'md'` | Size variant: `sm`, `md`, `lg` |
| `colors` | `array\|null` | 24 curated colors | Custom color palette (array of hex strings) |
| `allow-custom` | `bool` | `true` | Show hex input for custom colors |
| `inline` | `bool` | `false` | Show palette inline (no dropdown) |
| `clearable` | `bool` | `true` | Allow clearing selected color |
| `columns` | `int` | `8` | Number of columns in the swatch grid |
| `disabled` | `bool` | `false` | Disabled state |
| `required` | `bool` | `false` | Required indicator |
| `show-error` | `bool` | `true` | Show validation errors |

## wire:model

The value is a hex color string (e.g., `"#3b82f6"`) or empty string when cleared.

```php
// Livewire component
public string $color = '';
```

## Custom Palette

```blade
<x-kore::color-picker
    :colors="['#1a1a2e', '#16213e', '#0f3460', '#e94560']"
    :columns="4"
    inline
/>
```

## Configuration

```php
// config/kore-ui.php
'form' => [
    'color_picker' => [
        'columns' => 8,
        'allow_custom' => true,
    ],
],
```

## Default Palette

24 curated colors covering the full spectrum: reds, oranges, yellows, greens, teals, blues, purples, pinks, neutrals, and black/white.

## Swatch Sizes

| Size | Swatch |
|------|--------|
| `sm` | 24px |
| `md` | 28px |
| `lg` | 36px |
