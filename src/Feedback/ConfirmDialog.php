<?php

namespace KoreUi\Feedback;

use KoreUi\Overlay\OverlayComponent;

class ConfirmDialog extends OverlayComponent
{
    public string $title = '';

    public ?string $description = null;

    public string $type = 'question';

    public string $confirmText = 'Confirmar';

    public string $cancelText = 'Cancelar';

    public array $onConfirm = [];

    public array $onCancel = [];

    public ?string $callerRef = null;

    public static function overlayType(): string
    {
        return 'confirm';
    }

    public static function overlaySize(): string
    {
        return config('kore-ui.feedback.confirm.size', 'md');
    }

    public static function closesOnClickAway(): bool
    {
        return config('kore-ui.feedback.confirm.closes_on_click_away', false);
    }

    public static function closesOnEscape(): bool
    {
        return config('kore-ui.feedback.confirm.closes_on_escape', true);
    }

    public static function destroysOnClose(): bool
    {
        return true;
    }

    /**
     * Accept the confirmation — execute onConfirm callback and close.
     */
    public function accept(): void
    {
        if (! empty($this->onConfirm)) {
            $this->executeCallback($this->onConfirm);
        }

        $this->close();
    }

    /**
     * Reject the confirmation — execute onCancel callback and close.
     */
    public function reject(): void
    {
        if (! empty($this->onCancel)) {
            $this->executeCallback($this->onCancel);
        }

        $this->close();
    }

    /**
     * Execute a callback on the caller component.
     * Uses Livewire dispatch as fallback when the caller may not be in DOM.
     */
    protected function executeCallback(array $callback): void
    {
        $method = $callback['method'] ?? null;
        $params = $callback['params'] ?? [];

        if (! $method) {
            return;
        }

        // Dispatch global event — the caller's InteractsWithFeedback trait
        // listens for kore:confirm-callback and filters by callerRef.
        $this->dispatch('kore:confirm-callback',
            method: $method,
            params: $params,
            callerRef: $this->callerRef ?? '',
        );
    }

    public function render()
    {
        return view('kore::feedback.confirm');
    }
}
