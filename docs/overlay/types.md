# Overlay Types

The overlay system supports five distinct types, each with its own positioning, animation, and container styling.

## The Five Types

| Type | Default Position | Animation | Container Style |
|------|-----------------|-----------|-----------------|
| `modal` | `center` | Scale + translate | Rounded corners, max-height 90dvh, scrollable |
| `drawer` | `right` | Slide-x | Full height, no rounded corners, scrollable |
| `confirm` | `center` | Scale | Rounded corners, compact |
| `bottom-sheet` | `bottom` | Slide-y | Rounded top corners, max-height 80dvh, scrollable |
| `fullscreen` | `center` | Fade | No rounded corners, full height (min-h-screen), scrollable |

## Setting the Type

Override the `overlayType()` static method in your component:

```php
use KoreUi\Overlay\OverlayComponent;

class UserSettings extends OverlayComponent
{
    public static function overlayType(): string
    {
        return 'drawer';
    }

    public function render()
    {
        return view('livewire.overlays.user-settings');
    }
}
```

If you do not override the method, the type defaults to the value in `config/kore-ui.php` (which is `modal` by default).

## Type Details

### Modal

Centered on screen with a scale-up + translate animation. Ideal for forms, detail views, and general-purpose dialogs.

```
Position classes: items-end justify-center p-4 text-center sm:items-center sm:p-0
Container:        rounded-kore-lg max-h-[90dvh] overflow-y-auto
```

```php
public static function overlayType(): string
{
    return 'modal';
}
```

### Drawer

Slides in from the right edge (or left, if overridden). Full viewport height. Ideal for settings panels, navigation, and detail sidebars.

```
Position classes (right): items-stretch justify-end
Position classes (left):  items-stretch justify-start
Container:                h-full overflow-y-auto
```

```php
public static function overlayType(): string
{
    return 'drawer';
}
```

### Confirm

Centered with a quick scale animation. Compact container without scrolling by default. Ideal for confirmation prompts, alerts, and simple yes/no decisions.

```
Position classes: items-end justify-center p-4 text-center sm:items-center sm:p-0
Container:        rounded-kore-lg
```

```php
public static function overlayType(): string
{
    return 'confirm';
}
```

### Bottom Sheet

Slides up from the bottom of the viewport. Rounded top corners. Capped at 80% of viewport height. Ideal for mobile-friendly actions, pickers, and option lists.

```
Position classes: items-end justify-center p-4 sm:p-0
Container:        rounded-t-kore-xl max-h-[80dvh] overflow-y-auto
```

```php
public static function overlayType(): string
{
    return 'bottom-sheet';
}
```

### Fullscreen

Fills the entire viewport with a fade animation. No rounded corners. Ideal for immersive views, large editors, and media preview.

```
Position classes: items-stretch
Container:        min-h-screen overflow-y-auto
```

```php
public static function overlayType(): string
{
    return 'fullscreen';
}
```

## Default Positions Per Type

Each type has a default position defined in `OverlayDefaults::typePositions()`:

```php
[
    'modal'        => 'center',
    'drawer'       => 'right',
    'confirm'      => 'center',
    'bottom-sheet' => 'bottom',
    'fullscreen'   => 'center',
]
```

## Overriding Position

You can override the default position by implementing `overlayPosition()`. The most common use case is a left-aligned drawer:

```php
class NavigationDrawer extends OverlayComponent
{
    public static function overlayType(): string
    {
        return 'drawer';
    }

    public static function overlayPosition(): string
    {
        return 'left';
    }

    public function render()
    {
        return view('livewire.overlays.navigation-drawer');
    }
}
```

When the position is `left`, the drawer slides in from the left side (`-translate-x-full`) instead of the right side (`translate-x-full`). See [animations.md](./animations.md) for details.

## Examples

Open a drawer from a button:

```html
<button x-on:click="$dispatch('kore:open', { name: 'overlays.user-settings' })">
    Settings
</button>
```

Open a confirm dialog:

```php
class DeleteConfirm extends OverlayComponent
{
    public int $itemId;

    public static function overlayType(): string
    {
        return 'confirm';
    }

    public static function overlaySize(): string
    {
        return 'sm';
    }

    public function mount(int $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function confirm(): void
    {
        Item::destroy($this->itemId);
        $this->closeWith(['item-deleted']);
    }

    public function render()
    {
        return view('livewire.overlays.delete-confirm');
    }
}
```

```html
<button x-on:click="$dispatch('kore:open', {
    name: 'overlays.delete-confirm',
    arguments: { itemId: {{ $item->id }} }
})">
    Delete
</button>
```
