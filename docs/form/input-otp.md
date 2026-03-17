# Input OTP

One-time password input with auto-advance, paste support, numeric/masked modes, and visual separator.

## Basic Usage

```html
<x-kore::input-otp wire:model="code" label="Verification Code" :length="6" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `label` | string | null | Label text |
| `hint` | string | null | Help text |
| `name` | string | null | Name for error detection |
| `error` | string | null | Manual error |
| `length` | int | 6 | Number of digit inputs |
| `numeric` | bool | false | Restrict to numbers only (`inputmode="numeric"`) |
| `masked` | bool | false | Hide digits (`type="password"`) |
| `separatorAfter` | int | null | Position to insert a visual dash separator |
| `size` | string | `'md'` | Size: `sm`, `md`, `lg` |
| `disabled` | bool | false | Disabled state |
| `required` | bool | false | Required indicator |
| `showError` | bool | true | Auto-detect errors |

## Numeric

Shows numeric keyboard on mobile:

```html
<x-kore::input-otp wire:model="pin" label="PIN" :length="4" numeric />
```

## Masked

Each digit hidden like a password:

```html
<x-kore::input-otp wire:model="pin" label="Secret PIN" :length="4" masked />
```

## Separator

Visual dash between digit groups:

```html
<x-kore::input-otp wire:model="code" label="Code" :length="6" :separator-after="3" />
```

Renders as: `[ ] [ ] [ ] — [ ] [ ] [ ]`

## Keyboard Behavior

| Action | Behavior |
|--------|----------|
| Type a digit | Fills current input, auto-advances to next |
| Backspace | Clears current; if empty, moves to previous |
| Arrow Left/Right | Navigate between inputs |
| Paste | Distributes pasted text across all inputs |

## Wire:model

A hidden input holds the concatenated value (e.g., `"123456"`). On each change, Alpine joins the digits array and dispatches an `input` event on the hidden input for `wire:model` compatibility.
