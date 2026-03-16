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
