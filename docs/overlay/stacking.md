# Overlay Stacking

The overlay system supports opening overlays on top of other overlays. The stack follows a LIFO (last-in, first-out) model managed by the Alpine component.

## How the Stack Works

When you open an overlay while another is already visible:

1. The current overlay ID is pushed onto the `stack` array.
2. The current content transitions out (300ms).
3. The new overlay transitions in and becomes the active `current`.
4. The backdrop remains visible throughout -- no flicker.

When you close the top overlay:

1. If the stack is not empty, the previous overlay ID is popped and re-activated.
2. If the stack is empty, everything closes (backdrop fades out, body scroll restored).

Underlying overlays in the stack **keep their Livewire component state**. They are still mounted on the server; only the Alpine visibility toggles.

## Opening Nested Overlays

From inside an overlay, dispatch `kore:open` the same way as from a page:

```html
<!-- Inside an overlay component's Blade view -->
<button x-on:click="$dispatch('kore:open', {
    component: 'overlays.nested-detail',
    arguments: { itemId: {{ $itemId }} }
})">
    View Details
</button>
```

You can also dispatch from PHP inside an overlay:

```php
public function openNested(): void
{
    $this->dispatch('kore:open', component: 'overlays.nested-detail', arguments: ['itemId' => $this->itemId]);
}
```

## Navigation Methods

All methods are available in your `OverlayComponent` subclass via the `HasOverlayBehavior` trait.

### `close()`

Closes the current overlay and shows the previous one in the stack. If there is no previous overlay, everything closes.

```php
public function save(): void
{
    // Save logic...
    $this->close();
}
```

### `closeAll()`

Force-closes the entire stack. All overlays are dismissed, the backdrop hides, and body scroll is restored.

```php
public function done(): void
{
    // Close everything, regardless of stack depth
    $this->closeAll();
}
```

### `skipBack(int $count = 1, bool $destroy = false)`

Closes the current overlay and skips `$count` previous overlays in the stack before navigating back. Useful for "wizard" flows where you want to jump back multiple steps.

- `$count` -- how many previous overlays to skip over
- `$destroy` -- if `true`, the skipped overlays are destroyed (their Livewire state is removed)

```php
// Stack: [A, B, C] -- C is current
// After skipBack(1): C closes, B is skipped, A becomes current
$this->skipBack(1);

// With destroy: B's component state is cleaned up
$this->skipBack(1, destroy: true);
```

### `closeWith(array $events)`

Dispatches one or more Livewire events and then closes the overlay. This is the primary way to communicate results back to the parent page.

```php
public function confirm(): void
{
    $this->closeWith(['order-confirmed']);
}
```

#### Event Formats

The `$events` array supports four formats:

**1. Global event (string)**

```php
$this->closeWith(['user-updated']);
```
Dispatches: `Livewire.dispatch('user-updated')`

**2. Global event with parameters (array)**

```php
$this->closeWith([
    ['user-updated', ['userId' => $this->userId, 'name' => $this->name]],
]);
```
Dispatches: `Livewire.dispatch('user-updated', { userId: 5, name: 'John' })`

**3. Targeted event (class => string)**

```php
use App\Livewire\UserList;

$this->closeWith([
    UserList::class => 'refresh',
]);
```
Dispatches: `Livewire.dispatchTo('user-list', 'refresh')`

**4. Targeted event with parameters (class => array)**

```php
use App\Livewire\UserList;

$this->closeWith([
    UserList::class => ['user-saved', ['userId' => $this->userId]],
]);
```
Dispatches: `Livewire.dispatchTo('user-list', 'user-saved', { userId: 5 })`

You can mix formats in a single call:

```php
$this->closeWith([
    'global-notification',
    UserList::class => 'refresh',
    ['audit-log', ['action' => 'updated']],
]);
```

## Mixed-Type Stacking

Overlays of different types can be stacked freely. The position, size, and animation update automatically when the active overlay changes:

```html
<!-- Page: open a drawer -->
<button x-on:click="$dispatch('kore:open', { component: 'overlays.settings-drawer' })">
    Settings
</button>
```

```html
<!-- Inside settings-drawer: open a confirm dialog -->
<button x-on:click="$dispatch('kore:open', { component: 'overlays.reset-confirm' })">
    Reset to Defaults
</button>
```

```html
<!-- Inside reset-confirm: open a fullscreen preview -->
<button x-on:click="$dispatch('kore:open', { component: 'overlays.preview-fullscreen' })">
    Preview Changes
</button>
```

Stack state: `[settings-drawer, reset-confirm]` with `preview-fullscreen` as current. Closing the fullscreen returns to the confirm; closing the confirm returns to the drawer.

## Example: Multi-Step Wizard

```php
class WizardStep1 extends OverlayComponent
{
    public function next(): void
    {
        $this->dispatch('kore:open', component: 'overlays.wizard-step2', arguments: ['data' => $this->formData]);
    }

    public function render()
    {
        return view('livewire.overlays.wizard-step1');
    }
}

class WizardStep2 extends OverlayComponent
{
    public function back(): void
    {
        // Close step 2, return to step 1
        $this->close();
    }

    public function finish(): void
    {
        // Save and close everything
        $this->closeAll();
    }

    public function render()
    {
        return view('livewire.overlays.wizard-step2');
    }
}
```

## Example: Skip Back in a Deep Stack

```php
// Stack: [Step1, Step2, Step3] -- Step3 is current

class WizardStep3 extends OverlayComponent
{
    public function backToStart(): void
    {
        // Skip Step2, go directly to Step1
        $this->skipBack(1);
    }

    public function backToStartAndCleanUp(): void
    {
        // Skip Step2 and destroy its state, go to Step1
        $this->skipBack(1, destroy: true);
    }

    public function render()
    {
        return view('livewire.overlays.wizard-step3');
    }
}
```
