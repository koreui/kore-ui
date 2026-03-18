# Maskable Input

Masked input with token-based formatting, dynamic masks, auto-uppercase, paste support, and wire:model binding.

## Basic Usage

```blade
<x-kore::maskable wire:model="phone" label="Phone" mask="(##) ####-####" />
```

## Mask Tokens

| Token | Matches | Example |
|-------|---------|---------|
| `#` | Digit (0-9) | Phone: `(##) ####-####` |
| `A` | Letter (a-z, A-Z) | |
| `*` | Any character | |
| `!` | Letter → auto uppercase | RFC: `!!!!######!!!` |

Everything else is treated as a literal character (parentheses, dashes, spaces).

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Label text |
| `hint` | `string\|null` | `null` | Hint text below the field |
| `name` | `string\|null` | `null` | Input name (auto-detected from wire:model) |
| `error` | `string\|null` | `null` | Manual error message |
| `size` | `string` | `'md'` | Size variant: `sm`, `md`, `lg` |
| `mask` | `string\|array` | `null` | Mask pattern(s). Pass array for dynamic masks |
| `emit-formatted` | `bool` | `false` | Send formatted value to Livewire instead of raw |
| `slot-char` | `string` | `'_'` | Placeholder character for empty positions |
| `auto-clear` | `bool` | `false` | Clear value on blur if incomplete |
| `icon` | `string\|null` | `null` | Left Lucide icon name |
| `icon-right` | `string\|null` | `null` | Right Lucide icon name |
| `clearable` | `bool` | `false` | Show clear button |
| `disabled` | `bool` | `false` | Disabled state |
| `readonly` | `bool` | `false` | Readonly state |
| `required` | `bool` | `false` | Required indicator |
| `show-error` | `bool` | `true` | Show validation errors |

## wire:model

By default, Livewire receives the **raw value** (digits/letters only, no literals):

```php
// User types: (55) 1234-5678
// Livewire receives: "5512345678"
public string $phone = '';
```

With `emit-formatted`, Livewire receives the formatted value:

```blade
<x-kore::maskable wire:model="phone" mask="(##) ####-####" emit-formatted />
{{-- Livewire receives: "(55) 1234-5678" --}}
```

## Dynamic Masks

Pass an array of masks for inputs that can have different lengths:

```blade
<x-kore::maskable
    :mask="['(##) ####-####', '(##) #####-####']"
    label="Phone"
/>
```

The best-fitting mask is selected automatically based on input length.

## Auto-generated Placeholder

The placeholder is auto-generated from the mask using the slot character:
- Mask `(##) ####-####` → Placeholder `(__) ____-____`
- Override with the `placeholder` attribute

## Configuration

```php
// config/kore-ui.php
'form' => [
    'maskable' => [
        'slot_char' => '_',
        'auto_clear' => false,
        'emit_formatted' => false,
    ],
],
```
