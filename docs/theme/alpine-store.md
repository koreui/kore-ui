# Alpine Store API

The theme system exposes a global Alpine store at `$store.koreTheme`. It manages theme state, localStorage persistence, and OS preference detection.

## State

| Property | Type | Description |
|----------|------|-------------|
| `mode` | `'light' \| 'dark' \| 'system'` | User-selected mode |
| `resolved` | `'light' \| 'dark'` | Actual applied theme (resolves `system` to light/dark) |

## Getters

| Getter | Returns | Description |
|--------|---------|-------------|
| `isDark` | boolean | `resolved === 'dark'` |
| `isLight` | boolean | `resolved === 'light'` |
| `isSystem` | boolean | `mode === 'system'` |
| `systemPrefersDark` | boolean | OS prefers dark (`matchMedia` result) |

## Methods

### `setMode(mode)`

Sets the theme mode. Accepts `'light'`, `'dark'`, or `'system'`.

```html
<button @click="$store.koreTheme.setMode('dark')">Dark</button>
```

This method:
1. Updates `mode` and `resolved` properties
2. Saves to `localStorage('kore-theme')`
3. Applies/removes `dark` class and `data-theme` attribute on `<html>`
4. Updates the `matchMedia` listener (adds when system, removes otherwise)
5. Dispatches a `theme-changed` CustomEvent on `document`

### `init()`

Called automatically when the store is registered. Reads `localStorage`, validates the value, and initializes the `matchMedia` listener. You should not call this manually.

## Events

### `theme-changed`

Dispatched on `document` whenever the theme changes. Useful for integrating with non-Alpine code:

```js
document.addEventListener('theme-changed', (e) => {
    console.log(e.detail.mode);     // 'light', 'dark', or 'system'
    console.log(e.detail.resolved); // 'light' or 'dark'
});
```

## Usage from Alpine Components

Access the store from any Alpine `x-data`, `x-init`, `x-bind`, etc.:

```html
{{-- Conditional rendering --}}
<div x-show="$store.koreTheme.isDark">Dark mode content</div>

{{-- Reactive text --}}
<span x-text="$store.koreTheme.mode"></span>

{{-- In x-data methods --}}
<div x-data="{
    toggleTheme() {
        const next = this.$store.koreTheme.isDark ? 'light' : 'dark';
        this.$store.koreTheme.setMode(next);
    }
}">
    <button @click="toggleTheme">Toggle</button>
</div>
```

## localStorage

The store persists the mode (not the resolved theme) in `localStorage` under the key `kore-theme`. Valid values: `'light'`, `'dark'`, `'system'`. Invalid or missing values default to `'system'`.

All `localStorage` access is wrapped in try/catch for compatibility with restrictive incognito modes or disabled storage.

## System Preference Detection

When `mode === 'system'`, the store listens to `matchMedia('(prefers-color-scheme: dark)')` for changes. When the OS preference changes, the resolved theme updates automatically without any user interaction.

The listener is cleaned up when switching away from system mode to prevent memory leaks.

## DOM Effects

The store applies two attributes to `document.documentElement` (`<html>`):

| Resolved | Class | Attribute |
|----------|-------|-----------|
| `dark` | adds `dark` | `data-theme="dark"` |
| `light` | removes `dark` | `data-theme="light"` |

Both are needed because `kore-theme.css` targets `.dark, [data-theme="dark"]`. The class enables Tailwind's `dark:` variant; the attribute enables CSS selector targeting.
