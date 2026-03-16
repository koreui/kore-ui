# Runtime Overrides with `overlayAttributes`

The `overlayAttributes` parameter in the `kore:open` event lets you override any overlay attribute at open time, without modifying the component class.

## Basic Usage

Pass `overlayAttributes` as the third parameter in the `kore:open` event:

```html
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.edit-form',
    arguments: { userId: 5 },
    overlayAttributes: { size: 'lg', backdropBlur: true }
})">
    Edit (compact with blur)
</button>
```

## How It Works

The `OverlayManager::openOverlay()` method builds the overlay attributes in this order:

1. **Resolve effective values** for `type`, `position`, and `size` -- `overlayAttributes` overrides take priority over the component's static methods.
2. **Build the base attributes** from the component's static methods and the effective values.
3. **Merge `overlayAttributes`** on top using `array_merge()`, so any explicitly provided key wins.

### Derived Attribute Re-computation

When you override `type`, `position`, or `size`, the derived attributes are **automatically re-computed** from the effective values:

- Override `type` --> `animation` and `containerClass` re-compute for the new type
- Override `position` --> `animation` re-computes for the new position (e.g., left drawer)
- Override `size` --> `sizeClass` re-computes for the new size

You can still override the derived attributes explicitly, and they take final priority:

```html
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.my-component',
    overlayAttributes: {
        type: 'drawer',
        animation: {
            enter: { from: 'opacity-0', to: 'opacity-100', duration: 'ease-out duration-500' },
            leave: { from: 'opacity-100', to: 'opacity-0', duration: 'ease-in duration-300' }
        }
    }
})">
    Drawer with custom animation
</button>
```

Here, `type: 'drawer'` re-computes `containerClass` and would re-compute `animation`, but the explicit `animation` override takes final precedence.

## Priority Chain

```
config defaults (kore-ui.php)
    --> component static methods (overlayType(), overlaySize(), etc.)
        --> overlayAttributes (passed at open time)
```

Each level overrides the previous. The component class defines the "design-time" defaults; `overlayAttributes` provides "call-site" overrides.

## Overridable Attributes

| Attribute | Type | Description |
|-----------|------|-------------|
| `type` | `string` | Overlay type (triggers re-computation of `animation`, `containerClass`) |
| `size` | `string` | Size key (triggers re-computation of `sizeClass`) |
| `sizeClass` | `string` | CSS max-width classes (overrides computed value) |
| `position` | `string` | Position (triggers re-computation of `animation`) |
| `closesOnClickAway` | `bool` | Close on backdrop click |
| `closesOnEscape` | `bool` | Close on Escape key |
| `escapeClosesAll` | `bool` | Escape force-closes entire stack |
| `dispatchesCloseEvent` | `bool` | Emit `kore:closed` on close |
| `destroysOnClose` | `bool` | Destroy component state on close |
| `backdropBlur` | `bool` | Backdrop blur effect |
| `animation` | `array` | Animation enter/leave classes |
| `containerClass` | `string` | Container CSS classes |

## Use Cases

### Same Component, Different Types

Open the same component as a modal on desktop or a bottom sheet on mobile:

```html
<!-- Desktop trigger -->
<button
    class="hidden sm:block"
    x-on:click="$dispatch('kore:open', {
        component: 'overlays.picker',
        overlayAttributes: { type: 'modal', size: 'lg' }
    })"
>
    Choose Item
</button>

<!-- Mobile trigger -->
<button
    class="sm:hidden"
    x-on:click="$dispatch('kore:open', {
        component: 'overlays.picker',
        overlayAttributes: { type: 'bottom-sheet' }
    })"
>
    Choose Item
</button>
```

### Dynamic Size Based on Context

```html
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.data-viewer',
    arguments: { tableId: 1 },
    overlayAttributes: { size: '3xl' }
})">
    View Small Table
</button>

<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.data-viewer',
    arguments: { tableId: 2 },
    overlayAttributes: { size: '7xl' }
})">
    View Large Table
</button>
```

### Override Behavior Per Call Site

```html
<!-- Normal: can be dismissed easily -->
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.feedback-form'
})">
    Give Feedback
</button>

<!-- Important: cannot be clicked away, notify on close -->
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.feedback-form',
    overlayAttributes: {
        closesOnClickAway: false,
        dispatchesCloseEvent: true,
        backdropBlur: true
    }
})">
    Required Feedback
</button>
```

### Left Drawer Override

Open a normally right-aligned drawer on the left side:

```html
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.navigation',
    overlayAttributes: { type: 'drawer', position: 'left' }
})">
    Open Nav
</button>
```

The `position: 'left'` override causes `animation` to re-compute with `-translate-x-full` (slide from left) and the Alpine positioning to use `justify-start`.
