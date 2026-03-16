# Alpine Component: `KoreOverlay()`

The `KoreOverlay()` Alpine.js component powers the client-side behavior of the overlay system. It manages visibility, stacking, transitions, positioning, and close behavior.

## Registration

The component is defined in `resources/js/overlay.js` and registered in `resources/js/index.js`:

```js
import KoreOverlay from './overlay.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('KoreOverlay', KoreOverlay);
});
```

In the Blade template, it is used as:

```html
<div x-data="KoreOverlay()">
```

Your application must import `resources/js/index.js` (or the package entry point) before Alpine initializes.

## State Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `show` | `boolean` | `false` | Whether the overlay container (including backdrop) is visible |
| `contentVisible` | `boolean` | `false` | Whether the active overlay panel is visible (controls panel transitions) |
| `current` | `string\|null` | `null` | The ID of the currently active overlay |
| `stack` | `array` | `[]` | IDs of stacked (background) overlays, LIFO order |
| `overlaySize` | `string\|null` | `null` | Current overlay's size CSS classes |
| `backdropBlur` | `boolean` | `false` | Whether the backdrop has blur enabled |
| `positionClasses` | `string` | `''` | Flexbox positioning CSS classes for the current overlay |
| `transitioning` | `boolean` | `false` | Lock to prevent concurrent stack transitions |
| `listeners` | `array` | `[]` | Livewire event listener cleanup functions |

## Lifecycle

### `init()`

Called when the Alpine component initializes. Registers two Livewire event listeners:

- `kore:overlay-changed` -- dispatched by `OverlayManager` after adding a new overlay. Calls `activate(id)`.
- `kore:close` -- dispatched by `HasOverlayBehavior::close()`, `closeAll()`, `skipBack()`. Calls `closeOverlay()` with the provided parameters.

### `destroy()`

Called when the Alpine component is destroyed. Cleans up all registered Livewire event listeners.

## Key Methods

### `activate(id, skip = false)`

Handles showing an overlay, either as the first overlay or stacked on top of another.

**First overlay (no current):**
1. Sets `current = id` and `show = true` and `contentVisible = true`.
2. Calls `updateAttributes(id)` to set positioning and size.
3. Adds `overflow-y-hidden` to `document.body` to lock page scroll.
4. Focuses the first `[autofocus]` element after a 50ms delay.

**Stacking (current exists):**
1. If `skip` is `false`, pushes the current ID onto `stack`.
2. Sets `transitioning = true` and `contentVisible = false` to trigger the leave transition.
3. After 300ms (to let the leave animation complete):
   - Sets `current = id` and `contentVisible = true`.
   - Calls `updateAttributes(id)`.
   - Sets `transitioning = false`.
   - Focuses the first `[autofocus]` element.

The `skip` parameter is `true` when re-activating a previous overlay from the stack (to avoid re-pushing it).

### `closeOverlay(force = false, skipPrevious = 0, destroySkipped = false)`

Handles closing the current overlay with optional stack navigation.

1. If `dispatchesCloseEvent` is enabled, dispatches `kore:closed` with the overlay ID.
2. If `destroysOnClose` is enabled, calls `$wire.destroyOverlay(currentId)` to remove server state.
3. Pops and optionally destroys `skipPrevious` overlays from the stack.
4. Pops the next previous overlay:
   - If it exists and `force` is `false`, calls `activate(previousId, true)` to show it.
   - Otherwise, calls `toggle(false)` to close everything.

### `closeOnEscape()`

Handler for `keydown.escape.window`. Checks the current overlay's `closesOnEscape` attribute.

1. If `closesOnEscape` is `false`, does nothing.
2. Dispatches `kore:before-close` to the overlay component with `{ id, trigger: 'escape', closing: true }`.
3. If `params.closing` was set to `false` by a listener, aborts.
4. If `escapeClosesAll` is `true`, calls `closeOverlay(true)` (force close entire stack).
5. Otherwise, calls `closeOverlay(false)` (close only current, return to previous).

### `closeOnClickAway()`

Handler for clicking the backdrop. Checks `closesOnClickAway`.

1. If `closesOnClickAway` is `false`, does nothing.
2. Dispatches `kore:before-close` with `{ id, trigger: 'click-away', closing: true }`.
3. If `params.closing` was set to `false`, aborts.
4. Calls `closeOverlay(false)` -- click-away always respects the stack (not forced).

### `toggle(show)`

Controls the visibility of the entire overlay system.

**show = true:**
- Sets `show = true`.
- Locks body scroll with `overflow-y-hidden`.

**show = false:**
- Sets `contentVisible = false` to trigger the panel leave animation.
- Removes `overflow-y-hidden` from body.
- After 300ms: sets `show = false`, clears `current` and `stack`, calls `$wire.resetState()` to clean up the server-side `OverlayManager`.

### `updateAttributes(id)`

Reads the overlay attributes from Livewire state and updates Alpine properties:

- `overlaySize` from `sizeClass`
- `backdropBlur` from `backdropBlur`
- `positionClasses` from `getPositionClasses(type, position)`

### `getPositionClasses(type, position)`

Returns the flexbox CSS classes for positioning the overlay panel:

| Type | Position | Classes |
|------|----------|---------|
| `drawer` | `right` | `items-stretch justify-end` |
| `drawer` | `left` | `items-stretch justify-start` |
| `bottom-sheet` | any | `items-end justify-center p-4 sm:p-0` |
| `fullscreen` | any | `items-stretch` |
| `modal` / `confirm` | any | `items-end justify-center p-4 text-center sm:items-center sm:p-0` |

### `attr(id)`

Reads the `overlayAttributes` for a given overlay ID from Livewire state:

```js
attr(id) {
    const overlays = this.$wire.get('overlays');
    return overlays?.[id]?.overlayAttributes ?? null;
}
```

### `getComponentName(id)`

Returns the Livewire component name for a given overlay ID:

```js
getComponentName(id) {
    const overlays = this.$wire.get('overlays');
    return overlays?.[id]?.name ?? null;
}
```

Used by `closeOnEscape()` and `closeOnClickAway()` to dispatch `kore:before-close` to the correct component.

### `focusFirst()`

Finds the first element with the `autofocus` attribute inside the overlay and focuses it:

```js
focusFirst() {
    const el = this.$el.querySelector('[autofocus]');
    if (el) el.focus();
}
```

## Livewire Event Listeners

| Event | Source | Action |
|-------|--------|--------|
| `kore:overlay-changed` | `OverlayManager::openOverlay()` | Calls `activate(id)` |
| `kore:close` | `HasOverlayBehavior::close()`, `closeAll()`, `skipBack()` | Calls `closeOverlay(force, skipPrevious, destroySkipped)` |

## The 300ms Transition Timing

The value `300` appears in two places:

1. **`activate()` stacking delay** -- After setting `contentVisible = false`, waits 300ms before showing the new overlay. This matches the longest leave animation (`ease-in duration-200` is 200ms; the extra 100ms provides a buffer).

2. **`toggle(false)` close delay** -- After hiding the content, waits 300ms before fully resetting state and calling `$wire.resetState()`.

## The `transitioning` Lock

When stacking overlays, `transitioning` is set to `true` during the 300ms transition period. Any calls to `activate()` during this time are ignored. This prevents rapid-fire `kore:open` events from corrupting the stack state or causing visual glitches.

## Focus Trapping

The Blade template uses Alpine's `x-trap.noscroll.inert` directive:

```html
x-trap.noscroll.inert="show && current === '{{ $id }}'"
```

This traps keyboard focus within the active overlay, prevents scroll on the trapped area, and applies `inert` to everything outside. Focus trapping activates only for the current overlay.
