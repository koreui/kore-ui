# Feedback System - Getting Started

## What is the Feedback System?

The kore-ui feedback system provides **toast notifications** and **confirm dialogs** for any Laravel application with Livewire. It's the second module of kore-ui, built on top of the existing overlay infrastructure.

- **Toasts** — Temporary notifications with 6 types, actions, grouping, loading/resolve, swipe-to-dismiss
- **Confirm dialogs** — Confirmation prompts that reuse the overlay system with callbacks

Both are invoked from a single trait: `InteractsWithFeedback`.

## Prerequisites

1. The `kore-ui` package installed with its overlay system working.
2. CSS and JS imports already configured (same as overlay setup).
3. `blade-ui-kit/blade-icons` and `mallardduck/blade-lucide-icons` are included as dependencies.

## Adding the Feedback Manager

Add `<livewire:kore-feedback-manager />` to your layout, alongside the overlay manager:

```html
<body>
    {{ $slot }}

    <livewire:kore-overlay-manager />
    <livewire:kore-feedback-manager />

    @livewireScripts
</body>
```

The feedback manager handles rendering all toasts. It reads session flash data for toasts dispatched from controllers/redirects.

## Adding the Trait

Add `InteractsWithFeedback` to any Livewire component:

```php
<?php

namespace App\Livewire;

use KoreUi\Core\Concerns\InteractsWithFeedback;
use Livewire\Component;

class MyComponent extends Component
{
    use InteractsWithFeedback;

    public function save(): void
    {
        // ... save logic ...
        $this->toast()->success('Saved successfully')->send();
    }

    public function delete(int $id): void
    {
        $this->confirm('Delete this record?')
            ->onConfirm('confirmDelete', [$id])
            ->send();
    }

    public function confirmDelete(int $id): void
    {
        // ... delete logic ...
        $this->toast()->success('Record deleted')->send();
    }
}
```

## Using Outside Livewire

From controllers, middleware, or anywhere without a Livewire component:

```php
// Helper function
kore_toast()->success('Created successfully')->send();

// Facade
use KoreUi\Facades\Kore;
Kore::toast()->success('Created successfully')->send();
```

These automatically use `session()->flash()` so the toast appears after the redirect.

## Configuration

Publish and customize in `config/kore-ui.php`:

```php
'feedback' => [
    'toast' => [
        'position'         => 'top-right',   // top-left|top-center|top-right|bottom-left|bottom-center|bottom-right
        'timeout'          => 5,             // seconds, 0 = persistent
        'dismissible'      => true,
        'max_visible'      => 5,
        'swipe_to_dismiss' => true,
    ],
    'confirm' => [
        'size'               => 'md',
        'confirm_text'       => 'Confirmar',
        'cancel_text'        => 'Cancelar',
        'closes_on_escape'   => true,
        'closes_on_click_away' => false,
    ],
],
```
