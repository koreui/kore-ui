# koreUi

A modern UI component library for Laravel, built with Livewire 4, Tailwind CSS v4, and Alpine.js.

## Requirements

- PHP ^8.2
- Laravel ^12.0 | ^13.0
- Livewire ^4.0
- Tailwind CSS v4 (installed in your project)

## Installation

```bash
composer require kore-ui/kore-ui
```

### CSS Setup

Import the theme tokens and add the package views to Tailwind's source scan in your `app.css`:

```css
@import "tailwindcss";
@import "../../vendor/kore-ui/kore-ui/resources/css/kore-theme.css";
@source "../vendor/kore-ui/kore-ui/resources/**/*.blade.php";
```

### JS Setup

Import the Alpine components in your `app.js`:

```javascript
import '../../vendor/kore-ui/kore-ui/resources/js';
```

### Overlay Usage

Add the overlay manager to your layout:

```html
<livewire:kore-overlay-manager />
```

Open overlays by dispatching events:

```html
<button wire:click="$dispatch('kore:open', { component: 'my-modal' })">
    Open Modal
</button>
```

## License

MIT
