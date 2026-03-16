<?php

namespace KoreUi\Overlay;

use InvalidArgumentException;
use KoreUi\Core\Support\ComponentResolver;
use KoreUi\Overlay\Config\OverlayDefaults;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class OverlayManager extends Component
{
    #[Locked]
    public ?string $current = null;

    #[Locked]
    public array $overlays = [];

    #[On('kore:open')]
    public function openOverlay(string $name, array $arguments = [], array $overlayAttributes = []): void
    {
        $class = ComponentResolver::resolve($name);

        $id = md5($name.serialize($arguments).uniqid('', true));

        // Resolve effective type/position/size — overrides take priority
        $effectiveType = $overlayAttributes['type'] ?? $class::overlayType();
        $effectivePosition = $overlayAttributes['position'] ?? $class::overlayPosition();
        $effectiveSize = $overlayAttributes['size'] ?? $class::overlaySize();

        $attributes = array_merge([
            'type' => $effectiveType,
            'size' => $effectiveSize,
            'sizeClass' => OverlayDefaults::sizeClass($effectiveSize),
            'position' => $effectivePosition,
            'closesOnClickAway' => $class::closesOnClickAway(),
            'closesOnEscape' => $class::closesOnEscape(),
            'escapeClosesAll' => $class::escapeClosesAll(),
            'dispatchesCloseEvent' => $class::dispatchesCloseEvent(),
            'destroysOnClose' => $class::destroysOnClose(),
            'backdropBlur' => $class::backdropBlur(),
            'animation' => OverlayDefaults::animation($effectiveType, $effectivePosition),
            'containerClass' => OverlayDefaults::containerClass($effectiveType),
        ], $overlayAttributes);

        $this->overlays[$id] = [
            'name' => $name,
            'arguments' => $arguments,
            'overlayAttributes' => $attributes,
        ];

        $this->current = $id;

        $this->dispatch('kore:overlay-changed', id: $id);
    }

    #[On('kore:destroy')]
    public function destroyOverlay(string $id): void
    {
        unset($this->overlays[$id]);

        if ($this->current === $id) {
            $this->current = null;
        }
    }

    public function resetState(): void
    {
        $this->overlays = [];
        $this->current = null;
    }

    public function render()
    {
        return view('kore::overlay.manager');
    }
}
