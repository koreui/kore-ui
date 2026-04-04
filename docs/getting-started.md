# Getting Started

## Installation

Install via Composer:

```bash
composer require kore-ui/kore-ui
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=kore-ui-config
```

This creates `config/kore-ui.php` with all configurable defaults.

## Setup

### 1. Configure Vite

Add kore-ui's views and PHP sources to your Vite config for hot-reload and Tailwind scanning:

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'resources/views/**',
                'vendor/kore-ui/**',
            ],
        }),
        tailwindcss(),
    ],
});
```

### 2. Import CSS

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import '../../vendor/kore-ui/kore-ui/resources/css/kore-theme.css';

@source '../../vendor/kore-ui/kore-ui/resources/**/*.blade.php';
@source '../../vendor/kore-ui/kore-ui/src/**/*.php';
```

### 3. Add directives to your layout

```html
<!DOCTYPE html>
<html lang="es" x-data>
<head>
    @koreThemeScript
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-kore-bg text-kore-fg antialiased">

    {{ $slot }}

    <livewire:kore-overlay-manager />
    <livewire:kore-feedback-manager />

    @koreScripts
    @livewireScripts
</body>
</html>
```

> **Order matters:** `@koreScripts` must come **before** `@livewireScripts`. Livewire initializes Alpine.js and dispatches the `alpine:init` event — kore-ui plugins must be registered at that moment.

## JavaScript (Alpine.js plugins)

KoreUI ships a pre-compiled JavaScript bundle served via a Laravel route — **no npm configuration required** for the JS side.

The `@koreScripts` directive injects:

```html
<script src="/vendor/kore-ui/kore-ui.js"></script>
```

The bundle (~108 kB, ~30 kB gzip) includes all Alpine.js plugins:

- Overlay system (modals, drawers, panels)
- Feedback system (toasts, confirm dialogs)
- All form plugins: Select, DatePicker, InputOtp, Upload, TimePicker, Rating, Range, TagInput, ColorPicker, Maskable, Password, Number
- All UI plugins: Accordion, Tab, Dropdown, Tooltip, SpeedDial, Splitter, Carousel, Tree, Stepper, Clipboard, Stats
- Spotlight (command palette)
- Theme store (`$store.koreTheme`)

Alpine.js itself is **not included** — Livewire 4 provides it globally.

## Directives reference

| Directive | Location | Purpose |
|---|---|---|
| `@koreThemeScript` | `<head>` | Inline script (~200B) that applies the theme before first paint, preventing FOUC |
| `@koreScripts` | Before `@livewireScripts` | Loads all Alpine.js plugins |

## Rebuilding the bundle (contributors)

If you are contributing to kore-ui and modify files in `resources/js/`, rebuild the bundle:

```bash
cd kore-ui
npm run build   # production build → dist/kore-ui.js
npm run dev     # watch mode
```

The compiled `dist/kore-ui.js` is committed to the repository so end users never need to run a build step.
