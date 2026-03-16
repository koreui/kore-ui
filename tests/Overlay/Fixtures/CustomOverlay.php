<?php

namespace KoreUi\Tests\Overlay\Fixtures;

use KoreUi\Overlay\OverlayComponent;

class CustomOverlay extends OverlayComponent
{
    public static function overlayType(): string
    {
        return 'drawer';
    }

    public static function overlaySize(): string
    {
        return 'lg';
    }

    public static function overlayPosition(): string
    {
        return 'right';
    }

    public static function closesOnClickAway(): bool
    {
        return false;
    }

    public static function closesOnEscape(): bool
    {
        return true;
    }

    public static function escapeClosesAll(): bool
    {
        return false;
    }

    public static function destroysOnClose(): bool
    {
        return true;
    }

    public static function backdropBlur(): bool
    {
        return true;
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <h2>Custom Drawer</h2>
        </div>
        HTML;
    }
}
