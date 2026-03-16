# Overlay Sizes

Sizes control the maximum width of the overlay panel. They use responsive Tailwind CSS `max-w-*` classes so overlays adapt gracefully across breakpoints.

## Available Sizes

| Size | Classes |
|------|---------|
| `sm` | `sm:max-w-sm` |
| `md` | `sm:max-w-md` |
| `lg` | `sm:max-w-md md:max-w-lg` |
| `xl` | `sm:max-w-md md:max-w-xl` |
| `2xl` | `sm:max-w-md md:max-w-xl lg:max-w-2xl` |
| `3xl` | `sm:max-w-md md:max-w-xl lg:max-w-3xl` |
| `4xl` | `sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-4xl` |
| `5xl` | `sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl` |
| `6xl` | `sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl 2xl:max-w-6xl` |
| `7xl` | `sm:max-w-md md:max-w-xl lg:max-w-3xl xl:max-w-5xl 2xl:max-w-7xl` |
| `full` | `max-w-full` |

## How Sizes Work

On mobile (below the `sm` breakpoint), every overlay is full-width. As the viewport grows, the max-width progressively expands through larger breakpoints. For example, a `5xl` overlay is:

- **< sm**: full width
- **sm**: max-w-md (28rem)
- **md**: max-w-xl (36rem)
- **lg**: max-w-3xl (48rem)
- **xl**: max-w-5xl (64rem)

This responsive approach means you pick a logical size and the framework handles the breakpoint progression. If the requested size is not recognized, it falls back to `2xl`.

## Setting the Size

Override the `overlaySize()` static method in your component:

```php
use KoreUi\Overlay\OverlayComponent;

class QuickAction extends OverlayComponent
{
    public static function overlaySize(): string
    {
        return 'sm';
    }

    public function render()
    {
        return view('livewire.overlays.quick-action');
    }
}
```

The default size, if not overridden, is `2xl` (configurable in `config/kore-ui.php`).

## Sizes Apply to All Overlay Types

The size controls `max-width` on the overlay panel, which applies to every type:

- **Modal**: constrains the centered dialog width
- **Drawer**: constrains the drawer panel width
- **Confirm**: constrains the confirmation dialog width
- **Bottom Sheet**: constrains the sheet width
- **Fullscreen**: typically use `full` to fill the viewport

```php
class WideDrawer extends OverlayComponent
{
    public static function overlayType(): string
    {
        return 'drawer';
    }

    public static function overlaySize(): string
    {
        return '4xl';
    }

    public function render()
    {
        return view('livewire.overlays.wide-drawer');
    }
}
```

## Runtime Size Override

You can override the size at open time without changing the component:

```html
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.edit-form',
    overlayAttributes: { size: 'lg' }
})">
    Open (small)
</button>

<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.edit-form',
    overlayAttributes: { size: '5xl' }
})">
    Open (large)
</button>
```

When you override `size` via `overlayAttributes`, the `sizeClass` is automatically re-computed from the new size value by the `OverlayManager`. See [runtime-overrides.md](./runtime-overrides.md) for details.
