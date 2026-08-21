<?php

namespace KoreUi\Core\Concerns;

trait HasOverlayBehavior
{
    public static function overlayType(): string
    {
        return config('kore-ui.overlay.defaults.type', 'modal');
    }

    /**
     * El nombre accesible del diálogo, para el `aria-label` del `role="dialog"`.
     *
     * Devuelve `null` por defecto a propósito: el nombre lo sabe el componente
     * que se abre, no la librería, y un texto genérico como «Diálogo» no aporta
     * nada sobre el «diálogo» que ya anuncia el rol.
     *
     * Sobrescríbelo en tu overlay, o pásalo al abrirlo:
     *
     *     public static function overlayTitle(): ?string
     *     {
     *         return 'Editar cliente';
     *     }
     *
     *     // o, desde quien lo abre:
     *     $this->dispatch('kore:open', name: 'editar-cliente',
     *         overlayAttributes: ['title' => 'Editar '.$cliente->nombre]);
     */
    public static function overlayTitle(): ?string
    {
        return null;
    }

    public static function overlaySize(): string
    {
        return config('kore-ui.overlay.defaults.size', '2xl');
    }

    public static function overlayPosition(): string
    {
        return config('kore-ui.overlay.defaults.position', 'center');
    }

    public static function closesOnClickAway(): bool
    {
        return config('kore-ui.overlay.defaults.close_on_click_away', true);
    }

    public static function closesOnEscape(): bool
    {
        return config('kore-ui.overlay.defaults.close_on_escape', true);
    }

    public static function escapeClosesAll(): bool
    {
        return config('kore-ui.overlay.defaults.escape_closes_all', true);
    }

    public static function dispatchesCloseEvent(): bool
    {
        return config('kore-ui.overlay.defaults.dispatch_close_event', false);
    }

    public static function destroysOnClose(): bool
    {
        return config('kore-ui.overlay.defaults.destroy_on_close', false);
    }

    public static function backdropBlur(): bool
    {
        return config('kore-ui.overlay.defaults.backdrop_blur', false);
    }

    /**
     * Close this overlay.
     */
    public function close(): void
    {
        $this->dispatch('kore:close');
    }

    /**
     * Force close all overlays in the stack.
     */
    public function closeAll(): void
    {
        $this->dispatch('kore:close', force: true);
    }

    /**
     * Close this overlay and emit events to other components.
     *
     * Supported event formats:
     *   ['eventName']
     *   [['eventName', ['param1', 'param2']]]
     *   [ComponentClass::class => 'eventName']
     *   [ComponentClass::class => ['eventName', ['param1', 'param2']]]
     */
    public function closeWith(array $events): void
    {
        foreach ($events as $target => $event) {
            if (is_numeric($target)) {
                // Global event
                if (is_array($event)) {
                    $this->dispatch($event[0], ...$event[1] ?? []);
                } else {
                    $this->dispatch($event);
                }
            } else {
                // Targeted event
                if (is_array($event)) {
                    $this->dispatch($event[0], ...$event[1] ?? [])->to($target);
                } else {
                    $this->dispatch($event)->to($target);
                }
            }
        }

        $this->close();
    }

    /**
     * Skip N previous overlays in the stack before closing.
     */
    public function skipBack(int $count = 1, bool $destroy = false): void
    {
        $this->dispatch('kore:close', skipPrevious: $count, destroySkipped: $destroy);
    }
}
