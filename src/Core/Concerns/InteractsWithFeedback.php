<?php

namespace KoreUi\Core\Concerns;

use KoreUi\Feedback\Confirm;
use KoreUi\Feedback\Toast;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

trait InteractsWithFeedback
{
    /**
     * Methods authorized to run via kore:confirm-callback. Populated server-side
     * by Confirm::send() (one entry per onConfirm/onCancel handler, written
     * directly to this property — never via a public method, which the client
     * could call) and consumed on use. #[Locked] keeps the client from tampering
     * with it, so a forged kore:confirm-callback event cannot invoke an arbitrary
     * or protected method.
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $koreConfirmable = [];

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
     * Listener for confirm callbacks dispatched by ConfirmDialog on accept/reject.
     *
     * Security: only methods previously authorized server-side by Confirm::send()
     * (i.e. wired through ->onConfirm()/->onCancel()) are executed, and each
     * authorization is consumed on use. This blocks forged kore:confirm-callback
     * events from the browser that try to invoke arbitrary or protected/private
     * methods (e.g. runBulkAction) while skipping the confirmation dialog.
     */
    #[On('kore:confirm-callback')]
    public function handleConfirmCallback(string $method, array $params, string $callerRef): void
    {
        if ($callerRef !== $this->getId()) {
            return;
        }

        $index = array_search($method, $this->koreConfirmable, true);

        if ($index === false) {
            return;
        }

        unset($this->koreConfirmable[$index]);
        $this->koreConfirmable = array_values($this->koreConfirmable);

        $this->{$method}(...$params);
    }
}
