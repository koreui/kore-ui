# Toast

## Types

Each type has a default icon (Lucide), color token, and timeout:

| Type | Icon | Color | Auto-dismiss |
|------|------|-------|-------------|
| `success` | `circle-check` | `kore-success` | 5s |
| `error` | `circle-x` | `kore-destructive` | No (persistent) |
| `warning` | `triangle-alert` | `kore-warning` | 8s |
| `info` | `info` | `kore-info` | 5s |
| `question` | `circle-help` | `kore-primary` | No (persistent) |
| `loading` | `loader-circle` (spin) | `kore-muted` | No (persistent) |

```php
$this->toast()->success('Record saved')->send();
$this->toast()->error('Failed to save', 'Check required fields.')->send();
$this->toast()->warning('No connection')->send();
$this->toast()->info('New message', 'You have 3 unread messages.')->send();
$this->toast()->question('Discard changes?')->confirm('Yes', 'discard')->cancel('No')->send();
$this->toast()->loading('Importing file...')->send();
```

The second argument is an optional description that makes the toast expandable.

## Timeout and Persistent

```php
// Custom timeout (seconds)
$this->toast()->success('Done')->timeout(10)->send();

// Persistent — stays until manually dismissed
$this->toast()->info('Important notice')->persistent()->send();
```

## Positions

6 positions available. Default is `top-right` (configurable).

```php
$this->toast()->success('Saved')->position('bottom-left')->send();
```

Valid values: `top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right`.

Multiple toasts at different positions render in separate containers simultaneously.

## Actions

Inline action buttons that call Livewire methods:

```php
$this->toast()
    ->success('Item deleted')
    ->action('Undo', 'undoDelete', [42])
    ->action('View trash', 'showTrash')
    ->timeout(8)
    ->send();
```

## Confirm/Cancel (on Toast)

For quick yes/no decisions without opening a dialog:

```php
$this->toast()
    ->question('Discard changes?')
    ->confirm('Yes, discard', 'discard')
    ->cancel('No')
    ->send();
```

## Loading and Resolve

A toast that represents an in-progress operation and transforms in-place when complete:

```php
public function importCsv(): void
{
    // 1. Create loading toast — returns the ID
    $toastId = $this->toast()->loading('Importing file...')->send();

    try {
        $count = $this->processFile($this->file);

        // 2. Resolve with success — transforms the existing toast
        $this->toast()->resolve($toastId)->success("{$count} records imported")->send();
    } catch (\Exception $e) {
        // 2. Resolve with error
        $this->toast()->resolve($toastId)->error('Import failed', $e->getMessage())->send();
    }
}
```

Loading toasts are persistent, not dismissible, and don't group.

## Grouping

Identical toasts (same type + title) automatically merge into one with a counter badge:

```php
// Click 3 times → shows "Record saved (3)" instead of 3 separate toasts
$this->toast()->success('Record saved')->send();
```

Grouping is disabled automatically when a toast has actions, confirm/cancel options, or hooks.

```php
// Force individual (no grouping)
$this->toast()->success('Record saved')->noGroup()->send();
```

## Sole Mode

Clear all existing toasts before showing the new one:

```php
$this->toast()->info('Only this toast')->sole()->send();
```

## Hooks

Execute Livewire methods when a toast is closed or expires:

```php
$this->toast()
    ->info('Processing...')
    ->hook('close', 'onToastClosed')
    ->hook('timeout', 'onToastExpired', [42])
    ->timeout(5)
    ->send();
```

## Session Flash (viaSession)

Force session flash delivery instead of Livewire dispatch. Useful for redirects:

```php
$this->toast()->success('Welcome back')->viaSession()->send();
return redirect()->route('dashboard');
```

When using `kore_toast()` or `Kore::toast()` outside Livewire, session flash is used automatically.

## Expand/Collapse

Toasts with a description or actions auto-expand to show the content. Hovering can only *add* content, never remove it: an auto-expanded toast stays open after the mouse leaves.

```php
// Has description → auto-expands, and stays expanded
$this->toast()->success('Title', 'Description text')->send();

// Suppress auto-expand → starts collapsed, expands on hover, collapses on leave
$this->toast()->success('Title', 'Long description...')->expanded(false)->send();
```

Hover expansion is delayed so that moving the cursor across a toast doesn't snap it open and shut:

| Config | Default | Effect |
|---|---|---|
| `feedback.toast.expand_delay` | `150` ms | Wait before expanding on hover |
| `feedback.toast.collapse_delay` | `300` ms | Wait before collapsing on leave |

These delays only apply to toasts started with `expanded(false)` — an auto-expanded toast has nothing to collapse back to.

## Swipe to Dismiss

On touch devices, users can swipe a toast to dismiss it. Direction matches the nearest edge. Disabled for toasts with confirm/cancel options or when `dismissible` is false.

Configurable globally: `config('kore-ui.feedback.toast.swipe_to_dismiss')`.

## Pause on Hover

The progress bar and auto-dismiss timer pause when hovering over a toast and resume on mouse leave.

## Accessibility

- Toast container: `role="region"` with `aria-live="polite"`
- Error toasts: `role="alert"` (interrupts screen reader)
- Other toasts: `role="status"`
- Dismiss button: `aria-label="Cerrar notificacion"`
- `prefers-reduced-motion: reduce` disables spring easing and transitions

## Data Structure

The toast payload sent to Alpine:

```php
[
    'id'          => 'uuid-v4',
    'type'        => 'success',
    'title'       => 'Record saved',
    'description' => null,
    'timeout'     => 5,
    'position'    => 'top-right',
    'dismissible' => true,
    'sole'        => false,
    'noGroup'     => false,
    'expandable'  => false, // deprecated — removed in 2.0
    'autoExpand'  => false,
    'actions'     => [],
    'options'     => [],
    'hooks'       => [],
    'reference'   => 'livewire-component-id',
]
```

> **`expandable` is deprecated.** It duplicates what `description`, `actions` and `options`
> already say, and the library no longer reads it — the front end derives the expanded state
> with `isExpanded()`. It still ships so published templates that read it keep working, and it
> will be removed in 2.0. If you have a published toast template, stop relying on it.
