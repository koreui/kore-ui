# Overlay System - Getting Started

## What is the Overlay System?

The kore-ui overlay system is a unified Livewire-based system for rendering **modals**, **drawers**, **confirm dialogs**, **bottom sheets**, and **fullscreen overlays**. It uses a single manager component that handles rendering, stacking, animations, and lifecycle for all overlay types.

All overlays share the same infrastructure: a `<livewire:kore-overlay-manager />` tag in your layout, an Alpine.js component for client-side behavior, and PHP classes that extend `OverlayComponent`.

## Prerequisites

1. The `kore-ui` package must be installed and its service provider registered.
2. Import the kore-ui CSS (includes the design token theme):
   ```css
   @import '../../vendor/kore-ui/kore-ui/resources/css/kore-theme.css';
   ```
3. Import the kore-ui JavaScript (registers the Alpine component):
   ```js
   import '../../vendor/kore-ui/kore-ui/resources/js/index.js';
   ```
4. Livewire and Alpine.js must be loaded in your application (Livewire 3+ ships Alpine by default).

## Adding the Overlay Manager to Your Layout

Place the manager component in your main layout, typically before the closing `</body>` tag:

```html
<!DOCTYPE html>
<html>
<head>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}

    <livewire:kore-overlay-manager />

    @livewireScripts
</body>
</html>
```

The manager renders a single fixed container with a backdrop and handles mounting/unmounting overlay components as needed. You only need **one** manager per page.

## Creating Your First Overlay Component

Create a Livewire component that extends `OverlayComponent`:

```php
<?php

namespace App\Livewire\Overlays;

use KoreUi\Overlay\OverlayComponent;

class EditProfile extends OverlayComponent
{
    public string $name = '';
    public string $email = '';

    public function mount(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function save(): void
    {
        // Save logic...
        $this->close();
    }

    public function render()
    {
        return view('livewire.overlays.edit-profile');
    }
}
```

The corresponding Blade view (`livewire/overlays/edit-profile.blade.php`):

```html
<div class="p-6">
    <h2 class="text-lg font-semibold">Edit Profile</h2>

    <form wire:submit="save" class="mt-4 space-y-4">
        <input type="text" wire:model="name" class="w-full border rounded px-3 py-2" />
        <input type="email" wire:model="email" class="w-full border rounded px-3 py-2" />

        <div class="flex justify-end gap-2">
            <button type="button" wire:click="close" class="px-4 py-2 text-sm">
                Cancel
            </button>
            <button type="submit" class="px-4 py-2 text-sm bg-kore-primary text-kore-primary-fg rounded">
                Save
            </button>
        </div>
    </form>
</div>
```

## Opening an Overlay

Dispatch the `kore:open` event with the Livewire component name:

```html
<button
    x-on:click="$dispatch('kore:open', { name: 'overlays.edit-profile' })"
>
    Edit Profile
</button>
```

The `name` value is the standard Livewire component name (dot-notation or kebab-case). The `ComponentResolver` validates that the class implements the `Overlayable` interface.

## Passing Arguments

Use the `arguments` object to pass data to the component's `mount()` method:

```html
<button
    x-on:click="$dispatch('kore:open', {
        name: 'overlays.edit-profile',
        arguments: { userId: {{ $user->id }} }
    })"
>
    Edit Profile
</button>
```

Arguments are forwarded directly to the Livewire component, so `arguments: { userId: 5 }` calls `mount(int $userId)` with `5`.

## Closing an Overlay

From PHP (inside the overlay component):

```php
// Close the current overlay
$this->close();
```

From Blade (button in the overlay template):

```html
<button wire:click="close">Cancel</button>
```

The `close()` method dispatches `kore:close`, which the Alpine component handles by either returning to the previous overlay in the stack or hiding everything.

## Complete Minimal Example

**1. Create the overlay component:**

```php
// app/Livewire/Overlays/Welcome.php
<?php

namespace App\Livewire\Overlays;

use KoreUi\Overlay\OverlayComponent;

class Welcome extends OverlayComponent
{
    public string $message;

    public function mount(string $message = 'Hello!'): void
    {
        $this->message = $message;
    }

    public function render()
    {
        return view('livewire.overlays.welcome');
    }
}
```

**2. Create the view:**

```html
<!-- resources/views/livewire/overlays/welcome.blade.php -->
<div class="p-6 text-center">
    <p class="text-lg">{{ $message }}</p>
    <button wire:click="close" class="mt-4 px-4 py-2 bg-kore-primary text-kore-primary-fg rounded">
        Got it
    </button>
</div>
```

**3. Open it from any page:**

```html
<button
    x-on:click="$dispatch('kore:open', {
        name: 'overlays.welcome',
        arguments: { message: 'Welcome to kore-ui!' }
    })"
>
    Show Welcome
</button>
```

That is all you need. The overlay manager handles rendering, animation, backdrop, escape key, click-away, and cleanup automatically.
