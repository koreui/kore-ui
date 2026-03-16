<?php

namespace KoreUi\Core\Concerns;

use KoreUi\Feedback\Confirm;
use KoreUi\Feedback\Toast;
use Livewire\Attributes\On;

trait InteractsWithFeedback
{
    /**
     * Create a new Toast builder bound to this component.
     */
    public function toast(): Toast
    {
        return new Toast($this);
    }

    /**
     * Create a new Confirm builder bound to this component.
     */
    public function confirm(string $title): Confirm
    {
        return new Confirm($title, $this);
    }

    /**
     * Fallback listener for confirm callbacks when Livewire.find() fails.
     */
    #[On('kore:confirm-callback')]
    public function handleConfirmCallback(string $method, array $params, string $callerRef): void
    {
        if ($callerRef !== $this->getId()) {
            return;
        }

        $this->{$method}(...$params);
    }
}
