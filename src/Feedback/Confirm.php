<?php

namespace KoreUi\Feedback;

use Livewire\Component;

class Confirm
{
    protected string $title;

    protected ?string $description = null;

    protected string $type = 'question';

    protected ?string $confirmText = null;

    protected ?string $cancelText = null;

    protected array $onConfirm = [];

    protected array $onCancel = [];

    protected bool $persistent = false;

    protected ?Component $component = null;

    public function __construct(string $title, ?Component $component = null)
    {
        $this->title = $title;
        $this->component = $component;
    }

    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function confirmText(string $text): static
    {
        $this->confirmText = $text;

        return $this;
    }

    public function cancelText(string $text): static
    {
        $this->cancelText = $text;

        return $this;
    }

    public function onConfirm(string $method, array $params = []): static
    {
        $this->onConfirm = [
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    public function onCancel(string $method, array $params = []): static
    {
        $this->onCancel = [
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    public function persistent(): static
    {
        $this->persistent = true;

        return $this;
    }

    /**
     * Send the confirm dialog by dispatching kore:open with overlay attributes.
     */
    public function send(): void
    {
        $arguments = [
            'title'       => $this->title,
            'description' => $this->description,
            'type'        => $this->type,
            'confirmText' => $this->confirmText ?? config('kore-ui.feedback.confirm.confirm_text', 'Confirmar'),
            'cancelText'  => $this->cancelText ?? config('kore-ui.feedback.confirm.cancel_text', 'Cancelar'),
            'onConfirm'   => $this->onConfirm,
            'onCancel'    => $this->onCancel,
            'callerRef'   => $this->component?->getId(),
        ];

        $closesOnEscape = $this->persistent
            ? false
            : config('kore-ui.feedback.confirm.closes_on_escape', true);

        $closesOnClickAway = $this->persistent
            ? false
            : config('kore-ui.feedback.confirm.closes_on_click_away', false);

        $overlayAttributes = [
            'type'             => 'confirm',
            'size'             => config('kore-ui.feedback.confirm.size', 'md'),
            'closesOnClickAway' => $closesOnClickAway,
            'closesOnEscape'   => $closesOnEscape,
            'destroysOnClose'  => true,
        ];

        if ($this->component) {
            $this->authorizeCallbacks();

            $this->component->dispatch('kore:open',
                name: 'kore-confirm-dialog',
                arguments: $arguments,
                overlayAttributes: $overlayAttributes,
            );
        } else {
            // Outside Livewire — flash to session for next page load
            session()->flash('kore:confirm', [
                'name'              => 'kore-confirm-dialog',
                'arguments'        => $arguments,
                'overlayAttributes' => $overlayAttributes,
            ]);
        }
    }

    /**
     * Authorize the onConfirm/onCancel methods on the caller component so that a
     * later kore:confirm-callback event is allowed to invoke them. Written
     * directly to the #[Locked] property (not via a public method, which the
     * client could call) — this is the trusted server-side authorization path
     * that makes the kore:confirm-callback listener safe against forged events.
     */
    protected function authorizeCallbacks(): void
    {
        if (! $this->component || ! property_exists($this->component, 'koreConfirmable')) {
            return;
        }

        foreach ([$this->onConfirm, $this->onCancel] as $callback) {
            $method = $callback['method'] ?? null;

            if ($method && ! in_array($method, $this->component->koreConfirmable, true)) {
                $this->component->koreConfirmable[] = $method;
            }
        }
    }

    /**
     * Build the payload array (for testing).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'type'        => $this->type,
            'confirmText' => $this->confirmText ?? config('kore-ui.feedback.confirm.confirm_text', 'Confirmar'),
            'cancelText'  => $this->cancelText ?? config('kore-ui.feedback.confirm.cancel_text', 'Cancelar'),
            'onConfirm'   => $this->onConfirm,
            'onCancel'    => $this->onCancel,
            'persistent'  => $this->persistent,
        ];
    }
}
