<?php

namespace KoreUi\Core\Contracts;

interface Overlayable
{
    /**
     * The overlay type: modal, drawer, confirm, bottom-sheet, fullscreen.
     */
    public static function overlayType(): string;

    /**
     * The overlay size: sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl.
     */
    public static function overlaySize(): string;

    /**
     * The overlay position: center, right, left, bottom, top.
     */
    public static function overlayPosition(): string;

    /**
     * Whether clicking outside the overlay closes it.
     */
    public static function closesOnClickAway(): bool;

    /**
     * Whether pressing Escape closes the overlay.
     */
    public static function closesOnEscape(): bool;

    /**
     * Whether Escape closes all overlays in the stack (force close).
     */
    public static function escapeClosesAll(): bool;

    /**
     * Whether to dispatch a 'kore:closed' event when closing.
     */
    public static function dispatchesCloseEvent(): bool;

    /**
     * Whether to destroy the component state when it closes.
     */
    public static function destroysOnClose(): bool;

    /**
     * Whether the backdrop uses blur effect.
     */
    public static function backdropBlur(): bool;
}
