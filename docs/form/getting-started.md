# Form Components - Getting Started

## What are the Form Components?

The kore-ui form components provide **10 anonymous Blade components** with Alpine.js interactivity for building forms in Laravel. All components support `wire:model`, automatic error detection from Laravel's `$errors` bag, and semantic design tokens.

- **Input** — Text input with icon, prefix/suffix, clearable, sizes
- **Textarea** — Multi-line with auto-resize and character counter
- **Select** — Single/multi, searchable, native, grouped, async-ready
- **Checkbox** — Custom styled with description and indeterminate state
- **Radio** — Radio buttons with radio group wrapper
- **Toggle** — Switch with on/off labels and sizes
- **Password** — Input with toggleable visibility
- **Number** — Increment/decrement controls with long-press
- **Input OTP** — One-time password with auto-advance and paste
- **Float Label** — Floating label wrapper with 3 variants

## Prerequisites

1. The `kore-ui` package installed and set up — see [Getting Started](../getting-started.md).
2. `@koreScripts` directive in your layout (provides all form Alpine.js plugins).
3. CSS configured — `kore-theme.css` imported and Tailwind sources registered.

## Usage

All components use the `x-kore::` prefix and work inside any Blade or Livewire view:

```html
<form wire:submit="save">
    <x-kore::input wire:model="name" label="Name" icon="user" />
    <x-kore::input wire:model="email" label="Email" type="email" />
    <x-kore::password wire:model="password" label="Password" />
    <x-kore::select wire:model="country" label="Country" :options="$countries" searchable />
    <x-kore::textarea wire:model="bio" label="Bio" auto-resize :max-length="500" />
    <x-kore::toggle wire:model="active" label="Active" />
    <x-kore::checkbox wire:model="terms" label="I accept the terms" />
    <button type="submit">Save</button>
</form>
```

## Shared Features

All form components share these capabilities:

### Automatic Error Detection

Components detect errors from Laravel's `$errors` bag by matching the `name` prop (or `wire:model` attribute):

```html
{{-- Automatic: uses $errors->first('email') --}}
<x-kore::input wire:model="email" name="email" label="Email" />

{{-- Manual error --}}
<x-kore::input label="Email" error="Invalid email format" />

{{-- Suppress error display --}}
<x-kore::input wire:model="email" label="Email" :show-error="false" />
```

### Sizes

Three sizes available on all components: `sm`, `md` (default), `lg`.

```html
<x-kore::input label="Small" size="sm" />
<x-kore::input label="Medium" />
<x-kore::input label="Large" size="lg" />
```

### Hint Text

Shown below the input. Hidden when an error is displayed.

```html
<x-kore::input label="Email" hint="We won't share it" />
```

### Required Indicator

Adds a red asterisk next to the label and the HTML `required` attribute.

```html
<x-kore::input label="Email" required />
```

## Configuration

Customize defaults in `config/kore-ui.php`:

```php
'form' => [
    'size' => 'md',              // Default size for all form components
    'show_errors' => true,       // Auto-detect errors from $errors bag
    'select' => [
        'debounce' => 300,       // Debounce for async search (ms)
        'min_search' => 2,       // Min chars before async search fires
        'search_threshold' => 10,
    ],
    'password' => ['toggleable' => true],
    'textarea' => ['rows' => 4],
],
```

## Architecture

Form components are **anonymous Blade components** registered via `Blade::anonymousComponentPath()`. This means:

- No PHP class per component — logic lives in `@props` / `@php` blocks
- Alpine.js handles interactivity (Select, InputOtp)
- `$attributes->merge()` passes `wire:model` and other attributes directly to the native input
- The `<x-kore::field>` component wraps all inputs with label, error, and hint
