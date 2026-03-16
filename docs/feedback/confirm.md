# Confirm Dialog

## Overview

Confirm dialogs reuse the overlay system. Calling `$this->confirm()` dispatches `kore:open` with a generic `kore-confirm-dialog` component that renders inside the overlay manager. No extra components to create.

## Basic Usage

```php
use KoreUi\Core\Concerns\InteractsWithFeedback;

class MyComponent extends Component
{
    use InteractsWithFeedback;

    public function delete(int $id): void
    {
        $this->confirm('Delete this record?')
            ->onConfirm('confirmDelete', [$id])
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        Record::destroy($id);
        $this->toast()->success('Record deleted')->send();
    }
}
```

## Types

The `type` controls the icon and confirm button color:

| Type | Icon | Confirm button |
|------|------|---------------|
| `question` (default) | `circle-help` | Primary |
| `warning` | `triangle-alert` | Destructive |
| `error` | `circle-x` | Destructive |
| `info` | `info` | Primary |

```php
$this->confirm('Discard changes?')
    ->type('warning')
    ->send();
```

## Description

Add context below the title:

```php
$this->confirm('Delete account permanently?')
    ->description('This action cannot be undone. All data will be erased.')
    ->type('error')
    ->send();
```

## Custom Button Text

Override the default "Confirmar" / "Cancelar" labels:

```php
$this->confirm('Publish article?')
    ->confirmText('Publish now')
    ->cancelText('Keep as draft')
    ->onConfirm('publish')
    ->send();
```

Defaults are configurable in `config/kore-ui.php` under `feedback.confirm.confirm_text` and `feedback.confirm.cancel_text`.

## Callbacks

### onConfirm

Called when the user clicks the confirm button:

```php
->onConfirm('methodName', [$param1, $param2])
```

### onCancel

Called when the user clicks the cancel button:

```php
->onCancel('keepEditing')
```

Both callbacks execute on the **caller component** (the component that called `$this->confirm()`). The system uses `Livewire.dispatch('kore:confirm-callback')` as a fallback if the caller component has been re-rendered.

## Persistent Mode

Prevent closing with Escape or click-away — the user must click a button:

```php
$this->confirm('Delete account permanently?')
    ->description('This action cannot be undone.')
    ->type('error')
    ->persistent()
    ->onConfirm('deleteAccount')
    ->send();
```

## Full Example

```php
$this->confirm('Discard unsaved changes?')
    ->description('Your changes will be lost.')
    ->type('warning')
    ->confirmText('Yes, discard')
    ->cancelText('Keep editing')
    ->onConfirm('discardChanges', [$this->draftId])
    ->onCancel('keepEditing')
    ->persistent()
    ->send();
```

## How It Works Internally

1. `$this->confirm(...)` creates a `Confirm` builder bound to the current component
2. `send()` dispatches `kore:open` with `name: 'kore-confirm-dialog'` and overlay attributes (`type: 'confirm'`)
3. The `OverlayManager` receives the event and mounts `ConfirmDialog`
4. `ConfirmDialog` extends `OverlayComponent` — it renders with the overlay animation, backdrop, and escape handling
5. On accept/reject, it dispatches `kore:confirm-callback` to the caller and calls `$this->close()`

## Configuration

In `config/kore-ui.php`:

```php
'feedback' => [
    'confirm' => [
        'size'               => 'md',        // overlay size
        'confirm_text'       => 'Confirmar', // default confirm button text
        'cancel_text'        => 'Cancelar',  // default cancel button text
        'closes_on_escape'   => true,
        'closes_on_click_away' => false,     // default: requires button click
    ],
],
```
