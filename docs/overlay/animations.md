# Overlay Animations

Each overlay type has its own enter/leave animation. Animations are applied via Alpine.js `x-transition` directives in the Blade template.

## Animation System Overview

Animations are defined in `OverlayDefaults::typeAnimations()`. Each type has an `enter` and `leave` object, each containing:

- `from` -- CSS classes applied at the start of the transition
- `to` -- CSS classes applied at the end of the transition
- `duration` -- timing/easing classes

These map directly to Alpine's `x-transition` directives:

```html
x-transition:enter="{{ $anim['enter']['duration'] }}"
x-transition:enter-start="{{ $anim['enter']['from'] }}"
x-transition:enter-end="{{ $anim['enter']['to'] }}"
x-transition:leave="{{ $anim['leave']['duration'] }}"
x-transition:leave-start="{{ $anim['leave']['from'] }}"
x-transition:leave-end="{{ $anim['leave']['to'] }}"
```

## Animation Classes Per Type

### Modal

Scale up with a slight vertical translate on mobile, pure scale on desktop.

```
Enter:
  from:     opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95
  to:       opacity-100 translate-y-0 sm:scale-100
  duration: ease-out duration-300

Leave:
  from:     opacity-100 translate-y-0 sm:scale-100
  to:       opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95
  duration: ease-in duration-200
```

### Drawer (Right -- default)

Slides in from the right edge.

```
Enter:
  from:     opacity-0 translate-x-full
  to:       opacity-100 translate-x-0
  duration: ease-out duration-300

Leave:
  from:     opacity-100 translate-x-0
  to:       opacity-0 translate-x-full
  duration: ease-in duration-200
```

### Drawer (Left)

Slides in from the left edge. This is position-aware -- when `position` is `left`, the animation automatically adjusts.

```
Enter:
  from:     opacity-0 -translate-x-full
  to:       opacity-100 translate-x-0
  duration: ease-out duration-300

Leave:
  from:     opacity-100 translate-x-0
  to:       opacity-0 -translate-x-full
  duration: ease-in duration-200
```

### Confirm

Quick scale animation, no translate.

```
Enter:
  from:     opacity-0 scale-95
  to:       opacity-100 scale-100
  duration: ease-out duration-200

Leave:
  from:     opacity-100 scale-100
  to:       opacity-0 scale-95
  duration: ease-in duration-150
```

### Bottom Sheet

Slides up from the bottom.

```
Enter:
  from:     opacity-0 translate-y-full
  to:       opacity-100 translate-y-0
  duration: ease-out duration-300

Leave:
  from:     opacity-100 translate-y-0
  to:       opacity-0 translate-y-full
  duration: ease-in duration-200
```

### Fullscreen

Simple fade.

```
Enter:
  from:     opacity-0
  to:       opacity-100
  duration: ease-out duration-200

Leave:
  from:     opacity-100
  to:       opacity-0
  duration: ease-in duration-150
```

## Position-Aware Animations

The `OverlayDefaults::animation()` method accepts both `type` and `position`. It adjusts the animation for position-sensitive types:

```php
public static function animation(string $type, string $position = 'center'): array
```

Currently, position awareness applies to **drawers**:

- `position: 'right'` (default) -- uses `translate-x-full` (slide from right)
- `position: 'left'` -- uses `-translate-x-full` (slide from left)

All other types ignore the position parameter for animation purposes.

## Backdrop Animation

The backdrop has its own fixed animation, separate from the overlay panel:

```
Enter: ease-out duration-300, opacity 0 -> 100
Leave: ease-in duration-200, opacity 100 -> 0
```

This is defined directly in the Blade template and is the same for all overlay types.

## Custom Animations via `overlayAttributes`

Override the animation at open time:

```html
<button x-on:click="$dispatch('kore:open', {
    name: 'overlays.notification',
    overlayAttributes: {
        animation: {
            enter: {
                from: 'opacity-0 -translate-y-full',
                to: 'opacity-100 translate-y-0',
                duration: 'ease-out duration-500'
            },
            leave: {
                from: 'opacity-100 translate-y-0',
                to: 'opacity-0 -translate-y-full',
                duration: 'ease-in duration-300'
            }
        }
    }
})">
    Slide from Top
</button>
```

When you override `type` or `position` via `overlayAttributes`, the animation re-computes automatically. You only need to provide an explicit `animation` override if you want something completely custom. See [runtime-overrides.md](./runtime-overrides.md) for the full priority chain.
