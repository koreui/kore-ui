# Theme - Getting Started

## What is the Theme Switch?

The kore-ui theme system provides a **theme switch component** and an **Alpine store** for managing light, dark, and system color modes. It persists the user's preference in `localStorage` and reacts to OS-level preference changes in real time.

- **Alpine store** — Global state via `$store.koreTheme`, accessible from any component
- **Anti-FOUC directive** — Inline `<script>` that applies the theme before first paint
- **Theme Switch component** — 3 visual variants: segmented, toggle, dropdown
- **Shared state** — Multiple instances on the same page stay synchronized automatically

## Prerequisites

1. The `kore-ui` package installed.
2. CSS tokens configured — `kore-theme.css` already defines `.dark, [data-theme="dark"]` variants.
3. Alpine.js available globally (included via kore-ui JS).

## Setup

### 1. Add the anti-FOUC directive

Place `@koreThemeScript` in your layout's `<head>`, **before** any CSS or JS:

```html
<head>
    @koreThemeScript
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
```

This renders a blocking inline script (~200 bytes) that reads `localStorage` and applies the `dark` class + `data-theme` attribute to `<html>` before the browser paints. Without it, users may see a flash of the wrong theme on page load.

### 2. Use the component

```html
{{-- Segmented (default) — shows light, system, dark --}}
<x-kore::theme-switch />

{{-- Toggle — simple light/dark switch --}}
<x-kore::theme-switch variant="toggle" />

{{-- Dropdown — compact menu --}}
<x-kore::theme-switch variant="dropdown" />
```

No additional configuration is required. The Alpine store is registered automatically when kore-ui's JS is loaded.

## Configuration

Optional settings in `config/kore-ui.php`:

```php
'theme' => [
    'default' => 'system',  // 'light', 'dark', 'system'
    'nonce' => null,         // CSP nonce for the anti-FOUC inline script
],
```

### CSP Nonce

If your app uses a strict Content Security Policy, pass a nonce:

```php
// config/kore-ui.php
'theme' => [
    'nonce' => 'your-csp-nonce',
],
```

The `@koreThemeScript` directive will render `<script nonce="your-csp-nonce">`.

## Architecture

The theme system has three layers:

1. **Anti-FOUC script** (`@koreThemeScript`) — Runs before Alpine exists. Reads `localStorage('kore-theme')` and applies `dark` class + `data-theme` attribute synchronously.

2. **Alpine store** (`$store.koreTheme`) — Registered in `alpine:init`. Manages state, localStorage persistence, `matchMedia` listener for system preference, and dispatches `theme-changed` CustomEvent.

3. **Blade component** (`<x-kore::theme-switch>`) — Anonymous component with 3 visual variants. Reads and writes to the store via `$store.koreTheme`.

### How the layers connect

```
Page load
  → @koreThemeScript applies dark/light instantly (no Alpine yet)
  → Alpine boots, store.init() reads localStorage, syncs with DOM
  → Theme switch components bind to $store.koreTheme reactively
  → User clicks → store.setMode() → localStorage + DOM + CustomEvent
```

### wire:navigate compatibility

The store is idempotent — on re-init it reads `localStorage` and reaches the same state. `wire:navigate` destroys and recreates Alpine, but the source of truth (`localStorage`) persists across navigations.
