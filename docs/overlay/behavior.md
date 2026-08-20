# Overlay Behavior Configuration

Each overlay component can configure its close behavior, event dispatching, and visual effects through static methods. All methods have sensible defaults from `config/kore-ui.php`.

## Configuration Methods

All methods are `static` and can be overridden per component class.

### `closesOnClickAway(): bool`

Whether clicking the backdrop closes the overlay.

- **Default:** `true`
- **Config key:** `kore-ui.overlay.defaults.close_on_click_away`

```php
public static function closesOnClickAway(): bool
{
    return false; // Prevent dismissal by clicking outside
}
```

### `closesOnEscape(): bool`

Whether pressing the Escape key closes the overlay.

- **Default:** `true`
- **Config key:** `kore-ui.overlay.defaults.close_on_escape`

```php
public static function closesOnEscape(): bool
{
    return false; // Disable Escape key closing
}
```

### `escapeClosesAll(): bool`

When `true`, pressing Escape force-closes the **entire stack** (all overlays). When `false`, Escape only closes the current overlay and returns to the previous one in the stack.

- **Default:** `true`
- **Config key:** `kore-ui.overlay.defaults.escape_closes_all`

```php
public static function escapeClosesAll(): bool
{
    return false; // Escape only closes current overlay, not the whole stack
}
```

### `dispatchesCloseEvent(): bool`

When `true`, a `kore:closed` Livewire event is dispatched with the overlay `id` when the overlay closes. Useful for parent components that need to react to an overlay being dismissed.

- **Default:** `false`
- **Config key:** `kore-ui.overlay.defaults.dispatch_close_event`

```php
public static function dispatchesCloseEvent(): bool
{
    return true;
}
```

Listen for it in another component:

```php
#[On('kore:closed')]
public function onOverlayClosed(string $id): void
{
    // Refresh data, etc.
}
```

### `destroysOnClose(): bool`

When `true`, the overlay's Livewire component is destroyed (removed from the server state) when it closes. When `false`, the component state is preserved in the manager's `$overlays` array.

- **Default:** `false`
- **Config key:** `kore-ui.overlay.defaults.destroy_on_close`

```php
public static function destroysOnClose(): bool
{
    return true; // Clean up component state on close
}
```

### `backdropBlur(): bool`

When `true`, applies `backdrop-blur-sm` to the overlay backdrop, creating a frosted-glass effect behind the overlay.

- **Default:** `false`
- **Config key:** `kore-ui.overlay.defaults.backdrop_blur`

```php
public static function backdropBlur(): bool
{
    return true;
}
```

### Backdrop colour

The veil is painted with the `--kore-backdrop` token, which is dark in **both** themes. Override it in your own CSS to change it everywhere at once -- the Spotlight uses the same token:

```css
:root { --kore-backdrop: oklch(0.15 0.05 250); }   /* a tinted veil */
```

Do not point it at `--kore-fg`. That is the *text* colour and it flips with the theme, so in dark mode the veil comes out almost white and **lightens** the page instead of dimming it -- the background ends up brighter than the modal itself.

## Escape and Nested Panels

The overlay manager listens for Escape on `window`, so it receives the key no matter where the focus is. That means an overlay and any floating panel opened **inside** it -- a select dropdown, a date picker calendar, a colour panel -- see the very same event.

The rule that keeps them from fighting over it:

> **Whoever consumes Escape marks it with `preventDefault()`.** The manager skips any Escape that is already `defaultPrevented`.

Every floating component in KoreUi follows it, so a select open inside a modal takes two presses: the first closes the dropdown, the second closes the modal. Before this contract existed, one press closed both and the user lost the whole form by dismissing a dropdown.

If you build your own floating panel meant to live inside an overlay, do the same -- and only when there is something to close:

```js
onKeydown(e) {
    if (e.key !== 'Escape') return;
    if (! this.open) return;      // nothing to consume: let it through

    e.preventDefault();           // this is what tells the manager to stand down
    this.close();
}
```

Calling `preventDefault()` on an Escape you did not actually use is worse than not calling it at all: the manager will discard the key and nothing closes.

### Panels teleported to `<body>`

`x-teleport` keeps Alpine's *scope* but not the DOM tree. A panel moved to `<body>` no longer bubbles its events through the component root, so an `x-on:keydown` placed only on the root stops receiving anything the moment focus enters the panel. Put the handler on the teleported node as well:

```blade
<template x-teleport="body">
    <div data-kore-teleport x-show="open" x-on:keydown="onKeydown($event)">
        ...
    </div>
</template>
```

`tests/Ui/PanelesTeleportadosTest.php` enforces this for every teleported panel that contains focusable controls.

## Cost in Server Round-Trips

The overlay manager is a separate Livewire component, so opening an overlay is two components talking to each other. Measured in `demo/e2e/specs/34-overlay-pila.spec.js` and `35-overlay-feedback.spec.js`:

| Action | Round-trips |
|---|---|
| Open an overlay by dispatching `kore:open` from the browser | 1 |
| Close it (the manager resets its own state) | 1 |
| Open a confirm dialog from a server action | 2 |
| Answer it (response + callback + manager cleanup) | 3 |
| Show a toast from the server | 1 |
| Show a toast from JavaScript (`window.dispatchEvent`) | 0 |
| Open the spotlight | 0 |

Opening an overlay from a `wire:click` costs one more trip than dispatching `kore:open` in the browser, because the click has to reach the server first. When the overlay needs no server state, dispatch it client-side.

## Default Values Summary

From `config/kore-ui.php`:

```php
'overlay' => [
    'defaults' => [
        'type' => 'modal',
        'size' => '2xl',
        'position' => 'center',
        'close_on_click_away' => true,
        'close_on_escape' => true,
        'escape_closes_all' => true,
        'dispatch_close_event' => false,
        'destroy_on_close' => false,
        'backdrop_blur' => false,
    ],
],
```

You can publish and modify this config:

```bash
php artisan vendor:publish --tag=kore-ui-config
```

## Preventing Close with `kore:before-close`

You can intercept close attempts (from Escape or click-away) and cancel them. This allows patterns like "unsaved changes" confirmation.

**Important:** This event is dispatched client-side by the Alpine component via `Livewire.dispatchTo()`. It only works with client-side listeners using `@script` / `$wire.on()`. A PHP `#[On('kore:before-close')]` listener will **not** be able to cancel the close because the cancellation must happen synchronously in JavaScript.

### How It Works

The Alpine component creates a `params` object with:

- `id` (string) -- the overlay ID
- `trigger` (`'escape'` | `'click-away'`) -- what caused the close attempt
- `closing` (boolean) -- set to `false` to cancel the close

The event is dispatched to the current overlay component via `Livewire.dispatchTo()`. If `params.closing` is still `true` after dispatch, the close proceeds.

### Example: Confirm Before Closing

```php
class EditForm extends OverlayComponent
{
    public bool $isDirty = false;

    // ... form logic that sets $isDirty = true on changes

    public function render()
    {
        return view('livewire.overlays.edit-form');
    }
}
```

In the Blade view, use `@script` to register the client-side listener:

```html
<div>
    <!-- form content -->

    @script
    <script>
        $wire.on('kore:before-close', (params) => {
            if ($wire.isDirty && params.trigger === 'click-away') {
                // Prevent accidental dismissal when form has changes
                params.closing = false;
            }
        });
    </script>
    @endscript
</div>
```

This cancels click-away closing when the form is dirty, but still allows Escape to close. You can also block both triggers:

```html
@script
<script>
    $wire.on('kore:before-close', (params) => {
        if ($wire.isDirty) {
            params.closing = false;
            // Optionally show a warning
            alert('You have unsaved changes.');
        }
    });
</script>
@endscript
```

## Full Example: Locked Confirm Dialog

A confirm dialog that cannot be dismissed by clicking away or pressing Escape -- the user must click a button:

```php
class ImportantConfirm extends OverlayComponent
{
    public static function overlayType(): string
    {
        return 'confirm';
    }

    public static function overlaySize(): string
    {
        return 'sm';
    }

    public static function closesOnClickAway(): bool
    {
        return false;
    }

    public static function closesOnEscape(): bool
    {
        return false;
    }

    public function accept(): void
    {
        // Handle acceptance...
        $this->close();
    }

    public function render()
    {
        return view('livewire.overlays.important-confirm');
    }
}
```
